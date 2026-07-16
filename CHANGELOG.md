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

## 2026-07-15

### Added
- Integracion de `react-phone-input-2` para entrada de telefono internacional con selector de pais, banderas y busqueda.
- Soporte de prefijo internacional automatico en registro y edicion de perfil (reemplaza el `select` manual de paises).

### Changed
- `User.jsx`: campo de telefono de registro migrado de `input` manual a `PhoneInput` con `country="cl"`, paises preferidos LATAM y `enableSearch`.
- `UserSessionCard.jsx`: campo de telefono de edicion de perfil migrado de `input` manual a `PhoneInput`.
- `handleRegisterCustomer` (`User.jsx`): normalizacion de telefono ajustada a `+{digits}` (sin doble prefijo).
- `handleSubmit` (`UserSessionCard.jsx`): agregado `form.transform()` para normalizar telefono antes de enviar al backend.
- Confirmacion de telefono en registro ahora compara solo digitos en lugar de strings formateados.

### Fixed
- Error critico: `this.state.phone` / `this.setState` en componente funcional `User.jsx` (causaba crash en runtime).
- Doble prefijo de pais: `react-phone-input-2` ya incluye el codigo de pais, se elimino la sobreposicion manual con `registerCountryPrefix`.
- Funcion `formatPhone` eliminada de `UserSessionCard.jsx` (ya no se necesita con el formateo nativo del componente).

### Notes
- Backend `normalizePhone()` extrae solo digitos y antepone `+`, por lo que el formato `+{digits}` es compatible.
- El campo de login (`User.jsx`) permanece como `input` hibrido telefono/email por diseno.
- Campos de solo lectura (`UserSessionCard.jsx` perfil bloqueado) y OTP no requieren `PhoneInput`.

## 2026-05-12

### Added
- Integracion de datos backend en front por sitio/subdominio.
- Pagina de usuario cliente en front (`/usuario`).
- Guard `customer` para separar auth cliente/admin.
- Componentes reutilizables del front (`FrontHeader`, `FrontFooter`, `UserWelcomeCard`, `UserBenefitsCard`, `UserSessionCard`, `UserLoginCard`).
- Usuarios de prueba en `UserSeeder` para panel y clientes.

### Changed
- Seccion de juegos del home convertida a slider con todos los juegos y destacados visuales.
- Navegacion landing migrada a componentes reutilizables de front.
- Badges visuales migrados a componentes de Web Awesome.
- Mensajeria de login cliente con copy mas profesional.
- `README.md` actualizado con documentacion del proyecto y referencia unica a este archivo.

### Fixed
- Normalizacion defensiva en formato de dias recurrentes de promociones para evitar error de tipos en Filament.
- Manejo de errores de login cliente con componentes de feedback del front.

### Notes
- El historial futuro debe agregarse solo en este archivo.
