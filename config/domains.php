<?php

return [

    // Main tenant app  →  easyai.local  (or murad.bd on production)
    'app'   => env('APP_DOMAIN', 'easyai.local'),

    // Admin panel      →  admin.easyai.local  (or admin.murad.bd on production)
    'admin' => env('ADMIN_DOMAIN', 'admin.easyai.local'),

];