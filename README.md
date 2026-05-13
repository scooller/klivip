# KliVip

Plataforma web multi-sitio para gestion de promociones, juegos y experiencia de cliente.

## Resumen Del Proyecto

KliVip combina un panel administrativo en Filament con un front publico por subdominios, donde cada sitio muestra contenido propio (promociones, juegos y datos de contacto).

### Objetivos Funcionales

1. Gestionar contenido por sitio desde admin.
2. Mostrar ese contenido en el front de cada subdominio.
3. Separar autenticacion de clientes y administradores.
4. Mantener UI del front basada en Ant Design + React + Inertia.

## Stack Tecnico

### Backend

1. PHP 8.4
2. Laravel 13
3. Filament 5
4. Spatie Laravel Settings
5. MySQL

### Frontend

1. Inertia.js v3 + React 19
2. Ant Design 6
3. Vite 8
4. Tailwind CSS 4 (entorno)
5. Sass

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

1. `Panel Admin (Filament)`: gestion interna de sitios, promociones y juegos.
2. `Front Publico (Inertia + React)`: experiencia de cliente por subdominio.
3. `Capa de Aplicacion (Laravel)`: controladores, politicas, reglas de negocio.
4. `Persistencia (MySQL)`: entidades principales (`users`, `sites`, `games`, `promotions`).

### Flujo De Autenticacion

1. `web` guard: flujo de autenticacion para panel administrativo.
2. `customer` guard: flujo de autenticacion para cliente en front.
3. Ambos guards usan provider `users`, con separacion por rol y contexto de acceso.

### Flujo De Render Por Sitio

1. El host del request determina el sitio activo (subdominio).
2. El backend resuelve contenido del sitio (promociones/juegos/datos base).
3. Inertia entrega props a las paginas React (`Home`, `User`).
4. El front renderiza UI con componentes reutilizables de `resources/js/Components/Front`.

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

1. Validaciones de formulario con `Form` de Ant Design.
2. Mensajes de error de backend reflejados en campos del formulario.
3. `Alert` para error de autenticacion en la tarjeta de login.
4. `notification` para feedback de inicio/cierre de sesion.

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

## Politica De Cambios

El historial de cambios **no** se mantiene en este README.

Toda actualizacion funcional/tecnica debe registrarse exclusivamente en:

- [CHANGELOG.md](CHANGELOG.md)

## Licencia

Este proyecto se distribuye bajo licencia MIT.
