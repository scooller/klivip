<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'birth_date', 'avatar_path', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }

    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class)
            ->withTimestamps();
    }

    // Nuevas relaciones del sistema de sorteos
    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function sweepstakeCoupons(): HasMany
    {
        return $this->hasMany(SweepstakeCoupon::class);
    }

    public function validSweepstakeCoupons(): HasMany
    {
        return $this->hasMany(SweepstakeCoupon::class)
            ->where('is_voided', false)
            ->whereNull('deleted_at');
    }

    public function getCouponsCountInSweepstake(Sweepstake $sweepstake): int
    {
        return $this->couponRedemptions()
            ->where('sweepstake_id', $sweepstake->id)
            ->where('is_voided', false)
            ->sum('coupon_count');
    }

    public function hasRole(UserRole $role): bool
    {
        return $this->role === $role;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(UserRole::SuperAdmin);
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(UserRole::Admin);
    }

    public function isManager(): bool
    {
        return $this->hasRole(UserRole::Manager);
    }

    public function isPanelUser(): bool
    {
        return $this->isSuperAdmin() || $this->isAdmin() || $this->isManager();
    }

    public function belongsToSite(Site|int $site): bool
    {
        $siteId = $site instanceof Site ? $site->getKey() : $site;

        return $this->sites()->whereKey($siteId)->exists();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        $request = request();

        if (! $request instanceof Request) {
            return false;
        }

        $host = $request->getHost();

        if ($panel->getId() !== 'admin') {
            return false;
        }

        return str_starts_with($host, 'admin.') && $this->isPanelUser();
    }
}
