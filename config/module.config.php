<?php
/**
 * Created by PhpStorm.
 * User: vjvan
 * Date: 1/26/2017
 * Time: 15:02
 */
namespace Text\Processing;

use Zend\Router\Http\Segment;

return [
    'router'=> [
        'routes' => [
            'text' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/text[/:action[/:id]]',
                    'defaults' => [
                        'controller' => Controller\TextController::class,
                        'action' => 'index'
                    ]
                ]
            ],
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
            'text'        => __DIR__ . '/../view',
            'layout/layout' => __DIR__ . '/../view',
            'text/processing/layout' => __DIR__ . '/../view/layout/layout.phtml',
        ],
        'template_map' => [
            'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'text/layout' => __DIR__ . '/../view/layout/layout.phtml',
            'error/404'     => __DIR__ . '/../view/error/404.phtml',
            'error/index'   => __DIR__ . '/../view/error/index.phtml',
        ],
    ],
];