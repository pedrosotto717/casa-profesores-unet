# **Lógica de Negocio y Flujos Finales \- Sistema Casa del Profesor UNET**

Este documento resume los requerimientos definitivos, aclaraciones y flujos de negocio para el sistema, consolidando la información de todos los audios, videos y discusiones.

## **1\. Definiciones Clave**

* **Agremiado:** Es **exactamente** un usuario con rol \= Profesor. Es el miembro principal de la Casa del Profesor.  
* **Beneficiario:** Familiar directo de un Agremiado (Profesor). **NO** son usuarios del sistema (no tienen login) y **NO PAGAN** por ningún uso de instalaciones (incluida la piscina). Se registran asociados a un Agremiado.  
* **Invitado:** Persona externa traída por un Agremiado para un evento/reserva. **SÍ PAGA** la tarifa completa por el uso de áreas.  
* **Otros Usuarios (Estudiante, etc.):** Pagan tarifa completa por uso de áreas. No tienen beneficiarios. No pagan cuota mensual.  
* **Aporte (Solvencia):** Cuota **mensual** pagada **únicamente por Agremiados (Profesores)** para mantener su estado de "Solvente".  
* **Pago por Reserva:** Monto pagado por el alquiler de un área específica (areas) para una reservation. Todos los usuarios pagan por esto, excepto la piscina para Agremiados/Beneficiarios.  
* **Factura / Recibo:** Documento (registro en BD y potencialmente visual) que representa un Aporte o un Pago por Reserva. Debe contener los detalles del monto, fecha, moneda y a qué corresponde (aporte o reserva).

## **2\. Cambios Requeridos en Base de Datos**

**Nuevas Tablas:**

1. **beneficiarios**:  
   * id (PK)  
   * agremiado\_id (FK a users donde rol \= Profesor)  
   * nombre\_completo (string)  
   * parentesco (enum: 'Cónyuge', 'Hijo/a', 'Madre', 'Padre')  
   * estatus (enum: 'Activo', 'Inactivo', default: 'Activo')  
   * created\_at, updated\_at  
2. **facturas** (o recibos):  
   * id (PK)  
   * user\_id (FK a users, la persona que paga o a quien corresponde el aporte)  
   * tipo (enum: 'Aporte Solvencia', 'Pago Reserva')  
   * monto (decimal)  
   * moneda (string/enum: 'VES', 'COP', 'USD', 'EUR')  
   * fecha\_emision (date/datetime)  
   * fecha\_pago (date/datetime, puede ser la misma que emisión)  
   * estatus\_pago (enum: 'Pagado', 'Pendiente') \- *Aunque* el flujo indica que se genera al pagar, podría *ser útil.*  
   * descripcion (string, opcional)  
   * created\_at, updated\_at

**Tablas a Modificar:**

1. **aportes** (Tabla existente):  
   * Añadir factura\_id (FK a facturas, nullable si se decide generar factura después). **Confirmado: Se debe añadir.**  
2. **areas**:  
   * Añadir monto\_hora\_externo (decimal) \- *Tarifa base/completa.*  
   * Añadir porcentaje\_descuento\_agremiado (integer, ej. 20 para 20%). *Se calculará monto\_hora\_agremiado dinámicamente.*  
   * Añadir moneda (string/enum) \- *Moneda en la que se expresa el monto\_hora\_externo.*  
3. **reservations**:  
   * Añadir factura\_id (FK a facturas, nullable). Se asigna cuando el Admin marca como pagado.  
   * Añadir estatus\_pago (enum: 'Pendiente', 'Pagado', default: 'Pendiente'). Se actualiza cuando el Admin marca como pagado.  
   * Añadir fecha\_cancelacion (datetime, nullable).  
   * Añadir monto\_devuelto (decimal, nullable).

## **3\. Flujos de Negocio Detallados**

### **3.1. Flujo de Aportes Mensuales (Solvencia \- Solo Profesores)**

1. **Admin UI:** Accede a "Gestión de Usuarios", selecciona un Agremiado (Profesor).  
2. **Admin UI:** Clic en "Gestionar Aportes".  
3. **Admin UI:** Clic en "Agregar Aporte". Ingresa monto y fecha\_aporte.  
4. **Backend:**  
   * Crea un registro en la tabla aportes con user\_id, monto, fecha\_aporte.  
   * Crea un registro en la tabla facturas con user\_id (del profesor), tipo \= 'Aporte Solvencia', monto, moneda (definir default o permitir selección), fecha\_emision/pago, estatus\_pago \= 'Pagado'.  
   * Actualiza el registro en aportes asignando el factura\_id recién creado.  
   * Recalcula y actualiza el campo solvent\_until en la tabla users para ese profesor (basado en fecha\_aporte).  
5. **Admin/Profesor UI:** El profesor puede ver su historial de aportes y facturas asociadas. El Admin puede ver todas.

### **3.2. Flujo de Reservación y Pago de Áreas**

**A. Creación de Reserva (Todos los roles con permiso):**

1. **Usuario UI:** Selecciona área, fecha/hora en el calendario.  
2. **Usuario UI:** Completa formulario (título, asistentes, notas).  
3. **Backend:** Crea registro en reservations con estatus \= 'Pendiente', estatus\_pago \= 'Pendiente'.

**B.** Aprobación y **Registro de Pago (Admin):**

1. **Admin UI:** Va a "Gestión de Reservas", ve la solicitud Pendiente.  
2. **Backend (Cálculo Costo):** Al visualizar la reserva pendiente, el backend calcula el costo:  
   * horas\_reservadas \= end\_time \- start\_time.  
   * costo\_base \= area.monto\_hora\_externo \* horas\_reservadas.  
   * **Si usuario.rol \== Profesor:**  
     * descuento \= costo\_base \* (area.porcentaje\_descuento\_agremiado / 100).  
     * costo\_final \= costo\_base \- descuento.  
   * **Si usuario.rol \!= Profesor:**  
     * costo\_final \= costo\_base.  
   * **Excepción Piscina:** Si area \== Piscina Y (usuario.rol \== Profesor O el usuario está asociado a un Beneficiario de la reserva), costo\_final \= 0\. *(Nota: Se necesita lógica para identificar si hay beneficiarios)*.  
3. **Admin UI:** Ve el costo\_final calculado y la información de la reserva. Clic en **"Aprobar"**.  
4. **Backend:** Cambia reservations.estatus a 'Aprobado'. (El estatus\_pago sigue 'Pendiente').  
5. *(Usuario paga al Admin offline)*  
6. **Admin UI:** Encuentra la reserva Aprobada. Clic en **"Marcar como Pagado"**.  
7. **Admin UI:** Se abre un modal solicitando fecha\_pago y moneda. El monto a pagar (costo\_final) ya viene pre-calculado.  
8. **Backend:**  
   * Crea un registro en facturas con user\_id, tipo \= 'Pago Reserva', monto \= costo\_final, moneda (la seleccionada), fecha\_pago, estatus\_pago \= 'Pagado'.  
   * Actualiza el registro en reservations asignando el factura\_id y cambiando estatus\_pago a 'Pagado'.  
   * (Opcional) Genera/envía notificación/recibo al usuario.  
9. **Admin/Usuario UI:** La reserva ahora aparece como "Aprobada y Pagada" con detalles del pago visibles.

### **3.3. Gestión de Beneficiarios (Admin)**

1. **Admin UI:** Va a "Gestión de Usuarios", selecciona un Agremiado (Profesor).  
2. **Admin UI:** Accede a la nueva pestaña/sección "Beneficiarios".  
3. **Admin UI:** Ve la lista de beneficiarios activos e inactivos.  
4. **Admin UI:** Puede "Agregar Beneficiario" (ingresando nombre\_completo, parentesco).  
5. **Admin UI:** Puede "Editar" (cambiar nombre, parentesco).  
6. **Admin UI:** Puede "Desactivar/Activar" (cambiar estatus) un beneficiario existente.

## **4\. Otros Requerimientos Resumidos**

* **Gestión Academias:** Implementar UI (Admin) para CRUD de academy\_students (estudiantes por academia).  
* **Dashboard:** Añadir reporte de **Ingresos Totales** (filtrable por fecha) y reporte de **Uso de Áreas por Agremiado** (filtrable por agremiado y fecha).  
* **Documentos:** Cargar Estatutos, Normas y Planillas (solicitar a Prof. Iraima).  
* **Reservas UI:** Ajustar campo "Número de Beneficiarios" a "Número de Asistentes" o desglosar (