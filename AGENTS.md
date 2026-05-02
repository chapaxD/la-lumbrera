# Botanero Ventas — Instrucciones para el Agente

Sistema de punto de venta y gestión para restaurantes. **Vue.js 2 SPA + API REST en PHP + MySQL/TiDB.**

## Comandos de Desarrollo y Build

```bash
npm install        # Instalar dependencias del frontend
npm run dev        # Servidor de desarrollo en http://localhost:8080 (proxy /api/ → PHP local)
npm run build      # Build de producción → dist/
```

- No hay framework de pruebas configurado.
- El backend (PHP) lo sirve Apache/Laragon localmente; no se necesita iniciar un servidor PHP aparte.
- La producción se despliega en Render.com — `render.yaml` ejecuta `npm run build` automáticamente.

## Arquitectura

| Capa | Tecnología | Ubicación |
|------|------------|----------|
| Frontend SPA | Vue 2 + Buefy (Bulma) | `src/` |
| Cliente HTTP | Wrapper personalizado de fetch | `src/Servicios/HttpService.js` |
| Router | Vue Router 3 | `src/router/index.js` |
| Reportes PDF | jsPDF + Chart.js | `src/Servicios/ReportesPdfService.js` |
| API Backend | PHP 8 (sin framework) | `api/` |
| Lógica de negocio | Librerías modulares | `api/lib/` |
| Base de datos | MySQL 8 (local) / TiDB Cloud (prod) | PDO via `api/db_config.php` |
| Autenticación | JWT HS256 personalizado | `api/jwt_utils.php` |
| Tiempo real | Server-Sent Events | `api/cocina_sse.php` |

### Estructura de componentes del frontend (`src/components/`)

Cada dominio tiene su propia carpeta: `Caja/`, `Categorias/`, `Clientes/`, `Cocina/`, `Compras/`, `Configuracion/`, `Insumos/`, `MenuDia/`, `Ordenar/`, `Parrilla/`, `Reservas/`, `Usuarios/`, `Ventas/`.

### Patrones de HttpService

```js
HttpService.obtener(ruta)                // GET
HttpService.registrar(datos, ruta)       // POST (crear)
HttpService.obtenerConDatos(datos, ruta) // POST (leer con filtros)
HttpService.eliminar(ruta, id)           // POST (eliminar)
```

El token de autenticación se lee de `localStorage` con la clave `jwt_token` y se envía como `Authorization: Bearer <token>`.

## Convenciones de la API

- Todos los endpoints: `/api/<accion>.php` — sin framework de sub-routing.
- Nomenclatura: `obtener_*.php` (lectura), `registrar_*.php` (creación), `editar_*.php` (actualización), `eliminar_*.php` (eliminación), verbo primero para cambios de estado (`abrir_caja.php`, `ocupar_mesa.php`).
- Entrada: cuerpo JSON via `file_get_contents("php://input")`, incluso para lecturas.
- Formato de respuesta estándar:
  ```json
  { "resultado": true, "datos": [...], "mensaje": "..." }
  { "resultado": false, "error": "..." }
  ```
- Los endpoints protegidos deben incluir `require 'verificar_token.php'` al inicio — valida el Bearer token y define `$tokenDatos`.
- Las cabeceras comunes (CORS + `Content-Type: application/json`) las establece `encabezado.php`.

### Cómo agregar un nuevo endpoint

1. Crear `api/<accion>.php`.
2. Comenzar con `require 'encabezado.php'; require 'verificar_token.php';` si requiere autenticación.
3. Conectar con `$pdo = conectarBaseDatos();` (de `funciones.php`).
4. Retornar `json_encode(["resultado" => true, "datos" => $data])`.

### Cómo agregar una función a lib

Colocarla en el archivo `api/lib/<dominio>.php` correspondiente (o crear uno nuevo). Los archivos lib agrupan las consultas SQL relacionadas.

## Base de Datos

- Detección automática del entorno en `api/db_config.php`: local → `localhost:3306` (sin TLS), prod → TiDB Cloud (TLS requerido, sin conexiones persistentes).
- Zona horaria: UTC (`SET time_zone = '+00:00'`).
- PDO en modo excepción; usar `try/catch(PDOException)`.
- Referencia del esquema: `botanero_ventas.sql` (local), `botanero_tidb_backup.sql` (prod).
- Tablas clave: `usuarios`, `insumos`, `categorias`, `ventas`, `facturas`, `mesas`, `ordenes_activas`, `items_orden`, `reservas`, `clientes`, `deliveries`, `caja_diaria`.

## Autenticación y Roles

- Roles: `admin`, `mesero` (guardado en `usuarios.rol`).
- El payload JWT incluye `idUsuario`, `rol`, `nombre`; expira en 12 horas.
- Ante una respuesta 401, el frontend limpia el localStorage y redirige al login.
- La contraseña por defecto `PacoHunterDev` obliga al cambio de contraseña en el primer inicio de sesión.

## Configuración de Entornos

| Archivo | Propósito |
|---------|----------|
| `config/dev.env.js` | Variables de entorno del frontend para desarrollo (API con proxy a PHP local) |
| `config/prod.env.js` | Variables de entorno del frontend para producción (`API_BASE` → URL de Render.com) |
| `api/db_config.php` | Credenciales de BD del backend (detectadas automáticamente por hostname) |

URL base de la API en producción: `https://la-lumbrera-resto.onrender.com/api/`

## Documentación Clave

- [MANUAL_SISTEMA.md](MANUAL_SISTEMA.md) — manual del sistema para usuarios finales
- [MEJORAS.md](MEJORAS.md) — mejoras planificadas
- [PENDIENTES.md](PENDIENTES.md) — tareas pendientes
