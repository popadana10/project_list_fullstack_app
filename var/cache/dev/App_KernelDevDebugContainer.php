<?php

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.

if (\class_exists(\ContainerFbycQ9b\App_KernelDevDebugContainer::class, false)) {
    // no-op
} elseif (!include __DIR__.'/ContainerFbycQ9b/App_KernelDevDebugContainer.php') {
    touch(__DIR__.'/ContainerFbycQ9b.legacy');

    return;
}

if (!\class_exists(App_KernelDevDebugContainer::class, false)) {
    \class_alias(\ContainerFbycQ9b\App_KernelDevDebugContainer::class, App_KernelDevDebugContainer::class, false);
}

return new \ContainerFbycQ9b\App_KernelDevDebugContainer([
    'container.build_hash' => 'FbycQ9b',
    'container.build_id' => '70e92039',
    'container.build_time' => 1715683794,
    'container.runtime_mode' => \in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) ? 'web=0' : 'web=1',
], __DIR__.\DIRECTORY_SEPARATOR.'ContainerFbycQ9b');
