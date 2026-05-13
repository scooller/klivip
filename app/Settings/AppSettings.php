<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AppSettings extends Settings
{
    public string $site_name;

    public string $site_description;

    public bool $enable_registrations;

    public static function group(): string
    {
        return 'app';
    }
}
