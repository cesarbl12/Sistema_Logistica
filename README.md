# Sistema Logístico PWA

Aplicación web progresiva (PWA) para el control de acceso de contenedores en una
terminal/patio logístico: registra quién entra o sale, genera un pase de acceso
con código QR, y permite validar esa operación en la caseta de vigilancia.

## Roles

- **Cliente**: da de alta un registro de contenedor (operador, placas, número de
  contenedor, línea de transporte, tipo de operación) y recibe un código QR como
  pase de acceso. Puede ver y editar su historial de registros.
- **Guardia (operador)**: escanea el QR del cliente en la caseta, valida los
  datos del registro (incluida la foto de identificación) y marca la operación
  como realizada.
- **Administrador**: gestiona usuarios, consulta estadísticas (QR creados,
  escaneados, mensajes de WhatsApp enviados) y revisa la bitácora de eventos del
  sistema.

## Flujo general

1. El cliente crea un registro desde `index.html` → se genera un código QR y
   (según configuración) se notifica por WhatsApp/correo.
2. El operador de la caseta escanea ese QR desde `guardia.html`, revisa los
   datos y confirma la operación.
3. El administrador supervisa todo desde `admin.html`: usuarios, estadísticas y
   bitácora de eventos (`eventos_log`).

## Estructura del proyecto

- `index.html`, `guardia.html`, `admin.html`, `login.html` — vistas por rol.
- `js/` — lógica de cliente (auth, registro, escaneo QR, panel admin).
- `api/` — endpoints PHP (login, registros, usuarios, estadísticas, bitácora).
- `config/` — configuración de conexión a base de datos.
- `schema.sql` — esquema de la base de datos (`usuarios`, `registros_accesos`,
  `eventos_log`, `intentos_login`).
- `uploads/` — fotos de identificación subidas por los clientes.
- `manifest.json`, `sw.js`, `icon-192.png` — soporte PWA (instalable, ícono).

## Autenticación y roles

La sesión se valida en cada endpoint de `api/` (protección real en el
servidor); `js/auth.js` solo evita que un usuario sin sesión o con el rol
equivocado vea una página que no le corresponde, redirigiendo a `login` o a la
página que sí le toca según su rol (`cliente` → `index`, `operador` →
`guardia`, `admin` → `admin`).

## URLs

Las páginas se sirven sin extensión `.html` (`/login`, `/index`, `/guardia`,
`/admin`) mediante reglas de reescritura en `.htaccess`; la raíz del sitio
redirige a `/login`.

## Despliegue

Pensado para correr en XAMPP (local) o en hosting compartido tipo Hostinger
(ver comentarios en `schema.sql` y `.htaccess` sobre las diferencias entre
ambos entornos).
