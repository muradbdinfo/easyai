<?php

return [

    // Main tenant app  →  easyai.local  (or murad.bd on production)
    'app'   => env('APP_DOMAIN', 'ai.murad.bd'),

    // Admin panel      →  admin.easyai.local  (or admin.murad.bd on production)
    'admin' => env('ADMIN_DOMAIN', 'aiadmin.murad.bd'),

];
