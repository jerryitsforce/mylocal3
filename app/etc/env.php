<?php
return [
    'cache_types' => [
        'compiled_config' => 1,
        'config' => 1,
        'layout' => 0,
        'block_html' => 0,
        'collections' => 1,
        'reflection' => 1,
        'db_ddl' => 1,
        'eav' => 1,
        'customer_notification' => 1,
        'config_integration' => 1,
        'config_integration_api' => 1,
        'full_page' => 1,
        'target_rule' => 1,
        'config_webservice' => 1,
        'translate' => 1,
        'amasty_shopby' => 1
    ],
    'MAGE_MODE' => 'developer',
    'cron' => [

    ],
    'backend' => [
        'frontName' => 'admin'
    ],
    'remote_storage' => [
        'driver' => 'file'
    ],
    'checkout' => [
        'async' => 0,
        'deferred_total_calculating' => 0
    ],
    'db' => [
        'connection' => [
            'default' => [
                'host' => '172.17.0.3',
                'username' => 'root',
                'dbname' => 'htprod50',
                'password' => 'maria',
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8mb4;',
                'active' => '1',
                'driver_options' => [
                    1014 => false
                ]
            ],
            'indexer' => [
                'host' => '172.17.0.3',
                'username' => 'root',
                'dbname' => 'htprod50',
                'password' => 'maria',
                'model' => 'mysql4',
                'engine' => 'innodb',
                'initStatements' => 'SET NAMES utf8mb4;'
            ]
        ],
        'table_prefix' => ''
    ],
    'crypt' => [
        'key' => '20dc2368bbf4a43b08271c9a80407af5'
    ],
    'resource' => [
        'default_setup' => [
            'connection' => 'default'
        ]
    ],
    'x-frame-options' => 'SAMEORIGIN',
    'session' => [
        'save' => 'files'
    ],
    'lock' => [
        'provider' => 'db',
        'config' => [
            'prefix' => ''
        ]
    ],
    'directories' => [
        'document_root_is_pub' => true
    ],
    'install' => [
        'date' => 'Thu, 07 Sep 2023 06:21:56 +0000'
    ],
    'static_content_on_demand_in_production' => 0,
    'force_html_minification' => 1,
    'system' => [
        'default' => [
            'catalog' => [
                'search' => [
                    'engine' => 'elasticsearch7',
                    'elasticsearch7_server_hostname' => '172.17.0.6',
                    'elasticsearch7_server_port' => '9200',
                    'elasticsearch7_index_prefix' => 'hotai'
                ]
            ],
            // 'dev' => [
            //     'css' => [
            //         'merge_css_files' => '1',
            //         'minify_files' => '1'
            //     ],
            //     'js' => [
            //         'merge_files' => '1',
            //         'minify_files' => '1'
            //     ],
            //     'template' => [
            //         'minify_html' => '1'
            //     ]
            // ]
        ],
        'dev' => [
            'template' => [
                'minify_html' => '1'
            ],
            'css' => [
                'merge_css_files' => '1'
            ]
        ]
    ],
    'ecpay' => [
        'general' => [
            'hash_key' => 'XlpnlxKU014COS9lT75IbrEyYsCINb39',
            'hash_iv' => '0KexyeojV5xhqR4w'
        ]
    ],
    'dev' => [
        'debug' => [
            'debug_logging' => 0
        ]
    ],
    'cache' => [
        'graphql' => [
            'id_salt' => 'aOSjO80xejrSVDY3usnUJ0vNtQNXIv7C'
        ],
        'frontend' => [
            'default' => [
                'id_prefix' => '69d_'
            ],
            'page_cache' => [
                'id_prefix' => '69d_'
            ]
        ],
        'allow_parallel_generation' => false
    ],
    'config' => [
        'async' => 0
    ],
    'queue' => 
        array (
            'amqp' => 
            array (
            'host' => 'localhost',
            'port' => '5672',
            'user' => '2ntakmlqwmx4i_stg',
            'password' => 'imDbUyrXKdUuLajt',
            'virtualhost' => '2ntakmlqwmx4i_stg',
            ),
            'consumers_wait_for_messages' => 0,
        )
];
