<?php

/**
 * This file has been auto-generated
 * by the Symfony Routing Component.
 */

return [
    false, // $matchHost
    [ // $staticRoutes
        '/api/projects' => [
            [['_route' => 'api_project_index', '_controller' => 'App\\Controller\\ProjectController::index'], null, ['GET' => 0], null, false, false, null],
            [['_route' => 'api_project_create', '_controller' => 'App\\Controller\\ProjectController::create'], null, ['POST' => 0], null, false, false, null],
        ],
    ],
    [ // $regexpList
        0 => '{^(?'
                .'|/build/(.+)(*:18)'
                .'|/_error/(\\d+)(?:\\.([^/]++))?(*:53)'
                .'|/api/projects/([^/]++)(?'
                    .'|(*:85)'
                .')'
            .')/?$}sDu',
    ],
    [ // $dynamicRoutes
        18 => [[['_route' => 'pentatrion_vite_build_proxy', '_controller' => 'Pentatrion\\ViteBundle\\Controller\\ViteController::proxyBuild'], ['path'], null, null, false, true, null]],
        53 => [[['_route' => '_preview_error', '_controller' => 'error_controller::preview', '_format' => 'html'], ['code', '_format'], null, null, false, true, null]],
        85 => [
            [['_route' => 'api_project_show', '_controller' => 'App\\Controller\\ProjectController::show'], ['id'], ['GET' => 0], null, false, true, null],
            [['_route' => 'api_project_update', '_controller' => 'App\\Controller\\ProjectController::update'], ['id'], ['PUT' => 0, 'PATCH' => 1], null, false, true, null],
            [['_route' => 'api_project_delete', '_controller' => 'App\\Controller\\ProjectController::delete'], ['id'], ['DELETE' => 0], null, false, true, null],
            [null, null, null, null, false, false, 0],
        ],
    ],
    null, // $checkCondition
];
