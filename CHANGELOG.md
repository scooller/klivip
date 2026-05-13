# Changelog

Este archivo concentra el historial de cambios del proyecto.

## Politica

1. Registrar aqui todos los cambios funcionales y tecnicos.
2. No duplicar historial de cambios en README.
3. Mantener entradas con fecha, alcance y resumen.

## Formato Recomendado

```md
## YYYY-MM-DD

### Added
- ...

### Changed
- ...

### Fixed
- ...

### Notes
- ...
```

## 2026-05-12

### Added
- Integracion de datos backend en front por sitio/subdominio.
- Pagina de usuario cliente en front (`/usuario`).
- Guard `customer` para separar auth cliente/admin.
- Componentes reutilizables del front (`FrontHeader`, `FrontFooter`, `UserWelcomeCard`, `UserBenefitsCard`, `UserSessionCard`, `UserLoginCard`).
- Usuarios de prueba en `UserSeeder` para panel y clientes.

### Changed
- Seccion de juegos del home convertida a slider con todos los juegos y destacados visuales.
- Navegacion landing migrada a `Anchor` de Ant Design.
- Badges visuales migrados a `Tag` de Ant Design.
- Mensajeria de login cliente con copy mas profesional.
- `README.md` actualizado con documentacion del proyecto y referencia unica a este archivo.

### Fixed
- Normalizacion defensiva en formato de dias recurrentes de promociones para evitar error de tipos en Filament.
- Manejo de errores de login cliente con `Alert` + `notification` en front.

### Notes
- El historial futuro debe agregarse solo en este archivo.
