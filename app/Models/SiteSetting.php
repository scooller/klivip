<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_name',
        'site_description',
        'meta_keywords',
        'meta_author',
        'tag_manager_id',
        'og_image',
        'contact_email',
        'contact_phone',
        'contact_address',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'linkedin_url',
        'youtube_url',
        'custom_css',
        'header_scripts',
        'footer_scripts',
        'maintenance_mode',
        'maintenance_message',
        'enable_registrations',
        'enable_home_login_without_code',
        'enable_profile_unlock_otp',
        'enable_profile_unlock_magic_link',
        'hide_birth_date_on_profile',
    ];

    protected $casts = [
        'maintenance_mode' => 'boolean',
        'enable_registrations' => 'boolean',
        'enable_home_login_without_code' => 'boolean',
        'enable_profile_unlock_otp' => 'boolean',
        'enable_profile_unlock_magic_link' => 'boolean',
        'hide_birth_date_on_profile' => 'boolean',
    ];

    public static function current(): self
    {
        return static::firstOrCreate(
            ['id' => 1],
            [
                'site_name' => config('app.name', 'Klivip'),
                'maintenance_mode' => false,
                'enable_registrations' => true,
                'enable_home_login_without_code' => false,
                'enable_profile_unlock_otp' => true,
                'enable_profile_unlock_magic_link' => true,
                'hide_birth_date_on_profile' => true,
            ]
        );
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::current()->{$key} ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::current()->update([$key => $value]);
    }

    public static function allSettings(): array
    {
        return static::current()->toArray();
    }

    public static function forFrontend(): array
    {
        $settings = static::current();

        return [
            'site_name' => $settings->site_name,
            'site_description' => $settings->site_description,
            'seo' => [
                'meta_keywords' => $settings->meta_keywords,
                'meta_author' => $settings->meta_author,
                'tag_manager_id' => $settings->tag_manager_id,
                'og_image' => $settings->og_image ? url($settings->og_image) : null,
            ],
            'contact' => [
                'email' => $settings->contact_email,
                'phone' => $settings->contact_phone,
                'address' => $settings->contact_address,
            ],
            'social' => [
                'facebook' => $settings->facebook_url,
                'instagram' => $settings->instagram_url,
                'twitter' => $settings->twitter_url,
                'linkedin' => $settings->linkedin_url,
                'youtube' => $settings->youtube_url,
            ],
            'custom_css' => $settings->custom_css,
            'header_scripts' => $settings->header_scripts,
            'footer_scripts' => $settings->footer_scripts,
            'maintenance_mode' => (bool) $settings->maintenance_mode,
            'maintenance_message' => $settings->maintenance_message,
            'enable_registrations' => (bool) $settings->enable_registrations,
            'enable_home_login_without_code' => (bool) $settings->enable_home_login_without_code,
            'enable_profile_unlock_otp' => (bool) $settings->enable_profile_unlock_otp,
            'enable_profile_unlock_magic_link' => (bool) $settings->enable_profile_unlock_magic_link,
            'hide_birth_date_on_profile' => (bool) $settings->hide_birth_date_on_profile,
        ];
    }
}
