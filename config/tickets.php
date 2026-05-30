<?php

return [
    'layouts' => [
        'site' => 'admin.layout',
    ],

    // Disable the module's /tickets portal routes — customer-site handles support
    // via AdminSupportController at /admin/support with tickets.site.* route names.
    'routes' => [
        'site' => false,
    ],

    // Full-width in customer admin layout (no max-width constraint)
    'site_container' => '',

    'attachments' => [
        'enabled' => true,
    ],
];
