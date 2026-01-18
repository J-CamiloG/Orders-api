<?php

return [

    'default' => 'default',

    'documentations' => [

        'default' => [

            'api' => [
                'title' => env('APP_NAME', 'Orders API'),
            ],

            'routes' => [
                /*
                 * Route for accessing api documentation interface
                 */
                'api' => 'api/documentation',
            ],

            'paths' => [
                /*
                 * Edit to include full URL in assets
                 */
                'use_absolute_path' => env('L5_SWAGGER_USE_ABSOLUTE_PATH', true),
                /*
                 * Set to null to use the default asset path
                 */
                'proxy' => false,

                /*
                 * File name of the generated json documentation file
                 */
                'docs_json' => 'api-docs.json',

                /*
                 * File name of the generated yaml documentation file
                 */
                'docs_yaml' => null,

                /*
                 * Format of swagger docs
                 */
                'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),

                /*
                 * Absolute paths to directory containing the swagger annotations are stored.
                 */
                'annotations' => [
                    base_path('app'),
                ],
            ],
        ],
    ],

    'defaults' => [

        'routes' => [

            /*
             * Route for accessing parsed swagger annotations.
             */
            'docs' => 'docs',

            /*
             * Route for Oauth2 authentication callback.
             */
            'oauth2_callback' => 'api/oauth2-callback',

            /*
             * Middleware allows to prevent unexpected access to swagger docs
             */
            'middleware' => [
                'api' => [],
                'asset' => [],
                'docs' => [],
                'oauth2_callback' => [],
            ],
        ],

        'paths' => [

            /*
             * Absolute path to location where parsed swagger annotations will be stored
             */
            'docs' => storage_path('api-docs'),

            /*
             * Absolute path to directory where swagger annotations are stored
             */
            'annotations' => base_path('app'),

            /*
             * Absolute path to directory where to export views
             */
            'views' => resource_path('views/vendor/l5-swagger'),

            /*
             * Edit to set the api's base path
             */
            'base' => env('L5_SWAGGER_BASE_PATH', null),

            /*
             * File names
             */
            'docs_json' => 'api-docs.json',
            'docs_yaml' => null,
        ],

        /*
         * Swagger UI settings
         */
        'swagger_ui' => [
            'url' => env('L5_SWAGGER_JSON_URL', '/docs/api-docs.json'),
        ],

        'securityDefinitions' => [

            'securitySchemes' => [
                'bearerAuth' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                ],
            ],

            'security' => [
                [
                    'bearerAuth' => [],
                ],
            ],
        ],

        /*
         * Generate docs automatically
         */
        'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', true),

        /*
         * Swagger UI display options
         */
        'ui' => [
            'display' => [
                'doc_expansion' => 'none',
                'filter' => true,
            ],
        ],
    ],
];
