<?php return array(
    'root' => array(
        'name' => 'atc/whx4',
        'pretty_version' => 'dev-main',
        'version' => 'dev-main',
        'reference' => '0f7d7d52c152b4e0520a6ab58650b1b7920d1164',
        'type' => 'wordpress-plugin',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => false,
    ),
    'versions' => array(
        'atc/whx4' => array(
            'pretty_version' => 'dev-main',
            'version' => 'dev-main',
            'reference' => '0f7d7d52c152b4e0520a6ab58650b1b7920d1164',
            'type' => 'wordpress-plugin',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'composer/installers' => array(
            'pretty_version' => 'v1.12.0',
            'version' => '1.12.0.0',
            'reference' => 'd20a64ed3c94748397ff5973488761b22f6d3f19',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'rlanvin/php-rrule' => array(
            'pretty_version' => 'v2.6.0',
            'version' => '2.6.0.0',
            'reference' => '2a389a9fa67dda58bc5a569a3264555152db3c49',
            'type' => 'library',
            'install_path' => __DIR__ . '/../rlanvin/php-rrule',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'roundcube/plugin-installer' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'shama/baton' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
    ),
);
