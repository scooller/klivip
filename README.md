# KliVip

Plataforma web multi-sitio para gestion de promociones, juegos y experiencia de cliente.

## Resumen Del Proyecto

KliVip combina un panel administrativo en Filament con un front publico por subdominios, donde cada sitio muestra contenido propio (promociones, juegos y datos de contacto).

### Objetivos Funcionales

1. Gestionar contenido por sitio desde admin.
2. Mostrar ese contenido en el front de cada subdominio.
3. Separar autenticacion de clientes y administradores.
4. Mantener UI del front basada en Web Awesome + React + Inertia.

## Stack Tecnico

### Backend

1. PHP 8.4
2. Laravel 13
3. Filament 5
4. Spatie Laravel Settings
5. MySQL

### Frontend

1. Inertia.js v3 + React 19
2. Web Awesome 3
3. Vite 8
4. Tailwind CSS 4 (entorno)
5. Sass
6. react-phone-input-2 (selector de telefono internacional)

## Arquitectura De Accesos

### Admin

1. Dominio administrativo dedicado (ejemplo: `admin.klivip.test`).
2. Acceso para roles de panel (`super-admin`, `admin`, `manager`).

### Front Cliente

1. Dominio por sitio (`{site}.{dominio}`), por ejemplo `sitio1.klivip.test`.
2. Login de cliente separado usando guard `customer`.
3. Usuarios con rol `user` para autenticacion de front.

## Tipos De Usuario

Los tipos de usuario estan definidos en `app/Enums/UserRole.php`:

1. `super-admin`: acceso total al panel administrativo y gestion global.
2. `admin`: acceso administrativo del panel segun permisos del sistema.
3. `manager`: gestion operativa en panel (sitios, promociones, juegos).
4. `user`: cuenta de cliente para acceso al front (`/usuario`).

## Arquitectura Del Sistema

### Capas Principales

1. `Panel Admin (Filament)`: gestion interna de sitios, promociones, juegos, mensajeria.
2. `Front Publico (Inertia + React)`: experiencia de cliente por subdominio.
3. `Capa de Aplicacion (Laravel)`: controladores, politicas, reglas de negocio.
4. `Persistencia (MySQL)`: entidades principales (`users`, `sites`, `games`, `promotions`, `sms_templates`, `sent_sms`).

### Flujo De Autenticacion

1. `web` guard: flujo de autenticacion para panel administrativo.
2. `customer` guard: flujo de autenticacion para cliente en front.
3. Ambos guards usan provider `users`, con separacion por rol y contexto de acceso.

### Flujo De Render Por Sitio

1. El host del request determina el sitio activo (subdominio).
2. El backend resuelve contenido del sitio (promociones/juegos/datos base).
3. Inertia entrega props a las paginas React (`Home`, `User`).
4. El front renderiza UI con componentes reutilizables de `resources/js/Components/Front`.

## Sistema De Mensajeria

### Email (FinMail)

1. Templates de email editables desde panel (`FinityLabs\FinMail` plugin).
2. Log de correos enviados con reenvio desde panel.
3. Seeders con templates base en `database/seeders/FinMailTemplatesSeeder.php`.

### SMS

1. **Plantillas SMS** (`SmsTemplate`): templates con body traducible (JSON `{"es": "..."}`) y tokens para variables dinamicas.
2. **Log de envios** (`SentSms`): registro de cada SMS enviado con tracking de estado, error y relacion polimorfica al modelo origen.
3. **Estados** (`SmsStatus` enum): `Draft`, `Queued`, `Sent`, `Failed`.
4. **Servicio** (`App\Services\SmsService`): envio desde template o directo, logging automatico y reenvio.
5. **Recursos Filament** (grupo "Mensajeria"):
   - `Plantillas SMS` (`/sms-templates`): CRUD completo con previsualizacion.
   - `Mensajes SMS` (`/sent-sms`): listado de envios, modal de detalle, accion de reenviar y filtro de errores.
6. **Seeder**: `database/seeders/SmsTemplatesSeeder.php` con 4 templates base (cupones recibidos, OTP, desbloqueo perfil, recordatorio de sorteo).

## Sistema De Sorteos (Draws)

### Funcionalidad

1. **Sorteo de cupones**: desde la vista de una sweepstake, el boton "Sortear" abre un modal con ruleta animada (canvas + Alpine.js).
2. **Seleccion de ganadores**: el admin elige cuantos ganadores (1..N); la seleccion es **backend** con `Collection::random()` (crypto-safe, usa `random_int`).
3. **Universo de cupones elegibles**: cupones `validCoupons()` (no voided, no soft-deleted, no usados previamente).
4. **Historial**: cada sorteo queda registrado en `sweepstake_draws` + pivot `sweepstake_draw_coupon` con posicion del ganador.
5. **Notificaciones**: envio de email (`prize-won` via FinMail) y SMS (`prize-won` via SmsService) a los ganadores, idempotente con flag `force`.

### Componentes

1. **Servicio** (`App\Services\SweepstakeDrawService`): logica de draw + dispatch de notificaciones.
2. **Job** (`App\Jobs\NotifySweepstakeWinnersJob`): cola, tries=3, try/catch por canal.
3. **Action Filament** (`DrawSweepstakeAction`): modal con ruleta + formulario (ganadores, notas, toggle notificar).
4. **RelationManager** (`SweepstakeDrawsRelationManager`): tab "Sorteos realizados" en la vista de sweepstake.
5. **Infolist** (`SweepstakeDrawInfolist`): detalle del sorteo + lista de ganadores.

## Configuracion Del Dominio Admin

> **Importante**: El dominio del panel administrativo se configura en `.env` con la variable `ADMIN_DOMAIN` y se lee desde `config/app.php` como `admin_domain`.
>
> **Nunca** usar `env()` directamente en providers o servicios; tras ejecutar `config:cache`, los valores de `env()` no resueltos desde un archivo de configuracion devuelven `null`, causando que el dominio del panel no coincida y las rutas del front capturen el trafico del admin.

## Estructura Relevante

1. `app/Filament`: recursos y gestion administrativa.
2. `app/Http/Controllers/Front`: controladores del front por subdominio.
3. `routes/web.php`: rutas front + autenticacion cliente.
4. `resources/js/Pages`: paginas Inertia (`Home`, `User`).
5. `resources/js/Components/Front`: componentes reutilizables del front.
6. `database/seeders`: seeders para datos base y usuarios de prueba.

## Componentes Front Reutilizables

1. `FrontHeader.jsx`
2. `FrontFooter.jsx`
3. `UserWelcomeCard.jsx`
4. `UserBenefitsCard.jsx`
5. `UserSessionCard.jsx`
6. `UserLoginCard.jsx`

## Manejo De Errores En Login Cliente

1. Manejo de estado con `useForm` de Inertia.
2. Mensajes de error de backend reflejados en componentes del formulario.
3. `WaCallout` para feedback de autenticacion en login/sesion.
4. Feedback visual de inicio/cierre de sesion desde la UI de Web Awesome.

## Datos De Prueba

Seeder: `database/seeders/UserSeeder.php`

Credenciales locales de ejemplo (password: `password`):

1. `admin@klivip.test` (super-admin)
2. `manager@klivip.test` (manager)
3. `cliente1@klivip.test` (user cliente)
4. `cliente2@klivip.test` (user cliente)

## Instalacion Y Ejecucion Local

1. Instalar dependencias y preparar entorno:

```bash
composer run setup
```

2. Levantar entorno de desarrollo completo:

```bash
composer run dev
```

3. Ejecutar pruebas:

```bash
composer run test
```

4. Build frontend para produccion:

```bash
npm run build
```

## Despliegue En Produccion

Despues de `git pull`, si hay cambios en migraciones, seeders o configuracion:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force          # o ejecutar SQL manual si no hay acceso a CLI
php artisan db:seed --class=SmsTemplatesSeeder --force
php artisan config:clear              # siempre clear antes de re-cachear
php artisan route:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan queue:restart
npm run build
```

> **Crtitico**: Siempre ejecutar `config:clear` y `route:clear` antes de `config:cache` y `route:cache` en produccion para evitar que caches obsoletos causen redirecciones incorrectas.

## Politica De Cambios

El historial de cambios **no** se mantiene en este README.

Toda actualizacion funcional/tecnica debe registrarse exclusivamente en:

- [CHANGELOG.md](CHANGELOG.md)

## Licencia

Este proyecto se distribuye bajo licencia MIT.
