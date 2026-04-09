# Sugerencias de Diseño UX para "La Lumbrera"

Como sistema orientado a la operación en restaurante (donde la velocidad y la claridad bajo presión son vitales), estas son las principales recomendaciones de experiencia de usuario (UX) para llevar la aplicación al siguiente nivel:

## 1. 🚦 Código de Colores de Urgencia en Cocina/Parrilla completado!
Implementar un **sistema de semáforo** automático para las comandas en lugar de solo parpadear:
*   **0 a 10 min:** Tarjeta Verde o Normal (A tiempo)
*   **11 a 20 min:** Tarjeta con borde Naranja (Atención/Advertencia)
*   **+20 min:** Tarjeta Roja o resaltada bruscamente (Crítico)
*   **Beneficio UX:** Los cocineros no tienen que procesar mentalmente la hora; su cerebro identifica inmediatamente el color rojo y priorizan ese plato de forma instintiva.

## 2. 🦴 "Skeleton Loaders" en lugar de Spinners completado!
Reemplazar los círculos de carga (spinners de Buefy) que bloquean pantallas completas como el Dashboard o la lista de Mesas.
*   **Sugerencia:** Utilizar "Skeleton screens" o animaciones de carga estructurales. Es decir, mostrar la forma de las tarjetas (en color gris/pulsante) mientras los datos llegan desde la API.
*   **Beneficio UX:** Hace que el sistema se sienta psicológicamente más rápido y moderno (estándar actual usado por YouTube, Facebook, etc.).

## 3. 🎨 "Empty States" Amigables (Estados Vacíos) completado!
El evitar pantallas en blanco, ceros o tablas vacías cuando no hay información para mostrar.
*   **Sugerencia:** Mostrar ilustraciones limpias e iconos alegres con mensajes motivacionales. Por ejemplo:
    *   *En la cocina (Sin comandas):* "¡Todo servido! Tómense un respiro, equipo" + Ícono de una olla brillante.
    *   *Ventas del Día (En cero):* "Aún no hay ventas. ¡Vamos por esos primeros clientes de hoy!"
*   **Beneficio UX:** Remueve la incertidumbre de fallo del sistema y genera micro-momentos de recompensa emocional en los empleados.

## 4. ⌨️ Atajos de Teclado (Keyboard Shortcuts) completado!
Acelerar el uso del sistema de punto de venta físico sin depender tanto del mouse/pantalla táctil.
*   **Sugerencia:** Añadir combinaciones de teclas (hotkeys) universales. Ej: 
    *   Presionar `F2` en cualquier lado para abrir el modal de Ordenar.
    *   `Enter` para confirmar y enviar a Cocina rápidamente.
    *   `Esc` para cerrar modales y cancelar.
*   **Beneficio UX:** Reduce fuertemente el tiempo invertido por transacción o comanda, ideal para horas de alto flujo de clientes.

## 5. 🔊 Microinteracciones (Notificaciones de Sonido) completado!
Mejorar la capacidad de percepción de los operadores ocupados en tareas físicas.
*   **Sugerencia:** Reproducir un sonido discreto y corto (ej. una campana de recepción 🛎️) estrictamente cuando **ingresa un nuevo pedido** a la pantalla de cocina o parrilla.
*   **Beneficio UX:** Libera a los cocineros y parrilleros de la obligación de estar viendo el monitor fijamente. El sistema trabaja para ellos alertándoles auditivamente.
