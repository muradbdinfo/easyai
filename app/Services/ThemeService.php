<?php
// FILE: app/Services/ThemeService.php
namespace App\Services;
use App\Models\Setting;

class ThemeService
{
    public static function get(): array
    {
        return [
            'brand'        => Setting::get('theme_brand',        '#6366f1'),
            'tenant_mode'  => Setting::get('theme_tenant_mode',  'dark'),
            'landing_mode' => Setting::get('theme_landing_mode', 'dark'),
        ];
    }
}