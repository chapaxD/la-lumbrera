# Mejoras pendientes

## 🔴 Crítico

### 1. Órdenes en CSV → base de datos ✅ COMPLETADO
~~El punto más frágil del sistema. Si el servidor se reinicia con mesas ocupadas, se pierden las órdenes. Mover `mesas_ocupadas/` y `deliveries/` a tablas MySQL (`ordenes_activas`, `items_orden`) haría el sistema confiable y permitiría consultas reales.~~

### 2. Autenticación JWT ✅ COMPLETADO
~~El login solo guarda `logeado=true` en `localStorage`. No hay sesión en el servidor (cookie, JWT o session PHP). Cualquiera que abra DevTools puede falsificar el rol o simular estar logueado.~~

Se implementó autenticación JWT con PHP puro (sin librerías externas):
- `api/jwt_utils.php`: genera y valida tokens JWT con `hash_hmac` HS256, expiración 2h.
- `api/verificar_token.php`: middleware reutilizable — extrae el token del header `Authorization: Bearer`, valida y expone `$tokenDatos`. Devuelve 401 si el token falta o es inválido.
- `api/iniciar_sesion.php`: ahora devuelve el token JWT en la respuesta al autenticar.
- `src/Servicios/HttpService.js`: envía el token en el header `Authorization` en cada request.
- `src/App.vue`: guarda y verifica `jwt_token` en localStorage (reemplazó el flag `logeado`).
- `src/components/Encabezado.vue`: limpia `jwt_token` y todos los datos al hacer logout.
- Endpoints protegidos con `verificar_token.php`: `obtener_usuarios`, `registrar_usuario`, `editar_usuario`, `eliminar_usuario`, `obtener_ventas`, `obtener_historial_cajas`, `abrir_caja`, `cerrar_caja`.

---

## 🟡 Importante

### 3. Vue 2 llegó a EOL (fin de soporte dic 2023)
No es urgente migrar, pero no recibirá parches de seguridad. Considerar Vue 3 + Vite a futuro.

### 4. Sin paginación en reportes ✅ COMPLETADO
~~Si el negocio lleva años, `obtener_ventas.php` traerá miles de registros sin límite — puede colgar el navegador. Se implementó paginación server-side real: `obtenerVentas()` usa `LIMIT/OFFSET`, nueva función `contarVentas()` para el total del período, y `ReporteVentas.vue` con `backend-pagination` de Buefy. El total del período se calcula en SQL (no en el cliente).~~

### 5. Historial de stock (Kardex) sin filtro de fechas ✅ COMPLETADO
~~Solo listaba todo sin poder segmentar por período. Se implementó `b-datepicker` con `range` en `HistorialStock.vue`, el endpoint `obtener_historial_stock.php` acepta `inicio`/`fin` por POST, y `obtenerHistorialStock()` en `funciones.php` filtra con `WHERE DATE(h.fecha) BETWEEN ? AND ?`. El PDF exportado refleja el rango seleccionado.~~

### 6. Sin confirmación de reserva por canal externo
Las reservas se crean manualmente. Un formulario público simple o integración WhatsApp/email sería muy útil.

**Solución propuesta:** Página pública `/reservar` sin login que llame a `registrar_reserva.php`, con envío de confirmación por email (PHPMailer) o enlace de WhatsApp generado automáticamente.

---

## 🟢 Nice-to-have

### 7. Dashboard de inicio más rico ✅ COMPLETADO
Solo muestra caja y alertas. Podría mostrar ventas del día en tiempo real, mesa más ocupada, producto más vendido.

### 8. Control de acceso dentro del rol mesero ✅ COMPLETADO
~~Actualmente un mesero puede ver órdenes de otro mesero. Se implementó filtro por `idUsuario`/`rol` en `obtener_mesas.php` y `obtener_deliveries.php` para ocultar detalles ajenos, y se bloquearon acciones (agregar/entregado/cancelar) y actualizaciones desde `ocupar_mesa.php`/`editar_mesa.php`/`registrar_delivery.php` cuando la orden pertenece a otro usuario (excepto rol `admin`).~~

### 9. Backups automáticos
No hay mecanismo de respaldo de la BD. Un script programado con `mysqldump` en el WAMP protegería ante fallos.

**Solución propuesta:** Script `.bat` con `mysqldump` ejecutado por el Programador de tareas de Windows cada noche.

### 10. PWA / modo offline
Con service workers básicos, el mesero podría ver las mesas sin conexión y sincronizar al recuperarla.
