<?php

return [
    'request' => [
        'defaults' => [
            'string' => [
                'min:1',
                'max:255',
            ],

            'integer' => [
                'min:1',
                'max:99999',
            ],
        ],
        'names'    => [
            'email'    => [
                'email',
            ],
            'password' => [
                'confirm',
            ],
        ],
    ],
];
