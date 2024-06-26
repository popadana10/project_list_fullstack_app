<?php

namespace ContainerFbycQ9b;

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;

/**
 * @internal This class has been auto-generated by the Symfony Dependency Injection Component.
 */
class getPentatrionVite_EntrypointsLookupCollectionService extends App_KernelDevDebugContainer
{
    /**
     * Gets the private 'pentatrion_vite.entrypoints_lookup_collection' shared service.
     *
     * @return \Pentatrion\ViteBundle\Service\EntrypointsLookupCollection
     */
    public static function do($container, $lazyLoad = true)
    {
        include_once \dirname(__DIR__, 4).'/vendor/pentatrion/vite-bundle/src/Service/EntrypointsLookupCollection.php';

        return $container->privates['pentatrion_vite.entrypoints_lookup_collection'] = new \Pentatrion\ViteBundle\Service\EntrypointsLookupCollection(new \Symfony\Component\DependencyInjection\Argument\ServiceLocator($container->getService ??= $container->getService(...), [
            '_default' => ['privates', 'pentatrion_vite.entrypoints_lookup[_default]', 'getPentatrionVite_EntrypointsLookupDefaultService', true],
        ], [
            '_default' => '?',
        ]), '_default');
    }
}
