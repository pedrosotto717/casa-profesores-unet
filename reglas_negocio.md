# **Resumen Final de Requerimientos y Lógica de Negocio**

Este documento consolida y refina los requerimientos para el sistema de la Casa del Profesor UNET, basándose en todos los audios, el video y las aclaraciones proporcionadas. Define la lógica de negocio clave y las acciones a tomar.

## **1\. Gestión de Personas: Agremiados, Beneficiarios e Invitados**

**1.1. Agremiados (Profesores):**

* **Confirmado:** El término "Agremiado" se refiere exclusivamente a los usuarios con el rol de **"Profesor"**. Son los miembros principales que pagan una cuota mensual (Aporte de Solvencia).  
* **Acción:** Usar el rol existente. Asegurar que la interfaz use la terminología "Agremiado" o "Profesor" según corresponda.

**1.2. Beneficiarios (Grupo Familiar):**

* **Implementación NUEVA.** Separada de Usuarios e Invitados.  
* **Definición:** Familiares directos del Agremiado (Profesor). No necesitan ser usuarios del sistema (sin login).  
* **Regla de Negocio CLAVE:** **NO PAGAN** por el uso de ninguna instalación, incluida la piscina.  
* **Acciones:**  
  * **BD:** Crear tabla beneficiarios (id, agremiado\_id (FK a users), nombre\_completo, parentesco (enum: 'Cónyuge', 'Hijo/a', 'Madre', 'Padre'), estatus (enum: 'Activo', 'Inactivo')).  
  * **UI (Admin):** Crear interfaz para que el Admin pueda **CRUD** (Crear, Leer, Actualizar, Desactivar) beneficiarios asociados a cada Agremiado. Incluir el campo parentesco y estatus.  
  * **UI (Profesor):** El profesor debe poder *visualizar* sus beneficiarios registrados.

**1.3. Invitados:**

* **UI Faltante.** Debe implementarse.  
* **Definición:** Personas externas traídas por un Agremiado.  
* **Regla de Negocio CLAVE:** **SÍ PAGAN** por el uso de las instalaciones (tarifa externa/completa).  
* **Acciones:**  
  * **UI (Profesor):** Crear interfaz donde el profesor pueda registrar/invitar a personas externas para un evento/reserva específica.  
  * **Control:** Implementar un límite o control sobre la cantidad de invitados permitidos (según reglamento).

**1.4. Otros Usuarios (Estudiantes, Externos, etc.):**

* **Regla de Negocio:** Pagan la tarifa externa por el uso de áreas. No tienen beneficiarios. No pagan aporte de solvencia.

## **2\. Gestión Financiera: Aportes, Cobros, Pagos y Recibos**

**2.1. Aportes de Solvencia (Cuota Mensual):**

* **Aplicable:** **Solo para Agremiados (Profesores)**.  
* **UI Faltante (Admin):** Implementar la interfaz de **"Gestión de Aportes"** como se definió previamente:  
  * Acceso desde la lista de Agremiados.  
  * Permitir al Admin **CRUD** aportes (monto, fecha\_aporte).  
  * El backend debe recalcular solvent\_until automáticamente.  
* **Terminología:** Usar siempre "Aporte" en lugar de "Pago" para esta cuota mensual y en general para evitar connotaciones fiscales.

**2.2. Cobro por Uso de Áreas:**

* **Acción (Modificación BD):** Añadir precios a la tabla areas.  
  * costo\_agremiado (decimal): Tarifa con descuento para el Profesor.  
  * costo\_externo (decimal): Tarifa completa para Invitados, Estudiantes, otros.  
  * **Descuento:** Se puede manejar con un porcentaje de descuento fijo para agremiados sobre la tarifa externa.  
* **Regla de Negocio (Excepción):** La **Piscina** es **GRATIS** para Agremiados y sus Beneficiarios (uso individual). Para el resto (Invitados, Estudiantes) sí tiene costo.  
* **UI (Admin):** La "Gestión de Áreas" debe permitir al Admin configurar estos precios/porcentajes.

**2.3. Flujo de Pago por Reserva y Recibos:**

* **Implementación NUEVA.**  
* **Acciones:**  
  * **BD:** Crear tabla pagos\_reserva (id, reservation\_id (FK), monto\_pagado, fecha\_pago, moneda, notas (opcional)).  
  * **UI (Admin \- Flujo):** Al **Aprobar** una reserva en "Gestión de Reservas", el sistema debe abrir un modal para que el Admin registre: monto\_pagado, fecha\_pago, moneda (permitir seleccionar: Bolívares, Pesos, Dólares, Euros).  
  * **UI (Admin/Profesor):** La vista de detalle de una reserva aprobada debe mostrar la información del pago registrado.  
  * **Generación de Recibo:** Implementar una función simple para visualizar/imprimir un recibo básico con los detalles del pago.  
  * **Notificación:** Enviar una notificación (email o dentro del sistema) al usuario confirmando el pago y la aprobación de la reserva.

## **3\. Otras Modificaciones y Funcionalidades**

**3.1. Gestión de Academias:**

* **UI Faltante (Admin):** Crear la interfaz (CRUD) para **gestionar los estudiantes/participantes** de cada academia. Debe ser visible para el Admin.

**3.2. Dashboard y Reportes:**

* **Acción:** Añadir widgets/secciones:  
  * **Reporte Financiero:** Total de Ingresos por Aportes y por Pagos de Reserva (con filtro de fecha).  
  * **Reporte de Uso:** Filtro para ver cuántas veces un **Agremiado específico** ha usado/reservado qué áreas en un periodo.

**3.3. Documentos:**

* **Acción:** Cargar los documentos oficiales (Estatutos, Normas, **Planillas** \- pedir a la Prof. Iraima). Asegurar que la visibilidad (Público/Privado) funcione correctamente.

**3.4. Reservas:**

* **Campo "Número de Beneficiarios":** Cambiar etiqueta a algo más genérico como "Nro. Asistentes" o tener campos separados para "Nro. Beneficiarios" y "Nro. Invitados" para calcular el costo correctamente.  
* **Registro de Cancelación:** Registrar fecha\_cancelacion y monto\_devuelto (si aplica) en la tabla reservations o pagos\_reserva.

**3.5. Chat:**

* **Confirmado:** El flujo de chat entre Admin y Profesor funciona como se mostró en el video. No se requieren cambios funcionales.

## **4\. Tareas Adicionales (No Código)**

* **Tesis:** Actualizar la imagen del Modelo de Base de Datos para incluir la tabla aportes y las nuevas tablas (beneficiarios, pagos\_reserva).