---
applyTo: "src/**/*.{vue,js}"
---

# Convenciones del Frontend

## Llamadas HTTP — Usar HttpService

```js
import HttpService from '@/Servicios/HttpService'

// GET
const resp = await HttpService.obtener('obtener_insumos.php')

// POST (crear/actualizar)
const resp = await HttpService.registrar({ nombre, precio }, 'registrar_insumo.php')

// POST con filtros (leer con cuerpo)
const resp = await HttpService.obtenerConDatos({ idMesa }, 'obtener_ordenes.php')

// Eliminar
const resp = await HttpService.eliminar('eliminar_insumo.php', id)
```

Nunca usar `fetch()` ni `axios` directamente — siempre a través de `HttpService`.

## Manejo de respuestas

```js
if (resp.resultado) {
  // Usar resp.datos
} else {
  // Mostrar resp.error o resp.mensaje
}
```

## Componentes de UI

- Usar componentes de **Buefy** (`b-table`, `b-modal`, `b-field`, `b-input`, etc.) — no elementos HTML crudos de formulario.
- Íconos via Material Design Icons: `<b-icon icon="pencil" />`.
- Notificaciones via `this.$buefy.toast.open(...)` o `this.$buefy.dialog.confirm(...)`.

## Routing

Las rutas se definen en `src/router/index.js`. Cada ruta corresponde a una carpeta de dominio en `src/components/`.

## Autenticación

- Token guardado en `localStorage.jwt_token`.
- Rol en `localStorage.rol` (`admin` o `mesero`).
- Ante cualquier respuesta 401, HttpService limpia el localStorage y redirige a `/`.
