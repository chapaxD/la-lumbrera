# Sistema de Gestión de Ventas — Botanero Ventas

## Descripción general

Sistema web para la gestión integral de un negocio de alimentos y bebidas (restaurante, bar, botanero). Permite controlar el punto de venta, cocina, inventario, cajas, reservas y más, desde cualquier dispositivo en red local.

**Stack tecnológico:**
- Frontend: Vue 2 + Buefy (Bulma) — SPA compilada
- Backend: PHP 7/8 con PDO MySQL — API REST
- Base de datos: MySQL (`botanero_ventas`) en WAMP
- Autenticación: JWT con PHP nativo (HS256, expira en 2h)
- Impresión de tickets: librería `printd` — abre diálogo de impresión del navegador

---

## Módulos del sistema

### 1. Inicio / Dashboard

Pantalla principal visible tras hacer login.

**Widgets de resumen (solo admin):**
- Estado de la caja del día (abierta/cerrada, monto apertura, total ventas, gastos)
- Total de ventas del día
- Número de ventas del día
- Número de clientes atendidos
- Mesas ocupadas actualmente

**Gráficas (con selector de período):**
- Ventas por hora del día
- Ventas por usuario/empleado
- Ventas por días de la semana
- Ventas por meses del año
- Top productos más vendidos (con tabla y montos)

**Alertas de stock:**
- Lista de insumos cuyo stock actual es menor o igual al stock mínimo configurado

---

### 2. Realizar Orden (Punto de Venta)

Vista principal de operación del negocio. Muestra todas las mesas y deliveries activos en tiempo real.

**Mesas locales:**
- Visualización de todas las mesas configuradas (libres u ocupadas)
- Indicador de reserva si la mesa tiene una reserva programada para hoy
- Botón **Ocupar** → abre la vista de selección de productos
- Botón **Cobrar** → abre el modal de cobro
- Botón **Agregar** → permite añadir más productos a una orden activa
- Botón **Entregado** → marca como entregados los ítems seleccionados mediante checkbox (entrega individual posible; solo aplica a ítems en estado `listo`)
- Botón **Entregar al cliente** (verde) → cierra y elimina la orden completa; solo aparece cuando ningún ítem está pendiente de cocina
- Botón **Cancelar** → cancela la orden con motivo obligatorio (se registra en historial)
- Vista colapsable de los insumos de cada orden con estados: Pendiente / Listo / Entregado
- Alerta sonora/toast cuando cocina marca un plato como listo
- Control de acceso: meseros solo ven y operan sus propias órdenes; admin ve todo

**Deliveries:**
- Tarjetas individuales por cada delivery activo
- Datos: cliente, dirección, teléfono, atiende, total
- Mismas acciones: Cobrar, Agregar, Cancelar

**Cobro (modal):**
- Métodos de pago: EFECTIVO, TARJETA, QR, MIXTO
- Modo MIXTO: desglose por efectivo + tarjeta + QR
- Cálculo automático de cambio para efectivo
- Validación: solo permite cobrar si la caja del día está abierta
- Tras confirmar → genera la venta, descuenta stock, imprime ticket automáticamente

**Ticket de venta impreso:**
- Diseñado para impresora térmica de 80mm (modelo de referencia: **Epson TM-M30 / M352A**, papel 80mm)
- Contenido: nombre del negocio, logo, teléfono, dirección, fecha, empleado, cliente, lista de productos con precio unitario y subtotal, total, método de pago, cambio
- Se imprime automáticamente al registrar el cobro

---

### 3. Ordenar (Armar Pedido)

Vista de construcción de la orden para una mesa o delivery específico.

- Búsqueda de productos por nombre o código
- Filtro por tipo (PLATILLO / BEBIDA) y categoría
- Vista del menú del día (productos destacados para ese día de la semana)
- Agregar productos al pedido con cantidad y notas/características especiales
- Resumen del pedido con totales en tiempo real
- Guardar/actualizar la orden en la base de datos

---

### 4. Pantalla Cocina

Vista diseñada para ser usada en la cocina, sin necesidad de login de caja.

- **Actualización automática cada 10 segundos** (polling)
- Muestra todas las órdenes activas (mesas + deliveries) con ítems pendientes
- Cada tarjeta muestra: tipo (mesa/delivery), cliente, contador de pendientes
- Ítems con instrucciones especiales resaltados visualmente
- Botón **"Listo"** por cada ítem pendiente → cambia estado a `listo`
- Cuando todos los ítems de una orden están listos, la tarjeta cambia a verde
- **Reportar faltante:** modal para notificar al admin que falta un insumo o hay un problema (tipo: FALTANTE, CALIDAD, OTRO)
- Los reportes se guardan en BD con fecha y se pueden resolver desde el admin

---

### 5. Gestión de Insumos (Inventario)

Catálogo completo de productos/ingredientes del negocio.

**Listado de insumos:**
- Tabla con código, nombre, categoría, tipo, precio y stock actual
- Filtros por tipo (PLATILLO/BEBIDA), categoría y búsqueda por nombre/código
- Indicador visual de alertas de stock bajo
- Acciones: Ver detalle, Editar, Eliminar

**Registrar insumo:**
- Campos: código, nombre, descripción, tipo, categoría, precio, stock inicial, stock mínimo, stock materia, tipo corte
- El stock inicial registra un movimiento AJUSTE en el historial automáticamente

**Editar insumo:**
- Si se modifica el stock, registra automáticamente el ajuste en el historial con la diferencia (positivo o negativo)

**Historial de Stock (Kardex):**
- Registro de todos los movimientos de inventario
- Tipos de movimiento: VENTA, COMPRA, AJUSTE, MERMA, CANCELACION
- Filtro por rango de fechas
- Columnas: fecha, insumo, usuario, cantidad (+/-), tipo, nota
- Exportación a PDF con el período seleccionado

---

### 6. Entrada de Mercadería (Compras)

Registro del reabastecimiento de insumos.

- Búsqueda de insumos por nombre o código con autocompletado
- Lista de recepción con stock actual previo y cantidad a recibir
- Visualización del stock resultante antes de confirmar
- Al confirmar: incrementa el stock de cada insumo y registra movimientos COMPRA en el historial
- Requiere usuario logueado (queda registrado quién hizo la entrada)

---

### 7. Registro de Merma

Descuento manual de stock por pérdidas, vencimientos o errores.

- Selección del insumo (por nombre o código)
- Cantidad a descontar
- Registra movimiento MERMA en historial_stock con cantidad negativa
- El stock nunca baja de 0 (protección `GREATEST(0, stock - cantidad)`)

---

### 8. Reservas

Gestión de reservas de mesas para clientes.

**Listado:**
- Tabla paginada con: fecha, hora, cliente, teléfono, mesa, personas, estado, quién la registró
- Búsqueda por nombre de cliente
- Ordenamiento por columnas

**Crear reserva:**
- Campos: nombre del cliente, teléfono, fecha, hora, número de personas, mesa (o "EVENTO TOTAL" para todo el local), notas
- Fecha mínima: hoy

**Gestión de estados:**
- Estados: PENDIENTE → CONFIRMADA → COMPLETADA / CANCELADA
- Cambio de estado directamente desde la tabla con select
- Las reservas COMPLETADAS se marcan automáticamente al registrar un cobro de la mesa correspondiente ese día

**Visualización en mesas:**
- Si una mesa tiene reserva para hoy, aparece destacada en amarillo en la vista de Realizar Orden con la hora y nombre del cliente

---

### 9. Menú del Día

Planificador semanal de platos disponibles por día.

- Panel lateral con los 7 días de la semana
- Por cada día: listado de insumos asignados como "plato del día"
- Añadir insumos al menú del día con autocompletado
- Eliminar insumos del menú
- Los platos del día aparecen destacados en la vista de Ordenar para facilitar su selección

---

### 10. Caja Diaria

Control del flujo de dinero del negocio por jornada.

**Widget en Inicio (admin):**
- Estado actual: ABIERTA / CERRADA
- Monto de apertura
- Total de ventas desde la apertura
- Total de gastos del día
- Ventas por método de pago (efectivo, tarjeta, QR)

**Abrir caja:** registro del monto inicial en efectivo con usuario y timestamp.

**Gastos de caja:** registro de egresos durante el día (concepto + monto) con usuario.

**Cerrar caja:** registra el monto de cierre, calcula el total de ventas y gastos del período. La caja queda en estado CERRADA.

**Historial de cajas:**
- Tabla con todas las jornadas anteriores
- Datos por caja: usuario apertura, monto apertura, usuario cierre, monto cierre, ventas totales, gastos totales, ventas tarjeta, ventas QR, fechas
- Exportación a PDF

**Restricción:** No se puede registrar una venta si la caja del día está cerrada.

---

### 11. Usuarios y Acceso

Gestión de cuentas del sistema.

**Roles disponibles:**
- `admin` — acceso completo a todos los módulos, reportes y configuración
- `mesero` — solo puede ver/operar sus propias órdenes; no accede a reportes, caja ni usuarios

**Operaciones (solo admin):**
- Listar todos los usuarios
- Registrar nuevos usuarios con correo, nombre, teléfono, contraseña y rol
- Editar datos de usuario
- Eliminar usuario
- Las contraseñas se almacenan con `password_hash` (bcrypt)

**Perfil propio:**
- Cualquier usuario puede ver su perfil
- Cambio de contraseña con verificación de la actual

**Autenticación JWT:**
- Login con correo y contraseña
- Se genera un token JWT (HS256, expira 2h)
- El token viaja en el header `Authorization: Bearer` en cada petición
- Los endpoints críticos validan el token; devuelven 401 si es inválido o expirado
- Logout limpia el token del localStorage

---

### 12. Categorías

Gestión de categorías para clasificar los insumos.

- Tipos: PLATILLO / BEBIDA
- Operaciones: Crear, Editar, Eliminar
- Las categorías se usan como filtro en la vista de insumos y en la pantalla de ordenar

---

### 13. Configuración del Negocio

Datos generales que aparecen en los tickets y el encabezado del sistema.

- Nombre del establecimiento
- Teléfono
- Dirección
- Número de mesas (define cuántas mesas aparecen en la vista de Realizar Orden)
- Logo (imagen que aparece en el ticket impreso)

**Configuración inicial:** asistente de primeros pasos al instalar el sistema.

---

### 14. Cancelaciones (Historial)

Registro automático de todas las órdenes canceladas.

- Tabla `cancelaciones` con: tipo (LOCAL/DELIVERY), referencia, orden, usuario, motivo, fecha
- Al cancelar se solicita motivo obligatorio mediante diálogo
- Lógica de stock en cancelaciones:
  - Ítems **pendiente**: sin movimiento de stock (cocina no los preparó)
  - Ítems **listo/entregado**: descuenta stock como pérdida y registra movimiento CANCELACION con cantidad negativa en historial

---

## Flujo típico de operación diaria

```
1. Admin abre la caja del día (sección Inicio → Abrir Caja)
2. Mesero ocupa una mesa → selecciona productos → guarda orden
3. Cocina ve la orden en Pantalla Cocina → marca ítems como "Listo"
4. Mesero ve la alerta (toast + sonido) → selecciona los ítems listos con checkbox → clic en **"Entregado"** para marcarlos individualmente; puede entregar plato a plato sin esperar que toda la orden esté lista
5. Al terminar → mesero o admin hace clic en "Cobrar"
6. Selecciona método de pago → confirma → el ticket se imprime automáticamente
7. El stock se descuenta automáticamente de cada insumo vendido
8. Al cierre del día → Admin cierra la caja (registra monto final)
```

---

## Instalación en nuevo equipo

1. Instalar WAMP (Apache + PHP + MySQL)
2. Copiar la carpeta del proyecto en `C:\wamp64\www\`
3. Abrir phpMyAdmin -> Crear base de datos `botanero_ventas` (o dejar que el instalador la cree)
4. Llamar a `http://localhost/botanero-ventas/api/crear_tablas.php` → crea todas las tablas y el usuario admin por defecto
5. Acceder a `http://localhost/botanero-ventas/`
6. Login: `admin@admin.com` / `admin123` (cambiar inmediatamente)
7. Ir a Configuración → ingresar datos del negocio y número de mesas

**Credenciales por defecto:**
| Campo | Valor |
|-------|-------|
| Correo | admin@admin.com |
| Contraseña | admin123 |
| Rol | admin |

---

## Impresión de tickets

El sistema usa la librería `printd` para enviar el ticket al diálogo de impresión del navegador.

**Impresora configurada:** Epson M352A (térmica, papel 80mm)
- Área imprimible usada: 72mm
- Fuente: Courier New monoespaciada, 10px
- Página: `@page { size: 80mm auto; margin: 3mm 2mm; }`
- La comanda de cocina también se imprime en 80mm con fuente 13px

**Para imprimir directo sin diálogo de confirmación:**
1. Configurar la **Epson M352A** como **impresora predeterminada** en Windows
2. Abrir el sistema con Chrome/Edge usando el flag `--kiosk-printing`:
```
"C:\Program Files\Google\Chrome\Application\chrome.exe" --kiosk-printing http://localhost/botanero-ventas
```
Con esta configuración la impresión ocurre automáticamente al cobrar, sin ningún diálogo.

---

## Base de datos — Tablas principales

| Tabla | Descripción |
|-------|-------------|
| `usuarios` | Cuentas del sistema con rol y contraseña bcrypt |
| `insumos` | Catálogo de productos con stock y precios |
| `categorias` | Categorías de productos (PLATILLO/BEBIDA) |
| `ventas` | Registro histórico de ventas cobradas |
| `insumos_venta` | Detalle de qué se vendió en cada venta |
| `ordenes_activas` | Órdenes de mesas y deliveries en curso |
| `items_orden` | Ítems de cada orden activa con estado |
| `historial_stock` | Kardex de todos los movimientos de inventario |
| `cancelaciones` | Registro de órdenes canceladas con motivo |
| `reservas` | Reservas de mesas programadas |
| `caja_diaria` | Jornadas de caja abiertas y cerradas |
| `gastos_caja` | Egresos registrados durante una jornada |
| `menu_dia` | Platos asignados a cada día de la semana |
| `informacion_negocio` | Datos del establecimiento (nombre, logo, mesas) |
| `reportes_cocina` | Reportes de faltantes registrados por cocina |

