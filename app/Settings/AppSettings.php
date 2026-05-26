<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class AppSettings extends Settings
{
    public string $site_name;

    public string $site_description;

    public bool $enable_registrations;

    public bool $enable_home_login_without_code;

    public bool $enable_profile_unlock_otp;

    public bool $enable_profile_unlock_magic_link;

    public bool $hide_birth_date_on_profile;

    public static function group(): string
    {
        return 'app';
    }
}
