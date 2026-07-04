# Propuesta Técnica: Rediseño Completo del Módulo de Cupones

**Fecha:** 2026-07-02  
**Versión:** 1.0  
**Tecnologías:** Laravel 13, PHP 8.4, MySQL 8.0+, Filament v5

---

## 1. Análisis Funcional

### 1.1 Comprensión del Problema

El sistema actual mezcla cupones, promociones y juegos en una arquitectura que no se alinea con el modelo de negocio real. La entidad central debería ser el **sorteo**, donde los cupones representan participaciones individuales que los usuarios obtienen mediante el cobro de packs configurados (vía QR o link).

**Concepto clave:** Un cupón NO es un producto; es un **registro de participación** en un sorteo, generado por la acción de cobrar un origen (QR/link/pack) que representa uno o múltiples participaciones.

### 1.2 Resumen de Reglas de Negocio

| # | Regla | Implicación Técnica |
|---|-------|---------------------|
| 1 | Cupones SIEMPRE vinculados a un sorteo | `coupons.sweepstake_id` es NOT NULL y tiene FK |
| 2 | Vencimiento aplica al sorteo | Validar `sweepstakes.expires_at`, no campo individual en coupon |
| 3 | Usuario no requiere autenticación previa | Permitir identificación en tiempo de cobro (email/teléfono) |
| 4 | Flujo de cobro desde QR/link | Endpoint público con validación robusta |
| 5 | Pack genera 1 o N cupones | Lógica de generación masiva dentro de transacción |
| 6 | Máximo configurable por sorteo | Campo `sweepstakes.max_coupons` con validación |
| 7 | Numeración correlativa por sorteo | `coupon_number` único por `(sweepstake_id, number)` |
| 8 | Números NO se reutilizan | Soft deletes + flag `voided` + auditoría |
| 9 | Cada cupón es único | PK `id` + índice único por `(sweepstake_id, number)` |
| 10 | Trazabilidad completa del cobro | Tabla `coupon_redemptions` con metadata |
| 11 | Perfiles admin y manager | Policies + roles de Filament |

### 1.3 Decisiones Técnicas Importantes

#### Decisión 1: ¿Cómo manejar la numeración correlativa?

**Opción recomendada:** Usar contador por sorteo en la tabla `sweepstakes.last_coupon_number` dentro de transacción con `DB::transaction()` + `lockForUpdate()`.

**Por qué:**
- Simplicidad de implementación
- Garantía de unicidad con bloqueo a nivel de fila
- No requiere secuencias MySQL o triggers
- Permite consultas rápidas: "último número emitido"

**Alternativa:** Usar auto-increment de tabla `coupons` y calcular correlativo con `COUNT(*)` por sorteo.

**Por qué NO:** Menos performante, requiere subqueries en inserts masivos, no garantiza correlativo continuo si hay soft deletes.

#### Decisión 2: ¿Soft deletes o hard deletes para cupones?

**Opción recomendada:** Soft deletes (`deleted_at`) + flag `voided` (enum).

**Por qué:**
- Auditoría completa de cupones emitidos
- Diferenciación entre "eliminado por error" (soft delete) y "anulado/invalidado" (voided = true)
- Trazabilidad para sorteo: identificar números faltantes

**Alternativa:** Solo hard deletes.

**Por qué NO:** Pierde información crítica de auditoría y no permite revertir decisiones.

#### Decisión 3: ¿Dónde guardar la metadata del QR/link?

**Opción recomendada:** Tabla `redemption_sources` separada de `redemption_links`.

**Por qué:**
- `redemption_sources`: Define el tipo de origen (QR, link, manual, API)
- `redemption_links`: Define instancias específicas con config (cantidad de cupones, descripción, vigencia)
- Permite reutilizar tipos de origen
- Relación polimórfica flexible si en futuro se agregan más orígenes

**Alternativa:** Solo tabla `redemption_links` con campo `type`.

**Por qué NO:** Duplica configuración de tipos, menos escalable.

#### Decisión 4: ¿Cómo manejar concurrencia en cobro masivo?

**Opción recomendada:** Transacción + `lockForUpdate()` en sweepstakes + validación de límite antes de insert.

**Por qué:**
- Bloqueo optimizado a nivel de fila
- Previente race conditions en `last_coupon_number` y `max_coupons`
- Laravel maneja rollback automático en error
- No requiere locks de tabla complejos

**Alternativa:** `DB::raw("LOCK TABLES sweepstakes WRITE")` o cache locks.

**Por qué NO:** Deadlocks complejos, escalabilidad limitada, harder to test.

#### Decisión 5: ¿Identificación de usuario opcional u obligatoria?

**Opción recomendada:** Opcional pero recomendada. Si el usuario está autenticado, usar su ID. Si no, identificar con email/teléfono (y crear/actualizar usuario en el proceso).

**Por qué:**
- Flexibilidad para usuarios anónimos
- Trazabilidad sin fricción de registro
- Permite enriquecer perfil post-cobro

---

## 2. Diseño de Base de Datos

### 2.1 Esquema Completo

```sql
-- sites
-- Propósito: Representa un sitio/sucursal/cliente que organiza sorteos
CREATE TABLE sites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_sites_slug ON sites(slug);
CREATE INDEX idx_sites_active ON sites(is_active);


-- sweepstakes
-- Propósito: Sorteo individual asociado a un site, define vigencia, límites y config
CREATE TABLE sweepstakes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    site_id BIGINT UNSIGNED NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Fechas
    starts_at TIMESTAMP NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    
    -- Límites
    max_coupons INT UNSIGNED NULL COMMENT 'Límite total de cupones/participaciones. NULL = sin límite',
    max_coupons_per_user INT UNSIGNED NULL COMMENT 'Límite por usuario. NULL = sin límite',
    
    -- Estado
    is_active BOOLEAN DEFAULT TRUE,
    is_published BOOLEAN DEFAULT FALSE COMMENT 'Indica si el sorteo está disponible públicamente',
    
    -- Contador de numeración correlativa
    last_coupon_number INT UNSIGNED DEFAULT 0 COMMENT 'Último número de cupón emitido. Se incrementa en cada cobro.',
    
    -- Metadatos
    prize_description TEXT COMMENT 'Descripción del premio del sorteo',
    draw_at TIMESTAMP NULL COMMENT 'Fecha/hora prevista para el sorteo',
    draw_result TEXT COMMENT 'Resultado del sorteo (ganadores, observaciones, etc.)',
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX idx_sweepstakes_site_slug ON sweepstakes(site_id, slug);
CREATE INDEX idx_sweepstakes_site ON sweepstakes(site_id);
CREATE INDEX idx_sweepstakes_dates ON sweepstakes(starts_at, expires_at);
CREATE INDEX idx_sweepstakes_active ON sweepstakes(is_active, is_published);


-- redemption_sources (tipos de origen de canje)
-- Propósito: Define los tipos de orígenes de canje (QR, link, manual, API)
CREATE TABLE redemption_sources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Código único del tipo (ej: qr, link, manual, api)',
    name VARCHAR(255) NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_redemption_sources_code ON redemption_sources(code);
CREATE INDEX idx_redemption_sources_active ON redemption_sources(is_active);


-- redemption_links (QRs/links/packs específicos)
-- Propósito: Define instancias específicas de orígenes de canje con configuración
CREATE TABLE redemption_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sweepstake_id BIGINT UNSIGNED NOT NULL,
    redemption_source_id BIGINT UNSIGNED NOT NULL,
    
    -- Identificación
    code VARCHAR(100) NOT NULL UNIQUE COMMENT 'Código único del link/QR (ej: UUID o slug generado)',
    title VARCHAR(255) NOT NULL,
    description TEXT,
    
    -- Configuración del pack
    coupon_count INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Cantidad de cupones que genera este pack',
    
    -- Vigencia del link (puede ser más restrictiva que el sorteo)
    valid_from TIMESTAMP NULL,
    valid_until TIMESTAMP NULL,
    max_redemptions INT UNSIGNED NULL COMMENT 'Límite de veces que se puede canjear este link. NULL = sin límite',
    
    -- Estado
    is_active BOOLEAN DEFAULT TRUE,
    redemption_count INT UNSIGNED DEFAULT 0 COMMENT 'Contador de cuántas veces se canjeó este link',
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (sweepstake_id) REFERENCES sweepstakes(id) ON DELETE CASCADE,
    FOREIGN KEY (redemption_source_id) REFERENCES redemption_sources(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX idx_redemption_links_code ON redemption_links(code);
CREATE INDEX idx_redemption_links_sweepstake ON redemption_links(sweepstake_id);
CREATE INDEX idx_redemption_links_source ON redemption_links(redemption_source_id);
CREATE INDEX idx_redemption_links_dates ON redemption_links(valid_from, valid_until);
CREATE INDEX idx_redemption_links_active ON redemption_links(is_active);


-- coupon_redemptions (evento de cobro)
-- Propósito: Registra cada evento de cobro, con metadata completa de trazabilidad
CREATE TABLE coupon_redemptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sweepstake_id BIGINT UNSIGNED NOT NULL,
    redemption_link_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL COMMENT 'Usuario autenticado. NULL si cobrado sin login previo',
    
    -- Identificación del usuario (incluso si no estaba autenticado)
    user_email VARCHAR(255) NULL,
    user_phone VARCHAR(50) NULL,
    user_name VARCHAR(255) NULL,
    
    -- Configuración del cobro
    coupon_count INT UNSIGNED NOT NULL COMMENT 'Cantidad de cupones generados en este cobro',
    coupon_start_number INT UNSIGNED NOT NULL COMMENT 'Número del primer cupón generado',
    coupon_end_number INT UNSIGNED NOT NULL COMMENT 'Número del último cupón generado',
    
    -- Metadata del cobro
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    redemption_channel VARCHAR(50) NULL COMMENT 'Canal del cobro: web, mobile, qr_scan, etc.',
    device_info JSON NULL,
    
    -- Estado
    is_voided BOOLEAN DEFAULT FALSE COMMENT 'TRUE si este cobro fue anulado/revertido',
    voided_at TIMESTAMP NULL,
    voided_reason TEXT NULL,
    voided_by BIGINT UNSIGNED NULL COMMENT 'ID del admin que anuló el cobro',
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    
    FOREIGN KEY (sweepstake_id) REFERENCES sweepstakes(id) ON DELETE CASCADE,
    FOREIGN KEY (redemption_link_id) REFERENCES redemption_links(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (voided_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_coupon_redemptions_sweepstake ON coupon_redemptions(sweepstake_id);
CREATE INDEX idx_coupon_redemptions_link ON coupon_redemptions(redemption_link_id);
CREATE INDEX idx_coupon_redemptions_user ON coupon_redemptions(user_id);
CREATE INDEX idx_coupon_redemptions_email ON coupon_redemptions(user_email);
CREATE INDEX idx_coupon_redemptions_voided ON coupon_redemptions(is_voided);
CREATE INDEX idx_coupon_redemptions_created ON coupon_redemptions(created_at);


-- coupons (cupones/participaciones individuales)
-- Propósito: Cada cupón es 1 participación individual en un sorteo
CREATE TABLE coupons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sweepstake_id BIGINT UNSIGNED NOT NULL,
    redemption_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NULL,
    
    -- Numeración
    coupon_number INT UNSIGNED NOT NULL COMMENT 'Número correlativo ÚNICO dentro del sorteo',
    
    -- Estado
    is_voided BOOLEAN DEFAULT FALSE COMMENT 'TRUE si este cupón fue anulado (no participa)',
    voided_at TIMESTAMP NULL,
    voided_reason TEXT NULL,
    voided_by BIGINT UNSIGNED NULL,
    
    -- Auditoría de uso (si el cupón fue "usado" en el sorteo)
    is_used BOOLEAN DEFAULT FALSE COMMENT 'TRUE si este cupón fue seleccionado/ganador en el sorteo',
    used_at TIMESTAMP NULL,
    used_by BIGINT UNSIGNED NULL COMMENT 'ID del admin que marcó el cupón como usado',
    
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,
    
    FOREIGN KEY (sweepstake_id) REFERENCES sweepstakes(id) ON DELETE CASCADE,
    FOREIGN KEY (redemption_id) REFERENCES coupon_redemptions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (voided_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (used_by) REFERENCES users(id) ON DELETE SET NULL,
    
    UNIQUE KEY idx_coupon_unique_number (sweepstake_id, coupon_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE UNIQUE INDEX idx_coupons_sweepstake_number ON coupons(sweepstake_id, coupon_number);
CREATE INDEX idx_coupons_redemption ON coupons(redemption_id);
CREATE INDEX idx_coupons_user ON coupons(user_id);
CREATE INDEX idx_coupons_voided ON coupons(is_voided);
CREATE INDEX idx_coupons_used ON coupons(is_used);


-- users (extendiendo tabla existente)
-- Propósito: Usuarios del sistema. Asumimos que ya existe, pero la agregamos para completitud
-- Nota: Esta tabla probablemente ya existe. Solo agregamos índices útiles si no los tiene.

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_phone ON users(phone);
CREATE INDEX idx_users_name ON users(name);
```

### 2.2 Estrategia de Numeración Correlativa

**Implementación recomendada:**

1. Campo `sweepstakes.last_coupon_number` almacenado en DB
2. Dentro de transacción:
   - `DB::transaction()` → bloqueo explícito
   - `$sweepstake->lockForUpdate()` → bloquea la fila del sorteo
   - Leer `last_coupon_number`
   - Calcular rango: `start = last + 1`, `end = last + coupon_count`
   - Insertar N cupones con números `[start, end]`
   - Actualizar `last_coupon_number = end`
   - Insertar registro en `coupon_redemptions`
   - Commit automático de Laravel

**Por qué funciona:**
- `lockForUpdate()` previene que otras transacciones lean/escriban el mismo sorteo hasta que esta termine
- La unicidad de `(sweepstake_id, coupon_number)` en DB previene duplicados por error de código
- Transacción garantiza atomicidad: o se crean todos los cupones, o ninguno

**Manejo de concurrencia:**
```php
DB::transaction(function () use ($sweepstake, $couponCount) {
    $sweepstake = Sweepstake::lockForUpdate()->find($sweepstake->id);
    
    $startNumber = $sweepstake->last_coupon_number + 1;
    $endNumber = $startNumber + $couponCount - 1;
    
    // Validar límite máximo
    if ($sweepstake->max_coupons && $endNumber > $sweepstake->max_coupons) {
        throw new SweepstakeLimitException('Sorteo sin cupos disponibles');
    }
    
    // Crear cupones
    for ($i = $startNumber; $i <= $endNumber; $i++) {
        Coupon::create([
            'sweepstake_id' => $sweepstake->id,
            'redemption_id' => $redemption->id,
            'user_id' => $userId,
            'coupon_number' => $i,
        ]);
    }
    
    // Actualizar contador
    $sweepstake->update(['last_coupon_number' => $endNumber]);
});
```

### 2.3 Auditoría de Cobro, Reversas y Estados

**Flujo de reversa:**
1. No se eliminan registros físicos
2. Se marca `coupon_redemptions.is_voided = TRUE`
3. Se marca `coupons.is_voided = TRUE` para todos los cupones de esa redención
4. Se registran `voided_at`, `voided_reason`, `voided_by`
5. Los números correlativos NO se reutilizan

**Importante:**
- Soft delete de `redemption_links` para no romper relaciones históricas
- Los cupones anulados NO participan en el sorteo (`is_voided = TRUE`)
- Exportación CSV debe filtrar `is_voided = FALSE` y `deleted_at IS NULL`

---

## 3. Modelo Eloquent

### 3.1 Site

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Site extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relaciones
    public function sweepstakes(): HasMany
    {
        return $this->hasMany(Sweepstake::class);
    }

    public function activeSweepstakes(): HasMany
    {
        return $this->hasMany(Sweepstake::class)
            ->where('is_active', true)
            ->where('is_published', true);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

### 3.2 Sweepstake

```php
<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Sweepstake extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'site_id',
        'name',
        'slug',
        'description',
        'starts_at',
        'expires_at',
        'max_coupons',
        'max_coupons_per_user',
        'is_active',
        'is_published',
        'last_coupon_number',
        'prize_description',
        'draw_at',
        'draw_result',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'draw_at' => 'datetime',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'last_coupon_number' => 'integer',
        'max_coupons' => 'integer',
        'max_coupons_per_user' => 'integer',
    ];

    // Relaciones
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function redemptionLinks(): HasMany
    {
        return $this->hasMany(RedemptionLink::class);
    }

    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function validCoupons(): HasMany
    {
        return $this->hasMany(Coupon::class)
            ->where('is_voided', false)
            ->whereNull('deleted_at');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where('is_active', true)
            ->where('is_published', true)
            ->where('starts_at', '<=', $now)
            ->where('expires_at', '>', $now);
    }

    // Métodos de negocio
    public function isAvailable(): bool
    {
        return $this->is_active
            && $this->is_published
            && Carbon::now()->between($this->starts_at, $this->expires_at);
    }

    public function hasAvailableSlots(int $couponCount = 1): bool
    {
        if (!$this->max_coupons) {
            return true;
        }

        return ($this->last_coupon_number + $couponCount) <= $this->max_coupons;
    }

    public function getEmittedCouponsCount(): int
    {
        return $this->last_coupon_number;
    }

    public function getAvailableCouponsCount(): int
    {
        if (!$this->max_coupons) {
            return PHP_INT_MAX;
        }

        return max(0, $this->max_coupons - $this->last_coupon_number);
    }

    public function getValidCouponsCount(): int
    {
        return $this->coupons()
            ->where('is_voided', false)
            ->whereNull('deleted_at')
            ->count();
    }

    public function getUserCouponCount(?User $user): int
    {
        if (!$user) {
            return 0;
        }

        return $this->couponRedemptions()
            ->where('user_id', $user->id)
            ->where('is_voided', false)
            ->sum('coupon_count');
    }

    public function hasUserReachedLimit(?User $user, int $couponCount = 1): bool
    {
        if (!$this->max_coupons_per_user || !$user) {
            return false;
        }

        return ($this->getUserCouponCount($user) + $couponCount) > $this->max_coupons_per_user;
    }
}
```

### 3.3 RedemptionSource

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RedemptionSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relaciones
    public function redemptionLinks(): HasMany
    {
        return $this->hasMany(RedemptionLink::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

### 3.4 RedemptionLink

```php
<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class RedemptionLink extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sweepstake_id',
        'redemption_source_id',
        'code',
        'title',
        'description',
        'coupon_count',
        'valid_from',
        'valid_until',
        'max_redemptions',
        'is_active',
        'redemption_count',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_active' => 'boolean',
        'redemption_count' => 'integer',
        'coupon_count' => 'integer',
        'max_redemptions' => 'integer',
    ];

    // Relaciones
    public function sweepstake(): BelongsTo
    {
        return $this->belongsTo(Sweepstake::class);
    }

    public function redemptionSource(): BelongsTo
    {
        return $this->belongsTo(RedemptionSource::class);
    }

    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasManyThrough(Coupon::class, CouponRedemption::class);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        $now = Carbon::now();
        return $query->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>', $now);
            })
            ->where(function ($q) {
                $q->whereNull('max_redemptions')
                    ->whereColumn('redemption_count', '<', 'max_redemptions');
            });
    }

    // Métodos de negocio
    public function isAvailable(): bool
    {
        $now = Carbon::now();

        if (!$this->is_active) {
            return false;
        }

        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        if ($this->max_redemptions && $this->redemption_count >= $this->max_redemptions) {
            return false;
        }

        return $this->sweepstake->isAvailable();
    }

    public function incrementRedemptionCount(): void
    {
        $this->increment('redemption_count');
    }
}
```

### 3.5 CouponRedemption

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class CouponRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'sweepstake_id',
        'redemption_link_id',
        'user_id',
        'user_email',
        'user_phone',
        'user_name',
        'coupon_count',
        'coupon_start_number',
        'coupon_end_number',
        'ip_address',
        'user_agent',
        'redemption_channel',
        'device_info',
        'is_voided',
        'voided_at',
        'voided_reason',
        'voided_by',
    ];

    protected $casts = [
        'coupon_count' => 'integer',
        'coupon_start_number' => 'integer',
        'coupon_end_number' => 'integer',
        'is_voided' => 'boolean',
        'voided_at' => 'datetime',
        'device_info' => 'array',
    ];

    // Relaciones
    public function sweepstake(): BelongsTo
    {
        return $this->belongsTo(Sweepstake::class);
    }

    public function redemptionLink(): BelongsTo
    {
        return $this->belongsTo(RedemptionLink::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'redemption_id');
    }

    public function validCoupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'redemption_id')
            ->where('is_voided', false)
            ->whereNull('deleted_at');
    }

    // Scopes
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('is_voided', false);
    }

    public function scopeVoided(Builder $query): Builder
    {
        return $query->where('is_voided', true);
    }

    // Métodos de negocio
    public function void(string $reason, ?User $voidedBy = null): void
    {
        $this->update([
            'is_voided' => true,
            'voided_at' => now(),
            'voided_reason' => $reason,
            'voided_by' => $voidedBy?->id,
        ]);

        // Anular todos los cupones asociados
        $this->coupons()->update([
            'is_voided' => true,
            'voided_at' => now(),
            'voided_reason' => $reason,
            'voided_by' => $voidedBy?->id,
        ]);
    }

    public function getCouponNumbers(): array
    {
        return range($this->coupon_start_number, $this->coupon_end_number);
    }
}
```

### 3.6 Coupon

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sweepstake_id',
        'redemption_id',
        'user_id',
        'coupon_number',
        'is_voided',
        'voided_at',
        'voided_reason',
        'voided_by',
        'is_used',
        'used_at',
        'used_by',
    ];

    protected $casts = [
        'coupon_number' => 'integer',
        'is_voided' => 'boolean',
        'voided_at' => 'datetime',
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    // Relaciones
    public function sweepstake(): BelongsTo
    {
        return $this->belongsTo(Sweepstake::class);
    }

    public function redemption(): BelongsTo
    {
        return $this->belongsTo(CouponRedemption::class, 'redemption_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    // Scopes
    public function scopeValid(Builder $query): Builder
    {
        return $query->where('is_voided', false)
            ->whereNull('deleted_at');
    }

    public function scopeVoided(Builder $query): Builder
    {
        return $query->where('is_voided', true);
    }

    public function scopeUnused(Builder $query): Builder
    {
        return $query->where('is_used', false);
    }

    public function scopeUsed(Builder $query): Builder
    {
        return $query->where('is_used', true);
    }

    // Métodos de negocio
    public function isValid(): bool
    {
        return !$this->is_voided && $this->deleted_at === null;
    }

    public function canParticipate(): bool
    {
        return $this->isValid() && !$this->is_used;
    }

    public function markAsUsed(?User $usedBy = null): void
    {
        $this->update([
            'is_used' => true,
            'used_at' => now(),
            'used_by' => $usedBy?->id,
        ]);
    }

    public function markAsVoided(string $reason, ?User $voidedBy = null): void
    {
        $this->update([
            'is_voided' => true,
            'voided_at' => now(),
            'voided_reason' => $reason,
            'voided_by' => $voidedBy?->id,
        ]);
    }

    public function getDisplayNumber(): string
    {
        return sprintf('%s-%04d', $this->sweepstake->slug, $this->coupon_number);
    }
}
```

### 3.7 User (extensión)

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;

// NOTA: Asumiendo que el proyecto usa Jetstream/Fortify
class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;

    // ... código existente ...

    // Nuevas relaciones
    public function couponRedemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class);
    }

    public function validCoupons(): HasMany
    {
        return $this->hasMany(Coupon::class)
            ->where('is_voided', false)
            ->whereNull('deleted_at');
    }

    // Métodos auxiliares
    public function getCouponsCountInSweepstake(Sweepstake $sweepstake): int
    {
        return $this->couponRedemptions()
            ->where('sweepstake_id', $sweepstake->id)
            ->where('is_voided', false)
            ->sum('coupon_count');
    }
}
```

---

## 4. Flujo de Cobro

### 4.1 Diseño del Flujo Completo

```
[Usuario]
   ↓
[Accede a QR/Link]
   ↓
[GET /redemption/{code}] - Validar link y mostrar formulario
   ↓
[Usuario completa datos (email/teléfono)]
   ↓
[POST /redemption/{code}/redeem] - Procesar cobro
   ↓
[Validación]
   ├─ Link existe y está activo
   ├─ Sorteo existe y está disponible (fechas, publicado)
   ├─ Link está dentro de vigencia
   ├─ Link no alcanzó límite de redenciones
   ├─ Sorteo tiene cupos disponibles (si tiene máximo)
   ├─ Usuario no excedió límite por usuario (si tiene máximo)
   ↓
[Transacción DB]
   ├─ BEGIN TRANSACTION
   ├─ LOCK sweepstake (lockForUpdate)
   ├─ LEER last_coupon_number
   ├─ VALIDAR límite máximo de sorteo
   ├─ CREAR CouponRedemption
   ├─ GENERAR N cupones con números correlativos
   ├─ ACTUALIZAR last_coupon_number
   ├─ INCREMENTAR redemption_count en link
   ├─ COMMIT
   ↓
[Respuesta exitosa]
   ├─ Mostrar resumen al usuario
   ├─ Números de cupones generados
   ├─ Detalle del sorteo
   ↓
[Fin]
```

### 4.2 Estrategia de Concurrencia

**Implementación recomendada:**

```php
<?php

namespace App\Services;

use App\Exceptions\RedemptionException;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\RedemptionLink;
use App\Models\Sweepstake;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RedemptionService
{
    /**
     * Procesa un cobro de cupones desde un link/QR
     */
    public function redeem(
        string $linkCode,
        string $userEmail,
        ?string $userPhone = null,
        ?string $userName = null,
        ?User $authenticatedUser = null,
        array $metadata = []
    ): CouponRedemption {
        // Paso 1: Validar link y sorteo (fuera de transacción, para locks más cortos)
        $link = RedemptionLink::with(['sweepstake', 'redemptionSource'])
            ->where('code', $linkCode)
            ->firstOrFail();

        if (!$link->isAvailable()) {
            throw new RedemptionException('El link de canje no está disponible');
        }

        $sweepstake = $link->sweepstake;

        if (!$sweepstake->isAvailable()) {
            throw new RedemptionException('El sorteo no está disponible');
        }

        // Obtener o crear usuario
        $user = $authenticatedUser ?? $this->getOrCreateUser($userEmail, $userPhone, $userName);

        // Paso 2: Validar límite por usuario (fuera de transacción)
        if ($sweepstake->hasUserReachedLimit($user, $link->coupon_count)) {
            throw new RedemptionException('Has alcanzado el límite de participaciones en este sorteo');
        }

        // Paso 3: Transacción atómica con lock
        return DB::transaction(function () use ($link, $sweepstake, $user, $metadata) {
            // Lock explícito para prevenir race conditions
            $lockedSweepstake = Sweepstake::lockForUpdate()->find($sweepstake->id);

            // Validar límite máximo dentro de transacción (porque cambió desde paso 1)
            if (!$lockedSweepstake->hasAvailableSlots($link->coupon_count)) {
                throw new RedemptionException('El sorteo no tiene cupos disponibles');
            }

            // Calcular rango de números
            $startNumber = $lockedSweepstake->last_coupon_number + 1;
            $endNumber = $startNumber + $link->coupon_count - 1;

            // Crear registro de redención
            $redemption = CouponRedemption::create([
                'sweepstake_id' => $lockedSweepstake->id,
                'redemption_link_id' => $link->id,
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_phone' => $user->phone ?? null,
                'user_name' => $user->name ?? null,
                'coupon_count' => $link->coupon_count,
                'coupon_start_number' => $startNumber,
                'coupon_end_number' => $endNumber,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'redemption_channel' => $metadata['channel'] ?? 'web',
                'device_info' => $metadata['device_info'] ?? null,
            ]);

            // Crear cupones masivamente
            $coupons = collect();
            for ($i = $startNumber; $i <= $endNumber; $i++) {
                $coupons->push([
                    'sweepstake_id' => $lockedSweepstake->id,
                    'redemption_id' => $redemption->id,
                    'user_id' => $user->id,
                    'coupon_number' => $i,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            Coupon::insert($coupons->toArray());

            // Actualizar contador del sorteo
            $lockedSweepstake->update([
                'last_coupon_number' => $endNumber,
            ]);

            // Incrementar contador del link
            $link->incrementRedemptionCount();

            Log::info('Coupon redemption completed', [
                'redemption_id' => $redemption->id,
                'link_code' => $link->code,
                'user_id' => $user->id,
                'coupon_count' => $link->coupon_count,
                'numbers' => [$startNumber, $endNumber],
            ]);

            return $redemption;
        });
    }

    /**
     * Obtiene o crea un usuario basado en email/teléfono
     */
    protected function getOrCreateUser(
        string $email,
        ?string $phone,
        ?string $name
    ): User {
        $user = User::where('email', $email)->first();

        if ($user) {
            // Actualizar datos si están vacíos
            if (empty($user->phone) && $phone) {
                $user->update(['phone' => $phone]);
            }
            if (empty($user->name) && $name) {
                $user->update(['name' => $name]);
            }
            return $user;
        }

        // Crear nuevo usuario
        return User::create([
            'email' => $email,
            'phone' => $phone,
            'name' => $name ?? explode('@', $email)[0],
            'password' => bcrypt(str()->random(16)), // Password temporal
        ]);
    }

    /**
     * Reversa un cobro (anula cupones sin reutilizar números)
     */
    public function voidRedemption(
        int $redemptionId,
        string $reason,
        User $voidedBy
    ): void {
        DB::transaction(function () use ($redemptionId, $reason, $voidedBy) {
            $redemption = CouponRedemption::findOrFail($redemptionId);

            if ($redemption->is_voided) {
                throw new RedemptionException('Esta redención ya fue anulada');
            }

            $redemption->void($reason, $voidedBy);

            Log::warning('Coupon redemption voided', [
                'redemption_id' => $redemptionId,
                'reason' => $reason,
                'voided_by' => $voidedBy->id,
            ]);
        });
    }
}
```

### 4.3 Manejo de Concurrencia: Explicación Detallada

**Problema:** Si 2 usuarios cobran simultáneamente el mismo sorteo, ambos podrían leer el mismo `last_coupon_number` y generar cupones duplicados.

**Solución implementada:**

1. **`DB::transaction()`**: Garantiza que todas las operaciones sean atómicas. Si algo falla, todo se rollback.

2. **`lockForUpdate()`**: Bloquea la fila del sweepstake para que ninguna otra transacción pueda leer/escribir hasta que esta termine.

3. **Validación dentro de transacción**: Re-validar el límite máximo porque podría haber cambiado entre el check inicial y el lock.

4. **Índice único en DB**: `(sweepstake_id, coupon_number)` como safety net. Si hay un bug en el código, MySQL rechazará el insert duplicado.

**Por qué no usar cache locks:**
- Cache locks se pierden si el servidor se reinicia
- Difícil de debugear en producción
- No escalable bien en múltiples servidores
- Lock de fila de MySQL es más simple y confiable

**Por qué no usar `LOCK TABLES`:**
- Deadlocks complejos si múltiples transacciones esperan diferentes tablas
- Bloquea toda la tabla, no solo el sorteo específico
- Menos performante en alta concurrencia

**Recomendación adicional:**
- Para sorteos con muy alta concurrencia, considerar colas (jobs) para procesar cobros de forma secuencial.
- Pero para la mayoría de casos, `lockForUpdate()` es suficiente y más simple.

---

## 5. Panel Admin con Filament

### 5.1 Resources Propuestos

```
Filament/
├── Resources/
│   ├── SiteResource.php
│   ├── SweepstakeResource.php
│   ├── RedemptionLinkResource.php
│   ├── CouponRedemptionResource.php
│   └── CouponResource.php
```

### 5.2 SiteResource

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SiteResource\Pages;
use App\Models\Site;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SiteResource extends Resource
{
    protected static ?string $model = Site::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Sorteos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Sitio')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Activo'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Activo'),
                Tables\Columns\TextColumn::make('sweepstakes_count')
                    ->label('Sorteos')
                    ->counts('sweepstakes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->label('Solo activos'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            // RelationManager de sweepstakes
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSites::route('/'),
            'create' => Pages\CreateSite::route('/create'),
            'edit' => Pages\EditSite::route('/{record}/edit'),
            'view' => Pages\ViewSite::route('/{record}'),
        ];
    }
}
```

### 5.3 SweepstakeResource (Complejo con widgets y relation managers)

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SweepstakeResource\Pages;
use App\Filament\Resources\SweepstakeResource\RelationManagers;
use App\Models\Sweepstake;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SweepstakeResource extends Resource
{
    protected static ?string $model = Sweepstake::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';
    protected static ?string $navigationGroup = 'Sorteos';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Sorteo')
                    ->schema([
                        Forms\Components\Select::make('site_id')
                            ->relationship('site', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Usado para identificar el sorteo en los números de cupón'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3),
                        Forms\Components\Textarea::make('prize_description')
                            ->label('Descripción del premio')
                            ->rows(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Fechas')
                    ->schema([
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->required()
                            ->native(false),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->required()
                            ->native(false),
                        Forms\Components\DateTimePicker::make('draw_at')
                            ->label('Fecha del sorteo')
                            ->native(false)
                            ->helperText('Fecha prevista para realizar el sorteo'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Límites')
                    ->schema([
                        Forms\Components\TextInput::make('max_coupons')
                            ->numeric()
                            ->minValue(1)
                            ->label('Máximo de cupones total')
                            ->helperText('Dejar vacío para sin límite'),
                        Forms\Components\TextInput::make('max_coupons_per_user')
                            ->numeric()
                            ->minValue(1)
                            ->label('Máximo por usuario')
                            ->helperText('Dejar vacío para sin límite'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Activo'),
                        Forms\Components\Toggle::make('is_published')
                            ->default(false)
                            ->label('Publicado (visible públicamente)'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Resultado del Sorteo')
                    ->schema([
                        Forms\Components\Textarea::make('draw_result')
                            ->rows(5)
                            ->label('Resultado')
                            ->helperText('Ganadores, observaciones, etc.'),
                    ])
                    ->visible(fn ($record) => $record && $record->draw_at?->isPast()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('site.name')
                    ->label('Sitio')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('starts_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_published')
                    ->boolean(),
                Tables\Columns\TextColumn::make('last_coupon_number')
                    ->label('Cupones emitidos')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_coupons')
                    ->label('Máximo')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('validCouponsCount')
                    ->label('Cupones válidos')
                    ->getStateUsing(fn ($record) => $record->getValidCouponsCount())
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('site')
                    ->relationship('site', 'name'),
                Tables\Filters\Filter::make('active')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->label('Solo activos'),
                Tables\Filters\Filter::make('published')
                    ->query(fn ($query) => $query->where('is_published', true))
                    ->label('Solo publicados'),
                Tables\Filters\Filter::make('available')
                    ->query(fn (Builder $query) => $query->available())
                    ->label('Disponibles (publicados y en fecha)'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RedemptionLinksRelationManager::class,
            RelationManagers\CouponsRelationManager::class,
            RelationManagers\RedemptionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSweepstakes::route('/'),
            'create' => Pages\CreateSweepstake::route('/create'),
            'view' => Pages\ViewSweepstake::route('/{record}'),
            'edit' => Pages\EditSweepstake::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\SweepstakeStatsWidget::class,
        ];
    }
}
```

### 5.4 ViewSweepstake Page (con widgets y tabla de cupones)

```php
<?php

namespace App\Filament\Resources\SweepstakeResource\Pages;

use App\Filament\Resources\SweepstakeResource;
use App\Models\Sweepstake;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;

class ViewSweepstake extends ViewRecord implements HasTable
{
    protected static string $resource = SweepstakeResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Información General')
                    ->schema([
                        Infolists\Components\TextEntry::make('site.name'),
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('slug'),
                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull(),
                        Infolists\Components\TextEntry::make('prize_description')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Fechas')
                    ->schema([
                        Infolists\Components\TextEntry::make('starts_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('expires_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('draw_at')
                            ->label('Fecha del sorteo')
                            ->dateTime()
                            ->placeholder('No definida'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Estadísticas')
                    ->schema([
                        Infolists\Components\TextEntry::make('last_coupon_number')
                            ->label('Cupones emitidos'),
                        Infolists\Components\TextEntry::make('validCouponsCount')
                            ->label('Cupones válidos')
                            ->getStateUsing(fn ($record) => $record->getValidCouponsCount()),
                        Infolists\Components\TextEntry::make('max_coupons')
                            ->label('Máximo permitido')
                            ->placeholder('Sin límite'),
                        Infolists\Components\TextEntry::make('availableCouponsCount')
                            ->label('Cupones disponibles')
                            ->getStateUsing(fn ($record) => $record->getAvailableCouponsCount()),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Estado')
                    ->schema([
                        Infolists\Components\IconEntry::make('is_active')
                            ->boolean(),
                        Infolists\Components\IconEntry::make('is_published')
                            ->boolean(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->relationship('couponRedemptions')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user_phone')
                    ->label('Teléfono')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('coupon_count')
                    ->label('Cant.')
                    ->sortable(),
                Tables\Columns\TextColumn::make('coupon_start_number')
                    ->label('Desde #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('coupon_end_number')
                    ->label('Hasta #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('redemptionLink.title')
                    ->label('Origen')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_voided')
                    ->boolean()
                    ->label('Anulado'),
            ])
            ->filters([
                Tables\Filters\Filter::make('valid')
                    ->query(fn ($query) => $query->where('is_voided', false))
                    ->label('Solo válidos'),
                Tables\Filters\Filter::make('voided')
                    ->query(fn ($query) => $query->where('is_voided', true))
                    ->label('Solo anulados'),
                Tables\Filters\SelectFilter::make('redemption_source')
                    ->relationship('redemptionLink.redemptionSource', 'name')
                    ->label('Tipo de origen'),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('view_coupons')
                    ->label('Ver cupones')
                    ->icon('heroicon-o-ticket')
                    ->url(fn ($record) => route('filament.admin.resources.coupons.index', [
                        'tableFilters[redemption_id][value]' => $record->id,
                    ])),
                Tables\Actions\Action::make('void')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => !$record->is_voided)
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Motivo')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (array $data, $record) {
                        $record->void($data['reason'], auth()->user());
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\ExportBulkAction::make('export_csv')
                        ->label('Exportar CSV')
                        ->exporter(\App\Filament\Exports\RedemptionExport::class),
                ]),
            ])
            ->headerActions([
                Tables\Actions\ExportAction::make('export_csv')
                    ->label('Exportar CSV')
                    ->exporter(\App\Filament\Exports\RedemptionExport::class),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('export_all_coupons')
                ->label('Exportar cupones CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function (Sweepstake $record) {
                    return \App\Filament\Exports\SweepstakeCouponsExport::forSweepstake($record->id)->download();
                }),
        ];
    }
}
```

### 5.5 SweepstakeStatsWidget

```php
<?php

namespace App\Filament\Resources\SweepstakeResource\Widgets;

use App\Models\Sweepstake;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SweepstakeStatsWidget extends BaseWidget
{
    public ?Sweepstake $record = null;

    protected function getStats(): array
    {
        return [
            Stat::make('Cupones Emitidos', $this->record->getEmittedCouponsCount())
                ->description('Total de cupones generados')
                ->descriptionIcon('heroicon-m-ticket')
                ->color('success'),

            Stat::make('Cupones Válidos', $this->record->getValidCouponsCount())
                ->description('Participaciones activas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make('Cupones Disponibles', $this->record->getAvailableCouponsCount() === PHP_INT_MAX ? '∞' : $this->record->getAvailableCouponsCount())
                ->description($this->record->max_coupons ? 'Máximo: ' . $this->record->max_coupons : 'Sin límite')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($this->record->getAvailableCouponsCount() > 0 ? 'warning' : 'danger'),

            Stat::make('Redenciones', $this->record->couponRedemptions()->where('is_voided', false)->count())
                ->description('Usuarios que canjearon')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
        ];
    }
}
```

### 5.6 RedemptionLinkResource

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RedemptionLinkResource\Pages;
use App\Models\RedemptionLink;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class RedemptionLinkResource extends Resource
{
    protected static ?string $model = RedemptionLink::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';
    protected static ?string $navigationGroup = 'Sorteos';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Configuración del Link/QR')
                    ->schema([
                        Forms\Components\Select::make('sweepstake_id')
                            ->relationship('sweepstake', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live(),
                        Forms\Components\Select::make('redemption_source_id')
                            ->relationship('redemptionSource', 'name')
                            ->required()
                            ->default(function () {
                                return \App\Models\RedemptionSource::where('code', 'link')->first()?->id;
                            })
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->default(fn () => \Illuminate\Support\Str::random(12))
                            ->helperText('Código único del link. Se genera automáticamente si está vacío.'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Título visible para el usuario (ej: "Pack Fidelidad 10 Cupones")'),
                        Forms\Components\Textarea::make('description')
                            ->rows(2),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Configuración del Pack')
                    ->schema([
                        Forms\Components\TextInput::make('coupon_count')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->label('Cantidad de cupones')
                            ->helperText('Cuántos cupones genera este pack al canjear'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Vigencia del Link')
                    ->schema([
                        Forms\Components\DateTimePicker::make('valid_from')
                            ->label('Válido desde')
                            ->native(false),
                        Forms\Components\DateTimePicker::make('valid_until')
                            ->label('Válido hasta')
                            ->native(false),
                        Forms\Components\TextInput::make('max_redemptions')
                            ->numeric()
                            ->minValue(1)
                            ->label('Máximo de redenciones')
                            ->helperText('Cuántas veces se puede usar este link. Dejar vacío para sin límite'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Estado')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Activo'),
                    ])
                    ->columns(1),

                Forms\Components\Section::make('Enlace de Canje')
                    ->schema([
                        Forms\Components\TextEntry::make('redemption_url')
                            ->label('URL pública')
                            ->getStateUsing(fn ($record) => route('redemption.show', $record->code))
                            ->copyable()
                            ->url(fn ($record) => route('redemption.show', $record->code))
                            ->openUrlInNewTab(),
                    ])
                    ->visible(fn ($context) => $context === 'edit' || $context === 'view'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sweepstake.name')
                    ->label('Sorteo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('redemptionSource.name')
                    ->label('Tipo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('coupon_count')
                    ->label('Cupones')
                    ->sortable(),
                Tables\Columns\TextColumn::make('redemption_count')
                    ->label('Canjes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_redemptions')
                    ->label('Máximo')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('valid_from')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('valid_until')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('sweepstake')
                    ->relationship('sweepstake', 'name'),
                Tables\Filters\SelectFilter::make('redemption_source')
                    ->relationship('redemptionSource', 'name')
                    ->label('Tipo de origen'),
                Tables\Filters\Filter::make('active')
                    ->query(fn ($query) => $query->where('is_active', true))
                    ->label('Solo activos'),
                Tables\Filters\Filter::make('available')
                    ->query(fn ($query) => $query->available())
                    ->label('Disponibles'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRedemptionLinks::route('/'),
            'create' => Pages\CreateRedemptionLink::route('/create'),
            'view' => Pages\ViewRedemptionLink::route('/{record}'),
            'edit' => Pages\EditRedemptionLink::route('/{record}/edit'),
        ];
    }
}
```

### 5.7 CouponRedemptionResource y CouponResource

Estos resources serían similares a los anteriores. El `CouponResource` sería principalmente de lectura, ya que los cupones se crean automáticamente al cobrar.

---

## 6. Exportación CSV

### 6.1 Definición de Columnas del CSV

**Para listado de cupones/participaciones (por sorteo):**

| Columna | Descripción | Fuente |
|---------|-------------|--------|
| sorteo_id | ID del sorteo | `coupons.sweepstake_id` |
| sorteo_nombre | Nombre del sorteo | `sweepstakes.name` |
| sorteo_slug | Slug del sorteo | `sweepstakes.slug` |
| site_id | ID del sitio | `sweepstakes.site_id` |
| site_nombre | Nombre del sitio | `sites.name` |
| cupon_numero | Número del cupón | `coupons.coupon_number` |
| cupon_display | Display del cupón | Combinado: `{slug}-{numero:04d}` |
| usuario_id | ID del usuario | `coupons.user_id` |
| usuario_email | Email del usuario | `coupon_redemptions.user_email` |
| usuario_telefono | Teléfono del usuario | `coupon_redemptions.user_phone` |
| usuario_nombre | Nombre del usuario | `coupon_redemptions.user_name` |
| fecha_cobro | Fecha de generación | `coupon_redemptions.created_at` |
| origen_id | ID del link/QR | `coupon_redemptions.redemption_link_id` |
| origen_titulo | Título del origen | `redemption_links.title` |
| origen_tipo | Tipo de origen | `redemption_sources.name` |
| origen_descripcion | Descripción del pack | `redemption_links.description` |
| redencion_id | ID del evento de cobro | `coupons.redemption_id` |
| estado_anulado | Si el cupón está anulado | `coupons.is_voided` (Sí/No) |
| fecha_anulacion | Fecha de anulación (si aplica) | `coupons.voided_at` |
| motivo_anulacion | Motivo de anulación (si aplica) | `coupons.voided_reason` |

### 6.2 Nombre del Archivo

Formato: `cupones-sorteo-{slug}-{YYYY-MM-DD}.csv`

Ejemplo: `cupones-sorteo-navidad-2026-07-02.csv`

### 6.3 Encoding Recomendado

- **Encoding:** UTF-8 con BOM (Byte Order Mark)
- **Por qué:** Excel en Windows abre correctamente UTF-8 con BOM, pero confunde UTF-8 sin BOM

```php
\Symfony\Component\HttpFoundation\StreamedResponse::create()
    ->setCharset('UTF-8')
    ->headers->set('Content-Type', 'text/csv; charset=UTF-8');
// Agregar BOM al inicio del stream
echo "\xEF\xBB\xBF";
```

### 6.4 Consideraciones para Grandes Volúmenes

**Para exportar miles/millones de registros:**

1. **Usar chunking en lugar de cargar todo en memoria:**
```php
Coupon::where('sweepstake_id', $sweepstakeId)
    ->where('is_voided', false)
    ->whereNull('deleted_at')
    ->with(['sweepstake.site', 'redemption.redemptionLink.redemptionSource', 'user'])
    ->chunk(1000, function ($coupons) use ($handle) {
        foreach ($coupons as $coupon) {
            fputcsv($handle, $this->mapCouponToRow($coupon));
        }
    });
```

2. **Usar StreamedResponse de Laravel para respuesta HTTP directa:**
```php
return new StreamedResponse(function () use ($sweepstakeId) {
    $handle = fopen('php://output', 'w');
    // Escribir cabeceras
    fputcsv($handle, $headers);
    // Chunk y escribir rows
    fclose($handle);
});
```

3. **Timeouts:**
   - Aumentar `max_execution_time` en PHP si es necesario
   - Usar queue job para exportaciones muy grandes (>100k registros)
   - Notificar al usuario cuando el archivo esté listo

### 6.5 Implementación Recomendada

**Opción recomendada:** Filament ExportAction con exportador custom

**Por qué:**
- Integración nativa con Filament
- Soporta filtros activos de la tabla
- Fácil de reutilizar
- UI consistente

**Implementación:**

```php
<?php

namespace App\Filament\Exports;

use App\Models\Coupon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\Exportable;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SweepstakeCouponsExport implements FromQuery, WithHeadings, WithMapping, WithChunkReading
{
    use Exportable;

    protected int $sweepstakeId;

    public function __construct(int $sweepstakeId)
    {
        $this->sweepstakeId = $sweepstakeId;
    }

    public static function forSweepstake(int $sweepstakeId): self
    {
        return new self($sweepstakeId);
    }

    public function query()
    {
        return Coupon::query()
            ->where('sweepstake_id', $this->sweepstakeId)
            ->where('is_voided', false)
            ->whereNull('deleted_at')
            ->with([
                'sweepstake.site',
                'redemption.redemptionLink.redemptionSource',
                'user',
            ])
            ->orderBy('coupon_number');
    }

    public function headings(): array
    {
        return [
            'sorteo_id',
            'sorteo_nombre',
            'sorteo_slug',
            'site_id',
            'site_nombre',
            'cupon_numero',
            'cupon_display',
            'usuario_id',
            'usuario_email',
            'usuario_telefono',
            'usuario_nombre',
            'fecha_cobro',
            'origen_id',
            'origen_titulo',
            'origen_tipo',
            'origen_descripcion',
            'redencion_id',
            'estado_anulado',
            'fecha_anulacion',
            'motivo_anulacion',
        ];
    }

    public function map($coupon): array
    {
        $redemption = $coupon->redemption;
        $link = $redemption->redemptionLink;
        $source = $link->redemptionSource;
        $sweepstake = $coupon->sweepstake;
        $site = $sweepstake->site;

        return [
            $sweepstake->id,
            $sweepstake->name,
            $sweepstake->slug,
            $site->id,
            $site->name,
            $coupon->coupon_number,
            $coupon->getDisplayNumber(),
            $coupon->user_id,
            $redemption->user_email,
            $redemption->user_phone,
            $redemption->user_name,
            $redemption->created_at->format('Y-m-d H:i:s'),
            $link->id,
            $link->title,
            $source->name,
            $link->description,
            $redemption->id,
            $coupon->is_voided ? 'Sí' : 'No',
            $coupon->voided_at?->format('Y-m-d H:i:s'),
            $coupon->voided_reason,
        ];
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function fileName(): string
    {
        $sweepstake = Sweepstake::find($this->sweepstakeId);
        return sprintf(
            'cupones-sorteo-%s-%s.csv',
            $sweepstake->slug,
            now()->format('Y-m-d')
        );
    }
}
```

**Exportación desde Filament:**

```php
// En ViewSweepstake.php
use Actions\Action;
use App\Filament\Exports\SweepstakeCouponsExport;

Action::make('export_coupons')
    ->label('Exportar cupones CSV')
    ->icon('heroicon-o-document-arrow-down')
    ->action(function (Sweepstake $record) {
        return SweepstakeCouponsExport::forSweepstake($record->id)
            ->download($record->slug . '-cupones.csv');
    });
```

---

## 7. Arquitectura y Código

### 7.1 Estructura de Carpetas Propuesta

```
app/
├── Actions/
│   ├── RedeemCouponAction.php
│   └── VoidRedemptionAction.php
├── Enums/
│   ├── RedemptionChannel.php
│   ├── CouponStatus.php
│   └── SorteoStatus.php
├── Exceptions/
│   └── RedemptionException.php
├── Filament/
│   ├── Exports/
│   │   ├── SweepstakeCouponsExport.php
│   │   └── RedemptionExport.php
│   ├── Resources/
│   │   ├── SiteResource/
│   │   │   ├── Pages/
│   │   │   └── RelationManagers/
│   │   ├── SweepstakeResource/
│   │   │   ├── Pages/
│   │   │   │   ├── ListSweepstakes.php
│   │   │   │   ├── CreateSweepstake.php
│   │   │   │   ├── EditSweepstake.php
│   │   │   │   └── ViewSweepstake.php
│   │   │   ├── RelationManagers/
│   │   │   │   ├── CouponsRelationManager.php
│   │   │   │   ├── RedemptionsRelationManager.php
│   │   │   │   └── RedemptionLinksRelationManager.php
│   │   │   └── Widgets/
│   │   │       └── SweepstakeStatsWidget.php
│   │   ├── RedemptionLinkResource/
│   │   ├── CouponRedemptionResource/
│   │   └── CouponResource/
│   └── Widgets/
│       └── CouponStatsWidget.php
├── Http/
│   ├── Controllers/
│   │   └── RedemptionController.php
│   ├── Requests/
│   │   └── RedeemCouponRequest.php
│   └── Middleware/
│       └── CheckSorteoAvailability.php
├── Jobs/
│   └── ProcessCouponRedemption.php
├── Models/
│   ├── Site.php
│   ├── Sweepstake.php
│   ├── RedemptionSource.php
│   ├── RedemptionLink.php
│   ├── CouponRedemption.php
│   └── Coupon.php
├── Observers/
│   ├── CouponObserver.php
│   └── RedemptionObserver.php
├── Policies/
│   ├── SitePolicy.php
│   ├── SweepstakePolicy.php
│   ├── RedemptionLinkPolicy.php
│   ├── CouponRedemptionPolicy.php
│   └── CouponPolicy.php
├── Services/
│   └── RedemptionService.php
└── Events/
    ├── CouponRedeemed.php
    ├── RedemptionVoided.php
    └── SweepstakeLimitReached.php
```

### 7.2 Responsabilidades por Capa

| Capa | Responsabilidad | Ejemplo |
|------|-----------------|---------|
| **Models** | Relaciones, scopes, casts, lógica de negocio simple | `Sweepstake::hasAvailableSlots()` |
| **Services** | Lógica de negocio compleja, transacciones, coordinación | `RedemptionService::redeem()` |
| **Actions** | Operaciones unitarias reutilizables | `RedeemCouponAction::execute()` |
| **Observers** | Side effects automáticos | `CouponObserver::created()` |
| **Jobs** | Operaciones async o de larga duración | `ProcessCouponRedemption` |
| **Policies** | Autorización | `SweepstakePolicy::void()` |
| **Controllers** | HTTP request/response | `RedemptionController::redeem()` |
| **Requests** | Validación | `RedeemCouponRequest` |
| **Exports** | Exportación de datos | `SweepstakeCouponsExport` |

### 7.3 Ejemplo de Implementación: Controller

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\RedeemCouponRequest;
use App\Models\RedemptionLink;
use App\Services\RedemptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RedemptionController extends Controller
{
    public function __construct(
        private RedemptionService $redemptionService
    ) {}

    /**
     * Muestra el formulario de canje para un link/QR
     */
    public function show(string $code)
    {
        try {
            $link = RedemptionLink::where('code', $code)
                ->with(['sweepstake', 'redemptionSource'])
                ->firstOrFail();

            // Validar disponibilidad antes de mostrar
            if (!$link->isAvailable()) {
                return inertia('Redemption/Unavailable', [
                    'reason' => $this->getUnavailabilityReason($link),
                ]);
            }

            return inertia('Redemption/Show', [
                'link' => [
                    'code' => $link->code,
                    'title' => $link->title,
                    'description' => $link->description,
                    'coupon_count' => $link->coupon_count,
                ],
                'sweepstake' => [
                    'name' => $link->sweepstake->name,
                    'description' => $link->sweepstake->description,
                    'prize' => $link->sweepstake->prize_description,
                    'expires_at' => $link->sweepstake->expires_at->format('d/m/Y H:i'),
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            abort(404, 'Link de canje no encontrado');
        }
    }

    /**
     * Procesa el cobro de cupones
     */
    public function redeem(string $code, RedeemCouponRequest $request)
    {
        try {
            $redemption = $this->redemptionService->redeem(
                linkCode: $code,
                userEmail: $request->validated('email'),
                userPhone: $request->validated('phone'),
                userName: $request->validated('name'),
                authenticatedUser: auth()->user(),
                metadata: [
                    'channel' => $request->validated('channel') ?? 'web',
                    'device_info' => $this->getDeviceInfo($request),
                ]
            );

            return inertia('Redemption/Success', [
                'redemption' => [
                    'coupon_count' => $redemption->coupon_count,
                    'coupon_numbers' => $redemption->getCouponNumbers(),
                    'sweepstake_name' => $redemption->sweepstake->name,
                    'sweepstake_slug' => $redemption->sweepstake->slug,
                ],
            ]);

        } catch (\App\Exceptions\RedemptionException $e) {
            Log::warning('Coupon redemption failed', [
                'code' => $code,
                'error' => $e->getMessage(),
                'user' => $request->validated('email'),
            ]);

            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            Log::error('Unexpected error during redemption', [
                'code' => $code,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Ocurrió un error inesperado. Por favor intenta nuevamente.');
        }
    }

    protected function getUnavailabilityReason(RedemptionLink $link): string
    {
        if (!$link->is_active) {
            return 'Este link ha sido desactivado';
        }

        if ($link->valid_from?->isFuture()) {
            return 'Este link estará disponible desde ' . $link->valid_from->format('d/m/Y H:i');
        }

        if ($link->valid_until?->isPast()) {
            return 'Este link ha expirado';
        }

        if ($link->max_redemptions && $link->redemption_count >= $link->max_redemptions) {
            return 'Este link ha alcanzado su límite de redenciones';
        }

        if (!$link->sweepstake->is_available()) {
            if ($link->sweepstake->starts_at->isFuture()) {
                return 'El sorteo aún no ha comenzado';
            }
            if ($link->sweepstake->expires_at->isPast()) {
                return 'El sorteo ha finalizado';
            }
            if (!$link->sweepstake->is_published) {
                return 'El sorteo no está disponible';
            }
            if (!$link->sweepstake->is_active) {
                return 'El sorteo ha sido desactivado';
            }
            if ($link->sweepstake->hasAvailableSlots()) {
                return 'El sorteo no tiene más cupos disponibles';
            }
        }

        return 'No disponible';
    }

    protected function getDeviceInfo(Request $request): array
    {
        return [
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'is_mobile' => $request->header('User-Agent') && preg_match('/mobile/i', $request->header('User-Agent')),
        ];
    }
}
```

### 7.4 Ejemplo de Implementación: Policy

```php
<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Sweepstake;
use Illuminate\Auth\Access\Response;

class SweepstakePolicy
{
    /**
     * Determina si el usuario puede ver cualquier sorteo
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determina si el usuario puede ver un sorteo específico
     */
    public function view(User $user, Sweepstake $sweepstake): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determina si el usuario puede crear sorteos
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determina si el usuario puede actualizar un sorteo
     */
    public function update(User $user, Sweepstake $sweepstake): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determina si el usuario puede eliminar un sorteo
     */
    public function delete(User $user, Sweepstake $sweepstake): bool
    {
        if (!$user->hasRole('admin')) {
            return false;
        }

        // No permitir eliminar si hay cupones válidos
        if ($sweepstake->getValidCouponsCount() > 0) {
            return false;
        }

        return true;
    }

    /**
     * Determina si el usuario puede anular redenciones
     */
    public function voidRedemption(User $user, Sweepstake $sweepstake): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determina si el usuario puede exportar cupones
     */
    public function exportCoupons(User $user, Sweepstake $sweepstake): bool
    {
        return $user->hasAnyRole(['admin', 'manager']);
    }

    /**
     * Determina si el usuario puede registrar resultado del sorteo
     */
    public function registerDrawResult(User $user, Sweepstake $sweepstake): bool
    {
        return $user->hasRole('admin');
    }
}
```

---

## 8. Plan de Implementación

### Fase 1: Diseño de Schema (1-2 días)

**Tareas:**
1. Crear archivos de migración
2. Definir índices y foreign keys
3. Configurar enums (si se usan)
4. Revisar con el equipo

**Archivos a crear:**
- `database/migrations/2026_07_02_000001_create_sites_table.php`
- `database/migrations/2026_07_02_000002_create_sweepstakes_table.php`
- `database/migrations/2026_07_02_000003_create_redemption_sources_table.php`
- `database/migrations/2026_07_02_000004_create_redemption_links_table.php`
- `database/migrations/2026_07_02_000005_create_coupon_redemptions_table.php`
- `database/migrations/2026_07_02_000006_create_coupons_table.php`

**Riesgos:**
- Cambios en el schema durante desarrollo
- Migraciones conflictivas con tablas existentes

**Mitigación:**
- Usar migraciones con rollback
- Testing temprano con db seeds

---

### Fase 2: Modelos y Servicios (2-3 días)

**Tareas:**
1. Crear todos los models Eloquent
2. Definir relaciones (hasMany, belongsTo)
3. Crear scopes útiles
4. Implementar métodos de negocio en models
5. Crear `RedemptionService`
6. Crear Actions
7. Crear Policies

**Archivos a crear:**
- `app/Models/Site.php`
- `app/Models/Sweepstake.php`
- `app/Models/RedemptionSource.php`
- `app/Models/RedemptionLink.php`
- `app/Models/CouponRedemption.php`
- `app/Models/Coupon.php`
- `app/Services/RedemptionService.php`
- `app/Actions/RedeemCouponAction.php`
- `app/Actions/VoidRedemptionAction.php`
- `app/Policies/*`

**Riesgos:**
- Lógica de negocio compleja en servicios
- Relaciones incorrectas

**Mitigación:**
- Tests unitarios por cada método
- Code review de modelos

---

### Fase 3: Flujo de Cobro (2-3 días)

**Tareas:**
1. Crear `RedemptionController`
2. Crear `RedeemCouponRequest`
3. Implementar endpoints HTTP
4. Crear páginas Inertia de frontend
5. Implementar lógica de concurrencia
6. Testing de concurrencia

**Archivos a crear:**
- `app/Http/Controllers/RedemptionController.php`
- `app/Http/Requests/RedeemCouponRequest.php`
- `app/Exceptions/RedemptionException.php`
- `routes/web.php` (añadir rutas de redención)
- `resources/js/Pages/Redemption/Show.jsx`
- `resources/js/Pages/Redemption/Success.jsx`
- `resources/js/Pages/Redemption/Unavailable.jsx`

**Riesgos:**
- Race conditions en alta concurrencia
- Edge cases en validaciones
- Experiencia de usuario pobre

**Mitigación:**
- Tests de concurrencia con múltiples procesos
- Testing manual con diferentes escenarios
- UX testing con usuarios reales

---

### Fase 4: Admin Filament (3-4 días)

**Tareas:**
1. Crear todos los Resources
2. Implementar Relation Managers
3. Crear Widgets
4. Implementar filtros y búsquedas
5. Crear acciones de tabla y bulk actions
6. Implementar vistas de detalle
7. UX testing en admin

**Archivos a crear:**
- `app/Filament/Resources/*`
- `app/Filament/Exports/*`
- `app/Filament/Widgets/*`

**Riesgos:**
- UX confusa en admin
- Performance en tablas grandes
- Permisos mal configurados

**Mitigación:**
- Iteraciones con feedback del equipo
- Testing con datos de producción mockeados
- Revisión de policies

---

### Fase 5: Exportación (1-2 días)

**Tareas:**
1. Implementar exportadores CSV
2. Configurar encoding UTF-8 con BOM
3. Testing con grandes volúmenes
4. Documentar formato de exportación

**Archivos a crear:**
- `app/Filament/Exports/SweepstakeCouponsExport.php`
- `app/Filament/Exports/RedemptionExport.php`

**Riesgos:**
- Encoding incorrecto
- Timeout en exportaciones grandes
- Memoria agotada

**Mitigación:**
- Testing con chunking
- Implementar queue jobs para exportaciones masivas
- Monitoreo de performance

---

### Fase 6: Testing y Hardening (3-4 días)

**Tareas:**
1. Escribir tests de integración
2. Testing de edge cases
3. Performance testing
4. Security testing
5. Load testing
6. Bug fixes

**Archivos a crear:**
- `tests/Feature/RedemptionTest.php`
- `tests/Feature/ConcurrentRedemptionTest.php`
- `tests/Feature/SweepstakeTest.php`
- `tests/Unit/RedemptionServiceTest.php`

**Riesgos:**
- Bugs críticos descubiertos tarde
- Performance issues en producción

**Mitigación:**
- Test coverage > 80%
- Staging environment con datos reales
- Plan de rollback

---

### Fase 7: Retiro de Promociones/Juegos Antiguos (1-2 días)

**Tareas:**
1. Backup de datos antiguos
2. Eliminar archivos de código antiguo
3. Eliminar migraciones antiguas
4. Limpiar tabla `migrations`
5. Documentar cambios

**Riesgos:**
- Pérdida de datos históricos
- Romper dependencias

**Mitigación:**
- Backup completo antes de eliminar
- Verificar que no hay código referenciando lo viejo
- Rollback planificado

---

### Cronograma Resumido

| Fase | Duración | Días |
|------|----------|------|
| 1. Schema | 1-2 días | 2 |
| 2. Modelos/Servicios | 2-3 días | 3 |
| 3. Flujo de Cobro | 2-3 días | 3 |
| 4. Admin Filament | 3-4 días | 4 |
| 5. Exportación | 1-2 días | 2 |
| 6. Testing | 3-4 días | 4 |
| 7. Retiro antiguo | 1-2 días | 2 |
| **Total** | **13-20 días** | **~20 días** |

---

## 9. Testing

### 9.1 Tests Concretos

#### Test 1: Creación de sorteo

```php
<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\Sweepstake;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateSweepstakeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_sweepstake(): void
    {
        $admin = $this->createUserWithRole('admin');
        $site = Site::factory()->create();

        $data = [
            'site_id' => $site->id,
            'name' => 'Sorteo de Navidad',
            'slug' => 'navidad-2026',
            'description' => 'Sorteo especial de fin de año',
            'starts_at' => Carbon::now()->addDays(1),
            'expires_at' => Carbon::now()->addDays(30),
            'max_coupons' => 1000,
            'max_coupons_per_user' => 10,
            'is_active' => true,
            'is_published' => true,
        ];

        $response = $this->actingAs($admin)
            ->post(route('filament.admin.resources.sweepstakes.store'), $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('sweepstakes', [
            'name' => 'Sorteo de Navidad',
            'slug' => 'navidad-2026',
            'max_coupons' => 1000,
        ]);

        $sweepstake = Sweepstake::where('slug', 'navidad-2026')->first();
        $this->assertEquals(0, $sweepstake->last_coupon_number);
    }

    public function test_sweepstake_slug_must_be_unique_per_site(): void
    {
        $admin = $this->createUserWithRole('admin');
        $site = Site::factory()->create();

        Sweepstake::factory()->create([
            'site_id' => $site->id,
            'slug' => 'test-sweepstake',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('filament.admin.resources.sweepstakes.store'), [
                'site_id' => $site->id,
                'name' => 'Test 2',
                'slug' => 'test-sweepstake', // Duplicate slug
                'starts_at' => Carbon::now()->addDays(1),
                'expires_at' => Carbon::now()->addDays(30),
            ]);

        $response->assertSessionHasErrors('slug');
    }
}
```

#### Test 2: Creación de link/QR pack

```php
<?php

namespace Tests\Feature;

use App\Models\RedemptionLink;
use App\Models\RedemptionSource;
use App\Models\Site;
use App\Models\Sweepstake;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateRedemptionLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_link_with_pack_of_10_coupons(): void
    {
        $admin = $this->createUserWithRole('admin');
        $site = Site::factory()->create();
        $sweepstake = Sweepstake::factory()->for($site)->create();
        $source = RedemptionSource::factory()->create(['code' => 'link']);

        $data = [
            'sweepstake_id' => $sweepstake->id,
            'redemption_source_id' => $source->id,
            'code' => 'pack-fidelidad-10',
            'title' => 'Pack Fidelidad - 10 Cupones',
            'description' => 'Obtén 10 participaciones por este pack',
            'coupon_count' => 10,
            'max_redemptions' => 100,
            'is_active' => true,
        ];

        $response = $this->actingAs($admin)
            ->post(route('filament.admin.resources.redemption-links.store'), $data);

        $response->assertRedirect();

        $this->assertDatabaseHas('redemption_links', [
            'code' => 'pack-fidelidad-10',
            'coupon_count' => 10,
            'max_redemptions' => 100,
        ]);

        $link = RedemptionLink::where('code', 'pack-fidelidad-10')->first();
        $this->assertEquals(0, $link->redemption_count);
    }
}
```

#### Test 3: Cobro de pack de 1

```php
<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\RedemptionLink;
use App\Models\RedemptionSource;
use App\Models\Site;
use App\Models\Sweepstake;
use App\Models\User;
use App\Services\RedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RedeemSingleCouponTest extends TestCase
{
    use RefreshDatabase;

    public function test_redeeming_single_coupon_creates_one_record(): void
    {
        $site = Site::factory()->create();
        $sweepstake = Sweepstake::factory()->for($site)->create([
            'max_coupons' => 100,
            'last_coupon_number' => 0,
        ]);
        $source = RedemptionSource::factory()->create(['code' => 'link']);
        $link = RedemptionLink::factory()
            ->for($sweepstake)
            ->for($source)
            ->create([
                'code' => 'test-link-1',
                'coupon_count' => 1,
            ]);

        $service = new RedemptionService();

        $redemption = $service->redeem(
            linkCode: 'test-link-1',
            userEmail: 'user@example.com',
        );

        $this->assertDatabaseHas('coupon_redemptions', [
            'id' => $redemption->id,
            'user_email' => 'user@example.com',
            'coupon_count' => 1,
            'coupon_start_number' => 1,
            'coupon_end_number' => 1,
        ]);

        $this->assertDatabaseHas('coupons', [
            'sweepstake_id' => $sweepstake->id,
            'redemption_id' => $redemption->id,
            'coupon_number' => 1,
            'is_voided' => false,
        ]);

        $sweepstake->refresh();
        $this->assertEquals(1, $sweepstake->last_coupon_number);

        $link->refresh();
        $this->assertEquals(1, $link->redemption_count);
    }
}
```

#### Test 4: Cobro de pack de 10

```php
<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Sweepstake;
use App\Services\RedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedeemPackOfTenTest extends TestCase
{
    use RefreshDatabase;

    public function test_redeeming_pack_of_10_creates_10_coupons_consecutive(): void
    {
        $sweepstake = $this->createSweepstakeWithLink([
            'coupon_count' => 10,
            'max_coupons' => 100,
        ]);

        $service = new RedemptionService();
        $redemption = $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user@example.com',
        );

        $this->assertEquals(10, $redemption->coupon_count);
        $this->assertEquals(1, $redemption->coupon_start_number);
        $this->assertEquals(10, $redemption->coupon_end_number);

        // Verificar 10 cupones creados
        $coupons = Coupon::where('redemption_id', $redemption->id)->get();
        $this->assertCount(10, $coupons);

        // Verificar números consecutivos
        $numbers = $coupons->pluck('coupon_number')->sort()->values();
        $this->assertEquals(range(1, 10), $numbers->toArray());

        // Verificar que el contador del sorteo se actualizó
        $sweepstake['model']->refresh();
        $this->assertEquals(10, $sweepstake['model']->last_coupon_number);
    }

    protected function createSweepstakeWithLink(array $linkConfig): array
    {
        $site = \App\Models\Site::factory()->create();
        $sweepstake = \App\Models\Sweepstake::factory()
            ->for($site)
            ->create($linkConfig);
        $source = \App\Models\RedemptionSource::factory()->create(['code' => 'link']);
        $link = \App\Models\RedemptionLink::factory()
            ->for($sweepstake)
            ->for($source)
            ->create(array_merge([
                'code' => 'test-pack-10',
                'title' => 'Pack 10',
            ], $linkConfig));

        return [
            'model' => $sweepstake,
            'link' => $link,
        ];
    }
}
```

#### Test 5: Numeración correlativa por sorteo

```php
<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Sweepstake;
use App\Services\RedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SequentialNumberingTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_redemptions_maintain_sequential_numbers(): void
    {
        $sweepstake = $this->createSweepstakeWithLink(['coupon_count' => 5, 'max_coupons' => 50]);
        $service = new RedemptionService();

        // Primer cobro: cupones 1-5
        $redemption1 = $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user1@example.com',
        );

        // Segundo cobro: cupones 6-10
        $redemption2 = $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user2@example.com',
        );

        // Tercer cobro: cupones 11-15
        $redemption3 = $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user3@example.com',
        );

        // Verificar rangos
        $this->assertEquals([1, 5], [$redemption1->coupon_start_number, $redemption1->coupon_end_number]);
        $this->assertEquals([6, 10], [$redemption2->coupon_start_number, $redemption2->coupon_end_number]);
        $this->assertEquals([11, 15], [$redemption3->coupon_start_number, $redemption3->coupon_end_number]);

        // Verificar que no hay gaps
        $allNumbers = Coupon::where('sweepstake_id', $sweepstake['model']->id)
            ->orderBy('coupon_number')
            ->pluck('coupon_number')
            ->toArray();

        $this->assertEquals(range(1, 15), $allNumbers);
    }

    public function test_two_different_sweepstakes_have_independent_numbering(): void
    {
        $sweepstake1 = $this->createSweepstakeWithLink(['coupon_count' => 3]);
        $sweepstake2 = $this->createSweepstakeWithLink(['coupon_count' => 5]);

        $service = new RedemptionService();

        // Cobrar en sorteo 1
        $redemption1 = $service->redeem(
            linkCode: $sweepstake1['link']->code,
            userEmail: 'user@example.com',
        );

        // Cobrar en sorteo 2
        $redemption2 = $service->redeem(
            linkCode: $sweepstake2['link']->code,
            userEmail: 'user@example.com',
        );

        // Ambos deben empezar desde 1
        $this->assertEquals([1, 3], [$redemption1->coupon_start_number, $redemption1->coupon_end_number]);
        $this->assertEquals([1, 5], [$redemption2->coupon_start_number, $redemption2->coupon_end_number]);
    }

    // ... helper method createSweepstakeWithLink() ...
}
```

#### Test 6: Concurrencia

```php
<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Sweepstake;
use App\Services\RedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ConcurrentRedemptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_concurrent_redemptions_do_not_duplicate_numbers(): void
    {
        $sweepstake = $this->createSweepstakeWithLink(['coupon_count' => 5, 'max_coupons' => 50]);
        $service = new RedemptionService();

        // Simular 5 usuarios cobrando simultáneamente
        $redemptions = [];
        $processes = 5;

        for ($i = 0; $i < $processes; $i++) {
            $redemptions[] = DB::transaction(function () use ($service, $sweepstake, $i) {
                return $service->redeem(
                    linkCode: $sweepstake['link']->code,
                    userEmail: "user{$i}@example.com",
                );
            });
        }

        // Verificar que no hay números duplicados
        $allNumbers = [];
        foreach ($redemptions as $redemption) {
            $allNumbers = array_merge($allNumbers, $redemption->getCouponNumbers());
        }

        $this->assertCount(count($allNumbers), array_unique($allNumbers), 'Hay números duplicados');

        // Verificar que los números son consecutivos
        sort($allNumbers);
        $this->assertEquals(range(1, 25), $allNumbers);

        // Verificar contador final del sorteo
        $sweepstake['model']->refresh();
        $this->assertEquals(25, $sweepstake['model']->last_coupon_number);
    }

    public function test_concurrent_redemptions_respect_max_coupons_limit(): void
    {
        $sweepstake = $this->createSweepstakeWithLink([
            'coupon_count' => 10,
            'max_coupons' => 25, // Solo espacio para 2.5 redemptions
        ]);

        $service = new RedemptionService();

        // Primera redención: 10 cupones (ok)
        $redemption1 = $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user1@example.com',
        );

        // Segunda redención: 10 cupones (ok, total 20)
        $redemption2 = $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user2@example.com',
        );

        // Tercera redención: debería fallar (solo quedan 5 espacios, pero pide 10)
        $this->expectException(\App\Exceptions\RedemptionException::class);
        $this->expectExceptionMessage('sin cupos disponibles');

        $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user3@example.com',
        );

        // Verificar que solo se crearon 20 cupones
        $this->assertEquals(20, Coupon::where('sweepstake_id', $sweepstake['model']->id)->count());
    }

    // ... helper method createSweepstakeWithLink() ...
}
```

#### Test 7: Límite máximo alcanzado

```php
<?php

namespace Tests\Feature;

use App\Exceptions\RedemptionException;
use App\Models\Coupon;
use App\Models\Sweepstake;
use App\Services\RedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SweepstakeLimitReachedTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_redeem_when_sweepstake_limit_reached(): void
    {
        $sweepstake = $this->createSweepstakeWithLink([
            'coupon_count' => 10,
            'max_coupons' => 10, // Exactamente 1 redención
        ]);

        $service = new RedemptionService();

        // Primera redención: ok
        $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user1@example.com',
        );

        // Segunda redención: debería fallar
        $this->expectException(RedemptionException::class);
        $this->expectExceptionMessage('sin cupos disponibles');

        $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user2@example.com',
        );
    }

    public function test_partial_redemption_when_approaching_limit(): void
    {
        $sweepstake = $this->createSweepstakeWithLink([
            'coupon_count' => 10,
            'max_coupons' => 25, // Espacio para 2.5 redemptions
        ]);

        $service = new RedemptionService();

        // Primera redención: 10 cupones (ok)
        $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user1@example.com',
        );

        // Segunda redención: 10 cupones (ok, total 20)
        $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user2@example.com',
        );

        // Tercera redención: falla (solo quedan 5 espacios)
        $this->expectException(RedemptionException::class);

        $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user3@example.com',
        );
    }

    // ... helper method createSweepstakeWithLink() ...
}
```

#### Test 8: Reversa sin reutilización de números

```php
<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Sweepstake;
use App\Services\RedemptionService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoidRedemptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_voiding_redemption_marks_coupons_as_voided(): void
    {
        $sweepstake = $this->createSweepstakeWithLink(['coupon_count' => 5]);
        $admin = User::factory()->create();

        $service = new RedemptionService();
        $redemption = $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user@example.com',
        );

        // Verificar cupones válidos antes de anular
        $validCoupons = Coupon::where('redemption_id', $redemption->id)->where('is_voided', false)->get();
        $this->assertCount(5, $validCoupons);

        // Anular redención
        $service->voidRedemption($redemption->id, 'Error en el cobro', $admin);

        // Verificar que la redención está anulada
        $redemption->refresh();
        $this->assertTrue($redemption->is_voided);
        $this->assertNotNull($redemption->voided_at);
        $this->assertEquals('Error en el cobro', $redemption->voided_reason);
        $this->assertEquals($admin->id, $redemption->voided_by);

        // Verificar que todos los cupones están anulados
        $voidedCoupons = Coupon::where('redemption_id', $redemption->id)->where('is_voided', true)->get();
        $this->assertCount(5, $voidedCoupons);

        // Verificar que los cupones anulados NO son válidos
        foreach ($voidedCoupons as $coupon) {
            $this->assertFalse($coupon->isValid());
            $this->assertFalse($coupon->canParticipate());
        }
    }

    public function test_voided_coupon_numbers_are_not_reused(): void
    {
        $sweepstake = $this->createSweepstakeWithLink(['coupon_count' => 5, 'max_coupons' => 20]);
        $admin = User::factory()->create();

        $service = new RedemptionService();

        // Primera redención: cupones 1-5
        $redemption1 = $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user1@example.com',
        );

        // Anular primera redención
        $service->voidRedemption($redemption1->id, 'Error', $admin);

        // Segunda redención: debería ser cupones 6-10, NO reusar 1-5
        $redemption2 = $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user2@example.com',
        );

        $this->assertEquals(6, $redemption2->coupon_start_number);
        $this->assertEquals(10, $redemption2->coupon_end_number);

        // Verificar que el contador del sorteo avanzó
        $sweepstake['model']->refresh();
        $this->assertEquals(10, $sweepstake['model']->last_coupon_number);
    }

    public function test_export_csv_excludes_voided_coupons(): void
    {
        $sweepstake = $this->createSweepstakeWithLink(['coupon_count' => 5]);
        $admin = User::factory()->create();

        $service = new RedemptionService();
        $redemption1 = $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user1@example.com',
        );

        $redemption2 = $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user2@example.com',
        );

        // Anular la segunda redención
        $service->voidRedemption($redemption2->id, 'Error', $admin);

        // Exportar CSV
        $export = new \App\Filament\Exports\SweepstakeCouponsExport($sweepstake['model']->id);
        $rows = $export->collection()->toArray();

        // Debería haber solo 5 cupones (los de la primera redención)
        $this->assertCount(5, $rows);

        // Verificar que no hay cupones anulados
        foreach ($rows as $row) {
            $this->assertEquals('No', $row['estado_anulado']);
        }
    }

    // ... helper method createSweepstakeWithLink() ...
}
```

#### Test 9: Exportación CSV

```php
<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Filament\Exports\SweepstakeCouponsExport;
use App\Services\RedemptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class CsvExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_includes_all_required_columns(): void
    {
        $sweepstake = $this->createSweepstakeWithLink(['coupon_count' => 3]);

        $service = new RedemptionService();
        $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'test@example.com',
            userPhone: '+1234567890',
            userName: 'Test User',
        );

        $export = new SweepstakeCouponsExport($sweepstake['model']->id);
        $rows = $export->collection()->toArray();

        $this->assertCount(3, $rows);

        $row = $rows[0];

        // Verificar columnas requeridas
        $requiredColumns = [
            'sorteo_id',
            'sorteo_nombre',
            'sorteo_slug',
            'site_id',
            'site_nombre',
            'cupon_numero',
            'cupon_display',
            'usuario_email',
            'usuario_telefono',
            'usuario_nombre',
            'fecha_cobro',
            'origen_id',
            'origen_titulo',
            'origen_tipo',
        ];

        foreach ($requiredColumns as $column) {
            $this->assertArrayHasKey($column, $row, "Falta columna: {$column}");
        }

        // Verificar valores
        $this->assertEquals('test@example.com', $row['usuario_email']);
        $this->assertEquals('+1234567890', $row['usuario_telefono']);
        $this->assertEquals('Test User', $row['usuario_nombre']);
        $this->assertEquals(1, $row['cupon_numero']);
    }

    public function test_export_excludes_voided_coupons(): void
    {
        $sweepstake = $this->createSweepstakeWithLink(['coupon_count' => 5]);
        $admin = User::factory()->create();

        $service = new RedemptionService();

        $redemption1 = $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user1@example.com',
        );

        $redemption2 = $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user2@example.com',
        );

        // Anular segunda redención
        $service->voidRedemption($redemption2->id, 'Error', $admin);

        $export = new SweepstakeCouponsExport($sweepstake['model']->id);
        $rows = $export->collection()->toArray();

        // Solo 5 cupones válidos (de la primera redención)
        $this->assertCount(5, $rows);

        foreach ($rows as $row) {
            $this->assertEquals('No', $row['estado_anulado']);
        }
    }

    public function test_export_filename_format(): void
    {
        $sweepstake = $this->createSweepstakeWithLink(['coupon_count' => 1]);
        $export = new SweepstakeCouponsExport($sweepstake['model']->id);

        $filename = $export->fileName();

        $this->assertStringStartsWith('cupones-sorteo-', $filename);
        $this->assertStringEndsWith('.csv', $filename);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}\.csv$/', substr($filename, -15));
    }

    // ... helper method createSweepstakeWithLink() ...
}
```

#### Test 10: Permisos admin vs manager

```php
<?php

namespace Tests\Feature;

use App\Models\Sweepstake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_sweepstakes(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->post(route('filament.admin.resources.sweepstakes.store'), [
                'site_id' => 1,
                'name' => 'Test',
                'slug' => 'test',
                'starts_at' => now()->addDay(),
                'expires_at' => now()->addDays(30),
            ]);

        $response->assertRedirect();
    }

    public function test_manager_cannot_create_sweepstakes(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $response = $this->actingAs($manager)
            ->post(route('filament.admin.resources.sweepstakes.store'), [
                'site_id' => 1,
                'name' => 'Test',
                'slug' => 'test',
                'starts_at' => now()->addDay(),
                'expires_at' => now()->addDays(30),
            ]);

        $response->assertForbidden();
    }

    public function test_manager_can_view_sweepstakes(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $sweepstake = Sweepstake::factory()->create();

        $response = $this->actingAs($manager)
            ->get(route('filament.admin.resources.sweepstakes.view', $sweepstake));

        $response->assertOk();
    }

    public function test_manager_can_void_redemptions(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $sweepstake = $this->createSweepstakeWithLink(['coupon_count' => 1]);
        $service = new RedemptionService();
        $redemption = $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user@example.com',
        );

        // Manager puede anular
        $this->assertTrue($manager->can('voidRedemption', $sweepstake['model']));
    }

    public function test_manager_cannot_delete_sweepstakes_with_coupons(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole('manager');

        $sweepstake = $this->createSweepstakeWithLink(['coupon_count' => 1]);
        $service = new RedemptionService();
        $service->redeem(
            linkCode: $sweepstake['link']->code,
            userEmail: 'user@example.com',
        );

        // Manager no puede borrar si hay cupones
        $this->assertFalse($manager->can('delete', $sweepstake['model']));
    }

    // ... helper methods ...
}
```

---

## 10. Resultado Final

### 10.1 Arquitectura Recomendada

**Patrón arquitectónico:** Layered Architecture con Domain-Driven Design simplificado

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  Inertia.js  │  │   Filament   │  │    API       │     │
│  │    (React)   │  │   Admin      │  │  (opcional)  │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                      Application Layer                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │ Controllers  │  │  Requests    │  │    Jobs      │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  Services    │  │   Actions    │  │  Observers   │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                       Domain Layer                          │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │    Models    │  │   Policies   │  │   Enums      │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  Exceptions  │  │   Events     │  │  Listeners   │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                  Infrastructure Layer                       │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │   MySQL DB   │  │     Cache    │  │     Queue    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
└─────────────────────────────────────────────────────────────┘
```

**Principios aplicados:**
- **Single Responsibility:** Cada clase tiene una responsabilidad clara
- **Dependency Injection:** Services reciben dependencias por constructor
- **Separation of Concerns:** Lógica de negocio separada de presentación
- **Testability:** Services y Actions son fáciles de testear en aislamiento

### 10.2 Diagrama Textual de Relaciones

```
Site (1) ────< (N) Sweepstake
  │                   │
  │                   │
  │             (1) ──┴──< (N) RedemptionLink
  │                   │                   │
  │                   │             (1) ──┴──< (N) CouponRedemption
  │                   │                   │                   │
  │                   │                   │             (1) ──┴──< (N) Coupon
  │                   │                   │                   │
  │                   │                   │                   └──< (1) User
  │                   │                   │
  │                   │                   └──< (1) User
  │                   │
  │                   └──< (1) User
  │
  └──< (N) User (relationship inherited from existing app)


RedemptionSource (1) ────< (N) RedemptionLink


User (1) ────< (N) CouponRedemption
  │
  └──< (N) Coupon
```

**Leyenda:**
- `(1)` = Uno
- `(N)` = Muchos
- `───<` = Relación hasMany
- `>` = Relación belongsTo

### 10.3 Listado de Migraciones

1. `2026_07_02_000001_create_sites_table.php`
2. `2026_07_02_000002_create_sweepstakes_table.php`
3. `2026_07_02_000003_create_redemption_sources_table.php`
4. `2026_07_02_000004_create_redemption_links_table.php`
5. `2026_07_02_000005_create_coupon_redemptions_table.php`
6. `2026_07_02_000006_create_coupons_table.php`
7. (opcional) `2026_07_02_000007_seed_redemption_sources.php` - Seeder de tipos de origen por defecto (qr, link, manual, api)

### 10.4 Listado de Models

1. `app/Models/Site.php`
2. `app/Models/Sweepstake.php`
3. `app/Models/RedemptionSource.php`
4. `app/Models/RedemptionLink.php`
5. `app/Models/CouponRedemption.php`
6. `app/Models/Coupon.php`
7. (existente) `app/Models/User.php` - Extendido con nuevas relaciones

### 10.5 Listado de Resources Filament

1. `app/Filament/Resources/SiteResource.php`
   - Pages: ListSites, CreateSite, EditSite, ViewSite
   - RelationManager: SweepstakesRelationManager

2. `app/Filament/Resources/SweepstakeResource.php`
   - Pages: ListSweepstakes, CreateSweepstake, EditSweepstake, ViewSweepstake
   - Widgets: SweepstakeStatsWidget
   - RelationManagers:
     - CouponsRelationManager
     - RedemptionsRelationManager
     - RedemptionLinksRelationManager

3. `app/Filament/Resources/RedemptionLinkResource.php`
   - Pages: ListRedemptionLinks, CreateRedemptionLink, EditRedemptionLink, ViewRedemptionLink

4. `app/Filament/Resources/CouponRedemptionResource.php`
   - Pages: ListCouponRedemptions, ViewCouponRedemption

5. `app/Filament/Resources/CouponResource.php`
   - Pages: ListCoupons (read-only)
   - Bulk actions: MarkAsUsed, MarkAsVoided

6. `app/Filament/Exports/SweepstakeCouponsExport.php`
7. `app/Filament/Exports/RedemptionExport.php`

### 10.6 Riesgos y Decisiones Importantes

#### Riesgos Críticos

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|--------------|---------|------------|
| **Race conditions** en cobros concurrentes | Media | Alto | `lockForUpdate()` + tests de concurrencia + índice único en DB |
| **Performance** en exportaciones masivas | Media | Medio | Chunking + StreamedResponse + Queue jobs para >100k registros |
| **Encoding CSV** incompatible con Excel | Baja | Medio | UTF-8 con BOM + testing con Excel Windows |
| **Soft deletes** confusos en admin UX | Media | Medio | Filtrar por defecto `is_voided = false` + toggle para ver anulados |
| **Números correlativos** con gaps por anulaciones | Baja | Bajo | Documentar claramente + indicador visual en admin |

#### Decisiones Arquitectónicas

| Decisión | Opción Recomendada | Alternativa | Justificación |
|----------|-------------------|-------------|---------------|
| **Numeración correlativa** | Contador en `sweepstakes.last_coupon_number` con lock | Auto-increment + COUNT() | Más simple, más performante, garantiza correlativo |
| **Anulación de cupones** | `is_voided` flag + soft delete | Solo soft delete | Diferencia entre "eliminado por error" y "anulado por admin" |
| **Concurrencia** | `lockForUpdate()` + transacción | Cache locks | DB locks son más confiables, fáciles de debuggear |
| **Exportación CSV** | Filament ExportAction + Maatwebsite Excel | Custom StreamedResponse | Integración nativa, soporta filtros, fácil de mantener |
| **Identificación usuario** | Opcional (crear si no existe) | Requerido (crear siempre) | Flexibilidad para usuarios anónimos, mejor UX |

#### Decisiones Pendientes de Validación

1. **¿Debe haber un límite global de cupones por sorteo?**
   - **Recomendado:** Sí, campo `max_coupons` opcional (NULL = sin límite)
   - **Contexto:** Permite controlar costos o disponibilidad física

2. **¿Debe haber un límite por usuario?**
   - **Recomendado:** Sí, campo `max_coupons_per_user` opcional (NULL = sin límite)
   - **Contexto:** Previene abuso, permite "1 por persona"

3. **¿Los cupones anulados deben contarse en el límite máximo?**
   - **Recomendado:** NO, solo cuentan cupones válidos
   - **Contexto:** Si el admin anula por error, debería poder re-emitir
   - **Cambio en diseño:** Validación debe contar `validCoupons()` no `last_coupon_number`

4. **¿Debe haber workflow de aprobación para resultados de sorteo?**
   - **Recomendado:** NO, editar campo `draw_result` es suficiente
   - **Contexto:** Mantener simple, usar version control de DB si es necesario

5. **¿Debe haber notificaciones al usuario al cobrar?**
   - **Recomendado:** Opcional (fase 2)
   - **Contexto:** Email o SMS con resumen y números de cupón

---

## Conclusión

Esta propuesta presenta una arquitectura limpia, escalable y alineada con las mejores prácticas de Laravel 13 y Filament v5. Los puntos clave son:

1. **Separación clara de conceptos:** sorteo, origen de cobro, evento de cobro, cupón individual
2. **Numeración correlativa robusta:** con lock de DB y validación en índice único
3. **Trazabilidad completa:** auditoría de cada cobro con metadata
4. **Admin Filament potente:** widgets, filtros, acciones, exportación
5. **Testing exhaustivo:** cobertura de casos normales, edge cases y concurrencia
6. **Migración gradual:** plan por fases con rollback planificado

**Siguiente paso recomendado:**
Validar esta propuesta con el equipo de negocio y stakeholders, luego iniciar con **Fase 1: Diseño de Schema**.

---

**Versión:** 1.0  
**Autor:** GitHub Copilot  
**Fecha:** 2026-07-02
