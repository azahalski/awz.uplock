<?php
return [
    'ui.entity-selector' => [
        'value' => [
            'entities' => [
                [
                    'entityId' => 'awzuplock-user',
                    'provider' => [
                        'moduleId' => 'awz.uplock',
                        'className' => '\\Awz\\Uplock\\Access\\EntitySelectors\\User'
                    ],
                ],
                [
                    'entityId' => 'awzuplock-group',
                    'provider' => [
                        'moduleId' => 'awz.uplock',
                        'className' => '\\Awz\\Uplock\\Access\\EntitySelectors\\Group'
                    ],
                ],
            ]
        ],
        'readonly' => true,
    ]
];