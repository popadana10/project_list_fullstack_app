<?php

namespace Pentatrion\ViteBundle\Service;

use Pentatrion\ViteBundle\Event\RenderAssetTagEvent;
use Pentatrion\ViteBundle\Model\Tag;
use Pentatrion\ViteBundle\Twig\EntryFilesTwigExtension;
use Pentatrion\ViteBundle\Util\InlineContent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * @phpstan-import-type ViteEntryScriptTagsOptions from EntryFilesTwigExtension
 * @phpstan-import-type ViteEntryLinkTagsOptions from EntryFilesTwigExtension
 */
class EntrypointRenderer implements ResetInterface
{
    /** @var array<string, bool> */
    private array $returnedViteClients = [];

    /** @var array<string, bool> */
    private array $returnedReactRefresh = [];

    /** @var array<string, bool> */
    private array $returnedViteLegacyScripts = [];

    /** @var array<'scripts'|'styles', array<string, Tag>> */
    private array $renderedFiles = [
        'scripts' => [],
        'styles' => [],
    ];

    public function __construct(
        private EntrypointsLookupCollection $entrypointsLookupCollection,
        private TagRendererCollection $tagRendererCollection,
        private bool $useAbsoluteUrl = false,
        private string $preload = 'link-tag',
        private ?RequestStack $requestStack = null,
        private ?EventDispatcherInterface $eventDispatcher = null
    ) {
    }

    private function getEntrypointsLookup(?string $configName = null): EntrypointsLookup
    {
        return $this->entrypointsLookupCollection->getEntrypointsLookup($configName);
    }

    private function getTagRenderer(?string $configName = null): TagRenderer
    {
        return $this->tagRendererCollection->getTagRenderer($configName);
    }

    private function completeURL(string $path, bool $useAbsoluteUrl = false): string
    {
        if (str_starts_with($path, 'http') || false === $useAbsoluteUrl || null === $this->requestStack || null === $this->requestStack->getCurrentRequest()) {
            return $path;
        }

        return $this->requestStack->getCurrentRequest()->getUriForPath($path);
    }

    /**
     * @param ViteEntryScriptTagsOptions|ViteEntryLinkTagsOptions $options
     */
    private function shouldUseAbsoluteURL(array $options, ?string $configName = null): bool
    {
        $viteServer = $this->getEntrypointsLookup($configName)->getViteServer();

        return is_null($viteServer) && ($this->useAbsoluteUrl || (isset($options['absolute_url']) && true === $options['absolute_url']));
    }

    public function getMode(?string $configName = null): ?string
    {
        $entrypointsLookup = $this->getEntrypointsLookup($configName);

        if (!$entrypointsLookup->hasFile()) {
            return null;
        }

        return $entrypointsLookup->isBuild() ? 'build' : 'dev';
    }

    public function reset(): void
    {
        $this->returnedViteClients = [];
        $this->returnedReactRefresh = [];
        $this->returnedViteLegacyScripts = [];
        $this->renderedFiles = [
            'scripts' => [],
            'styles' => [],
        ];
    }

    /**
     * @return array<string, Tag>
     */
    public function getRenderedScripts(): array
    {
        return $this->renderedFiles['scripts'];
    }

    /**
     * @return array<string, Tag>
     */
    public function getRenderedStyles(): array
    {
        return $this->renderedFiles['styles'];
    }

    /**
     * @param ViteEntryScriptTagsOptions $options
     *
     * @phpstan-return ($toString is true ? string : array<Tag>)
     */
    public function renderScripts(
        string $entryName,
        array $options = [],
        ?string $configName = null,
        bool $toString = true
    ): string|array {
        $entrypointsLookup = $this->getEntrypointsLookup($configName);
        $tagRenderer = $this->getTagRenderer($configName);

        if (!$entrypointsLookup->hasFile()) {
            return '';
        }

        $useAbsoluteUrl = $this->shouldUseAbsoluteURL($options, $configName);

        $tags = [];
        $viteServer = $entrypointsLookup->getViteServer();
        $isBuild = $entrypointsLookup->isBuild();
        $base = $entrypointsLookup->getBase();

        if (!is_null($viteServer)) {
            // vite server is active
            if (!isset($this->returnedViteClients[$configName])) {
                $tags[] = $tagRenderer->createViteClientScript($viteServer.$base.'@vite/client');

                $this->returnedViteClients[$configName] = true;
            }

            if (
                !isset($this->returnedReactRefresh[$configName])
                && isset($options['dependency']) && 'react' === $options['dependency']
            ) {
                $tags[] = $tagRenderer->createReactRefreshScript($viteServer.$base);

                $this->returnedReactRefresh[$configName] = true;
            }
        } elseif (
            $entrypointsLookup->isLegacyPluginEnabled()
            && !isset($this->returnedViteLegacyScripts[$configName])
        ) {
            /* legacy section when vite server is inactive */
            $tags[] = $tagRenderer->createDetectModernBrowserScript();
            $tags[] = $tagRenderer->createDynamicFallbackScript();
            $tags[] = $tagRenderer->createSafariNoModuleScript();

            foreach ($entrypointsLookup->getJSFiles('polyfills-legacy') as $filePath) {
                // normally only one js file
                $tags[] = $tagRenderer->createScriptTag(
                    [
                        'nomodule' => true,
                        'crossorigin' => true,
                        'src' => $this->completeURL($filePath, $useAbsoluteUrl),
                        'id' => 'vite-legacy-polyfill',
                    ]
                );
            }

            $this->returnedViteLegacyScripts[$configName] = true;
        }

        /* normal js scripts */
        foreach ($entrypointsLookup->getJSFiles($entryName) as $filePath) {
            if (!isset($this->renderedFiles['scripts'][$filePath])) {
                $tag = $tagRenderer->createScriptTag(
                    array_merge(
                        [
                            'type' => 'module',
                            'src' => $this->completeURL($filePath, $useAbsoluteUrl),
                            'integrity' => $entrypointsLookup->getFileHash($filePath),
                        ],
                        $options['attr'] ?? []
                    )
                );

                $tags[] = $tag;

                $this->renderedFiles['scripts'][$filePath] = $tag;
            }
        }

        /* legacy js scripts */
        if ($entrypointsLookup->hasLegacy($entryName)) {
            $id = self::pascalToKebab("vite-legacy-entry-$entryName");

            $filePath = $entrypointsLookup->getLegacyJSFile($entryName);
            if (!isset($this->renderedFiles['scripts'][$filePath])) {
                $tag = $tagRenderer->createScriptTag(
                    [
                        'nomodule' => true,
                        'data-src' => $this->completeURL($filePath, $useAbsoluteUrl),
                        'id' => $id,
                        'crossorigin' => true,
                        'class' => 'vite-legacy-entry',
                        'integrity' => $entrypointsLookup->getFileHash($filePath),
                    ],
                    InlineContent::getSystemJSInlineCode($id)
                );

                $tags[] = $tag;

                $this->renderedFiles['scripts'][$filePath] = $tag;
            }
        }

        return $this->renderTags($tags, $isBuild, $toString);
    }

    /**
     * @param ViteEntryLinkTagsOptions $options
     *
     * @phpstan-return ($toString is true ? string : array<Tag>)
     */
    public function renderLinks(
        string $entryName,
        array $options = [],
        ?string $configName = null,
        bool $toString = true
    ): string|array {
        $entrypointsLookup = $this->getEntrypointsLookup($configName);
        $tagRenderer = $this->getTagRenderer($configName);

        if (!$entrypointsLookup->hasFile()) {
            return '';
        }

        $useAbsoluteUrl = $this->shouldUseAbsoluteURL($options, $configName);
        $isBuild = $entrypointsLookup->isBuild();

        $tags = [];

        foreach ($entrypointsLookup->getCSSFiles($entryName) as $filePath) {
            if (!isset($this->renderedFiles['styles'][$filePath])) {
                $tag = $tagRenderer->createLinkStylesheetTag(
                    $this->completeURL($filePath, $useAbsoluteUrl),
                    array_merge(['integrity' => $entrypointsLookup->getFileHash($filePath)], $options['attr'] ?? [])
                );

                $tags[] = $tag;

                $this->renderedFiles['styles'][$filePath] = $tag;
            }
        }

        if ($isBuild) {
            foreach ($entrypointsLookup->getJavascriptDependencies($entryName) as $filePath) {
                if (!isset($this->renderedFiles['scripts'][$filePath])) {
                    $tag = $tagRenderer->createModulePreloadLinkTag(
                        $this->completeURL($filePath, $useAbsoluteUrl),
                        ['integrity' => $entrypointsLookup->getFileHash($filePath)]
                    );

                    $tags[] = $tag;

                    $this->renderedFiles['scripts'][$filePath] = $tag;
                }
            }
        }

        if ($isBuild && isset($options['preloadDynamicImports']) && true === $options['preloadDynamicImports']) {
            foreach ($entrypointsLookup->getJavascriptDynamicDependencies($entryName) as $filePath) {
                if (!isset($this->renderedFiles['scripts'][$filePath])) {
                    $tag = $tagRenderer->createModulePreloadLinkTag(
                        $this->completeURL($filePath, $useAbsoluteUrl),
                        ['integrity' => $entrypointsLookup->getFileHash($filePath)]
                    );

                    $tags[] = $tag;

                    $this->renderedFiles['scripts'][$filePath] = $tag;
                }
            }
        }

        return $this->renderTags($tags, $isBuild, $toString);
    }

    /**
     * @param array<Tag> $tags
     *
     * @phpstan-return ($toString is true ? string : array<Tag>)
     */
    public function renderTags(array $tags, bool $isBuild, bool $toString): string|array
    {
        if (null !== $this->eventDispatcher) {
            foreach ($tags as $tag) {
                $this->eventDispatcher->dispatch(new RenderAssetTagEvent($isBuild, $tag));
            }
        }

        if ('link-tag' !== $this->preload) {
            $tags = array_filter($tags, function (Tag $tag) {
                return !$tag->isModulePreload();
            });
        }

        return $toString
        ? implode('', array_map(function ($tagEvent) {
            return TagRenderer::generateTag($tagEvent);
        }, $tags))
        : $tags;
    }

    public static function pascalToKebab(string $str): string
    {
        return strtolower((string) preg_replace('/[A-Z]/', '-\\0', lcfirst($str)));
    }
}
