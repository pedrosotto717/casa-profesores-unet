# Reglamento de Uso de la Casa del Profesor Universitario (CPU) — **Especificación de Lógica de Negocio para Backend**

> **Propósito**\
> Traducir el **Reglamento de Uso de la CPU (febrero 2017)** a reglas, restricciones, flujos y configuraciones **implementables** en el backend del proyecto (Laravel 12). Este archivo sirve como **base de conocimiento (Specs)** para Cursor AI y para desarrolladores.

> **Alcance**\
> • Cobertura: acceso e identidad (agremiado/afiliado/invitado), invitaciones, reservas/uso de áreas, faltas y sanciones, reglas por área (sauna/vapor, piscina, parque infantil), contratación de locales (eventos), horarios y capacidades, disposiciones generales.\
> • Enfoque: *no* es un documento legal; es una **formalización técnica** con defaults configurables.

---

## 1) Vocabulario y roles (mapeo al sistema)

| Término reglamento     | Descripción                                                                                | Rol del sistema (DB `users.role`)      |
| ---------------------- | ------------------------------------------------------------------------------------------ | -------------------------------------- |
| **Agremiado**          | Profesor afiliado al CPU/APUNET, responsable de su núcleo familiar e invitados en la sede. | `docente`                              |
| **Afiliado**           | Persona con condición de afiliación válida (no necesariamente docente).                    | `docente` o `administrador` según caso |
| **Invitado**           | Tercero autorizado por un agremiado (responsabilidad solidaria).                           | `invitado`                             |
| **Junta Coordinadora** | Autoridad operativa (2016–2018 en el reglamento).                                          | `administrador`                        |
| **Concesionario**      | Operador del restaurante.                                                                  | (externo; sin acceso al sistema)       |

**Principios transversales del reglamento** (a reflejar en **políticas y validaciones**):

- Responsabilidad del **agremiado** por conducta y daños de **familiares e invitados** durante su permanencia (registro de “**sponsor**” y trazabilidad de acciones/ingresos).
- **Conducta** adecuada y **indumentaria** apropiada en áreas; **portación de armas prohibida**; el uso de instalaciones es **bajo riesgo del usuario** (consentimientos/avisos).
- **Verificación de identidad**: carnet o cédula para validar agremiado al ingreso.

---

## 2) Invitaciones y control de acceso

**Reglas clave**

1. **Límite de invitados** por agremiado: **máximo 5** simultáneos, adicionales al grupo familiar.
2. **Acompañamiento**: los invitados **deben permanecer** acompañados por el agremiado que los registró.
3. **Registro**: se debe registrar **nombre y cédula** de cada invitado antes del acceso.
4. **Responsabilidad**: faltas, daños o perjuicios de invitados se atribuyen al **agremiado sponsor**.
5. **Restaurante (excepción)**: acceso **público** sin restricciones de invitación, **tiempo máximo 150 min** dentro del recinto y **sin libre tránsito** al resto de áreas.

**Implicaciones de backend**

- Entidad `invitations` con `inviter_user_id`, `invitee_user_id?`, `status`, `expires_at`.
- **Política**: denegar creación de invitación si el sponsor supera `guest_limit=5` activos en el día/turno.
- **Check-in**: crear `visit_logs` (si se implementa) con relación `guest → sponsor`, hora de ingreso/salida, y marcar acompañamiento (checkbox de declaración).
- **Restaurante**: modelar como `area` **pública**: no requiere invitación; crear regla de **tiempo máximo** por visita (150 min) y bloquear acceso a otras áreas con ese ticket.

---

## 3) Faltas y sanciones

**Clasificación**

- **Leves**: ofensas personales, palabras obscenas, llegar ebrio, ofensas a las damas.
- **Graves**: riña con o sin armas, juegos de azar, portar armas, daños al patrimonio, y otras que la Junta determine.

**Implicaciones de backend**

- Catálogo `violations` con `severity = ['leve','grave']`, `description`, `area_id?`.
- Flujo: `report → review → sanction`. Sanciones sugeridas: **amonestación**, **suspensión temporal de acceso**, **bloqueo de reservas**, **pase a comité/ética**.
- Guardar **responsable solidario** (`sponsor_user_id`) si el infractor es invitado.
- **Reglas**: al registrar **grave**, bloquear reservas del usuario (e invitados asociados) por `N` días (configurable) y notificar a administración.

---

## 4) Reglas por área

### 4.1 Sauna y vapor

**Acceso y uso**

- **Exclusivo** para agremiados (no invitados).
- **Higiene obligatoria** (ducha, toalla para sentarse; sin calzado; entrada por vestuario).
- **Turnos** por **reserva local** (planilla) de **15 min** con **máx. 6 personas** por turno.
- **Salud**: contraindicaciones (hipertensión grave, cardiovasculares, embarazo, etc.).
- **Prohibido**: alimentos/bebidas, fumar, cremas/aceites, dispositivos electrónicos.
- **Horario**: **L–V 3:00 pm–7:00 pm**.

**Backend**

- Marcar `area: sauna` con **reglas**: `role_whitelist = ['docente']`, `slot_minutes = 15`, `slot_capacity = 6`, `schedule = Mon–Fri 15:00–19:00`.
- Al reservar, requerir **aceptación** de **consentimiento** de salud.
- Validar que todas las personas del turno poseen rol permitido.

### 4.2 Piscinas

**Normas principales**

- **Indumentaria**: traje de baño (gorro opcional). **Ducha obligatoria** previa; sin calzado en andenes.
- **Prohibido** dentro de la piscina/bordes: comer, beber, **vidrio**, bloqueador/bronceador en el agua.
- **Seguridad**: no correr/empujar/saltos peligrosos; seguir indicaciones de salvavidas.
- **Menores**: **< 15 años** siempre **acompañados** por un adulto.
- **Arancel de invitado** para uso de piscina (si aplica).
- **Horario recreativo**: **Mar–Dom 9:00 am–5:00 pm**.
- **Clases de natación**: reservar **50 % del vaso** para la escuela; ingreso 5 min antes del horario.
- **Clima**: suspender por tormenta eléctrica.

**Backend**

- `area: piscina` con `schedule = Tue–Sun 09:00–17:00`.
- Regla `minors_supervision_required = true (age < 15)`.
- Bandera `storm_shutdown = true` (permite **cierre administrativo** de agenda por alerta meteo).
- **Bloques** específicos para `academies` que tomen **50 %** de capacidad.
- Validar `no_glass`, `no_food_drink_in_pool` como **políticas de cartel/consentimiento** (no bloqueantes a nivel de API, pero auditables).

### 4.3 Parque infantil

- Uso para **menores de 10 años**; **calzado obligatorio**; **supervisión adulta** permanente.
- Cuidado de equipamiento; reportar averías; limpieza (sin vidrio).
- **Responsabilidad** recae en padres/representantes.

**Backend**

- `area: parque_infantil` con regla `max_age = 10`, `adult_supervision_required = true`.

---

## 5) Instalaciones, capacidades y horarios

**Áreas contratables (eventos)**

- **Terrazas techadas**: Salón **Primavera** (cap. 100), Salón **Pradera** (cap. 150).
- **Terraza no techada**: **La Potrera** (cap. 60).
- **Parrillero central**: **La Blanca** (cap. 40).
- **Kioscos**: **Tuquerena** (cap. 30), **Morusca** (cap. 30).
- **Auditorio**: Salón **Paramillo** (cap. 100).
- **Salón La Bermeja** (cap. 30).

**Horarios operativos (por defecto)**

- **Restaurante** (concesionario): según convenio.
- **Festas infantiles**: 10:00 am–6:00 pm.
- **Festas de adultos**: 7:00 pm–2:00 am (máx.).
- **Funcionamiento general (sin evento)**: 10:00 am–12:00 am (máx.).
- **Piscina (recreativo)**: 10:00 am–5:00 pm (ver §4.2).

**Backend**

- Sembrar `areas` y `services` con **capacidades** y **horarios** default.
- En reservas, validar `capacity` y **horas extra** (flag `overtime_allowed=true` con **tarifa** configurable).
- Exponer **catálogo** de áreas con **capacidad**, **horario** y **reglas** para frontend.

---

## 6) Contratación de locales (eventos)

**Reglas de contratación**

1. Sujetos: agremiados/afiliados; instituciones o terceros con aprobación de la Junta.
2. **Canon**: terceros pagan **≥ 3×** el monto del agremiado. **Pago por adelantado** del canon correspondiente.
3. **Lista de invitados** obligatoria para aprobación del evento.
4. **Prohibido**: uso de **papelillos**.
5. **Límite**: solo **un local por día** por contratante.
6. **Mobiliario/enseres**: retirar a **primera hora del día siguiente**; si tras **48 h** no retiran, se dispone y se cobra **gasto por remoción**.
7. **Mantenimiento extra** post‑evento: puede aplicarse **cuota especial** extraordinaria.
8. Cláusula “**primero en llegar, primero en ser servido**” sujeta a disponibilidad; firma del **contrato** 10 días antes (para eventos sociales).

**Backend**

- `reservations` con **workflow**: `borrador → solicitada → aprobada/rechazada → cerrada`.
- Validaciones: `one_area_per_day_per_customer`, `guest_list_required`, `no_confetti=true`, `prepayment_required=true`, `third_party_multiplier=3`.
- Campos: `decision_reason`, `reviewed_at`, `approved_by`.
- Post‑evento: registrar `extra_cleaning_fee` si aplica y **bloquear** nuevas reservas si hay **deuda**.

> **Nota de terminología**: el reglamento usa “**alquiler/canon de arrendamiento**”. En el sistema, por lineamientos institucionales, exponer como **“uso de área con aporte especial”** o **“cuota por uso”** para mantener carácter **no lucrativo**.

---

## 7) Disposiciones generales (cumplimiento)

- La CPU **no se responsabiliza** por objetos dejados o por accidentes/lesiones: mostrar **disclaimer** en flujos de reserva y check‑in (checkbox de aceptación).
- **Prohibición total** de armas: agregar regla `weapon_free_zone=true` para cartel y protocolo de acceso.
- Cierre administrativo: permitir a `administrador` **cerrar áreas** por mantenimiento/limpieza o seguridad (ej. sauna y vapor **periódicamente**).

---

## 8) Plantillas de configuración (JSON/YAML)

### 8.1 `cpu-config.json`

```json
{
  "guest_limit": 5,
  "restaurant": {
    "is_public": true,
    "max_duration_minutes": 150,
    "restricted_to_restaurant_area": true
  },
  "areas": {
    "sauna": {
      "role_whitelist": ["docente"],
      "slot_minutes": 15,
      "slot_capacity": 6,
      "schedule": [["Mon","Fri","15:00","19:00"]],
      "consent_required": ["health_sauna_risks"],
      "prohibitions": ["food_drink","smoking","creams_oils","electronics"]
    },
    "piscina": {
      "schedule": [["Tue","Sun","09:00","17:00"]],
      "rules": {
        "attire": {"swimsuit_required": true, "cap_optional": true},
        "shower_before_entry": true,
        "no_glass": true,
        "no_food_drink_in_pool": true,
        "unsafe_behaviors": ["running","pushing","backflips"],
        "minors": {"age_lt": 15, "adult_supervision_required": true},
        "storm_shutdown": true
      },
      "academy_allocation": {"percent_capacity_reserved": 50, "checkin_lead_minutes": 5}
    },
    "parque_infantil": {
      "max_age": 10,
      "adult_supervision_required": true,
      "footwear_required": true
    }
  },
  "events": {
    "third_party_price_multiplier": 3,
    "prepayment_required": true,
    "guest_list_required": true,
    "one_area_per_day_per_contractor": true,
    "no_confetti": true,
    "overtime": {"allowed": true, "rate_per_hour": 0},
    "equipment_removal_hours": 48,
    "extra_cleaning_fee_allowed": true
  },
  "compliance": {
    "weapon_free_zone": true,
    "disclaimers": ["no_liability_objects","no_liability_accidents"]
  }
}
```

### 8.2 Semillas recomendadas (sin cambios a `database_structure.md`)

- `areas`: Sauna, Piscina, Salón Primavera, Salón Pradera, La Potrera, La Blanca, Kiosco Tuquerena, Kiosco Morusca, Salón Paramillo, Salón La Bermeja, Parque Infantil, Restaurante.
- `services`: “Reserva [Área]” (una por área reservable).
- `documents`: publicar “Reglamento CPU – 2017 (PDF)” con `visibility='publico'`.

---

## 9) Validaciones y políticas (cheat‑sheet para backend)

- **Invitaciones**: `count(active_invites_by_sponsor_today) ≤ guest_limit`.
- **Check‑in restaurante**: `visit.duration ≤ 150` y `visit.scope == 'restaurante'`.
- **Reserva sauna**: `user.role ∈ role_whitelist` y `slot_capacity` no superado.
- **Reserva piscina**: respetar `schedule`; bloquear por `storm_shutdown`; respetar **reserva de capacidad** para academias.
- **Eventos**: requerir `prepayment` y `guest_list`; aplicar `price * third_party_price_multiplier` si `contractor_type == 'tercero'`; **un área por día**.
- **Sanciones**: `violation.severity == 'grave' → user.booking_block_until = now + N días` (configurable).
- **Armas**: denegar acceso si `weapons_detected=true` (por protocolo de seguridad).
- **Cierres**: permitir a admin levantar `area_closure` temporal con motivo.

---

## 10) Riesgos y notas de implementación

- Varias normas son **conductuales** (p. ej., “no correr”): se implementan como **avisos/consentimientos** y **auditoría**, no como bloqueos automáticos.
- **Edades**: requerir fecha de nacimiento en `profiles` para validar `minor`.
- **Acompañamiento**: operacionalmente se verifica en control de acceso; en el sistema, capturar **declaración** y **vincular check‑ins** (sponsor/invitado).
- **Terminología**: frontend debe usar **“aportes/cuotas”** en vez de “alquiler”.
- **Meteorología**: `storm_shutdown` se activa manualmente por admin (o integración futura).
- **Horarios**: permitir configuración por temporada; usar TZ local.

---

## 11) Trazabilidad y auditoría

- Registrar en `audit_logs`: creación/cambio de estado de **invitaciones**, **reservas**, **cierres de área**, **violations/sanctions**, **pagos/cargos** post‑evento.
- Adjuntar evidencia (fotos/informe) en reportes de faltas.
- Informes: ocupación por área, uso por tipo de contratante, incidencias por severidad, bloqueo por sanciones.

---

## 12) Apéndice de fuentes (resumen)

- **Deberes/derechos, invitaciones, responsabilidad** del agremiado; **excepción restaurante** y límite **150 min**.
- **Faltas** leves/graves y sancionatoria.
- **Sauna/vapor**: exclusividad, higiene, turnos 15 min, 6 personas, horario L–V 3–7 pm.
- **Piscina**: indumentaria/ducha, prohibiciones, menores < 15 con adulto, horario Mar–Dom 9–17, 50 % para escuela, cierre por tormentas.
- **Parque infantil**: < 10 años, calzado, supervisión.
- **Instalaciones/capacidades** y **horarios** por tipo de evento.
- **Contratación**: sujetos, canon (3×), pre‑pago, lista de invitados, no confetti, un local/día, retiro de enseres en 48 h, cuota extra de mantenimiento.
- **Disposiciones generales**: no responsabilidad por objetos/accidentes, prohibición de armas.

> **Estado**: Aprobado para **MVP**. Cambios en reglamento deberán versionarse en este archivo y en `cpu-policy.json`.

