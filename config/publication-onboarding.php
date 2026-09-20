<?php

return [
    'source_origin' => 'https://hexaprwire.com',
    'distributor_plugin' => 'hexa-pr-wire-distributor/hexa-pr-wire-distributor.php',
    'press_release_contract_version' => '1.0',
    'required_press_release_fields' => [
        'headline',
        'dateline',
        'lead',
        'body',
        'boilerplate',
        'media_contact',
        'canonical_url',
        'published_at',
        'image',
    ],
    'allowed_branding_roles' => ['logo', 'icon'],
    'allowed_branding_mime_types' => [
        'image/avif',
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/svg+xml',
        'image/webp',
    ],
];
