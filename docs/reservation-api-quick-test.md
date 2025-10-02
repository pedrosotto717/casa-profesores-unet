# Guía Rápida - Testing Endpoints de Reservas

> **Fecha actual**: 1 de octubre 2025, 9:45 PM

## Endpoints Creados

### 1. **POST** `/api/v1/reservations` - Crear reserva
### 2. **GET** `/api/v1/reservations` - Listar reservas  
### 3. **PUT** `/api/v1/reservations/{id}` - Actualizar reserva
### 4. **POST** `/api/v1/reservations/{id}/cancel` - Cancelar reserva
### 5. **POST** `/api/v1/reservations/{id}/approve` - Aprobar reserva (admin)
### 6. **POST** `/api/v1/reservations/{id}/reject` - Rechazar reserva (admin)
### 7. **GET** `/api/v1/reservations/availability` - Disponibilidad (público)

---

## Áreas Disponibles (del Seeder)

| ID | Área | Reservable | Capacidad | Horario |
|----|------|------------|-----------|---------|
| 1 | Piscina | ✅ | Variable | Mar-Dom 9:00-17:00 |
| 2 | Salón Primavera | ✅ | 100 | Lun-Dom 10:00-00:00 |
| 3 | Salón Pradera | ✅ | 150 | Lun-Dom 10:00-00:00 |
| 4 | Auditorio Paramillo | ✅ | 100 | Lun-Dom 10:00-00:00 |
| 5 | Kiosco Tuquerena | ✅ | 30 | Lun-Dom 10:00-00:00 |
| 6 | Kiosco Morusca | ✅ | 30 | Lun-Dom 10:00-00:00 |
| 7 | Sauna | ✅ | 6 | Lun-Vie 15:00-19:00 |
| 8 | Cancha de usos múltiples | ✅ | Variable | Lun-Dom 6:00-22:00 |
| 9 | Cancha de bolas criollas | ✅ | Variable | Lun-Dom 6:00-22:00 |
| 10 | Mesa de pool (billar) | ✅ | 4 | Lun-Dom 8:00-23:00 |
| 11 | Mesa de ping pong | ✅ | 4 | Lun-Dom 8:00-23:00 |
| 12 | Peluquería (con previa cita) | ✅ | 2 | Lun-Sáb 8:00-18:00 |

---

## JSON Payloads para Postman

### 1. Crear Reserva - Piscina (mañana)
```json
{
  "area_id": 1,
  "starts_at": "2025-10-02T10:00:00Z",
  "ends_at": "2025-10-02T12:00:00Z",
  "title": "Sesión de natación matutina",
  "notes": "Entrenamiento personal de natación"
}
```

### 2. Crear Reserva - Salón Primavera (evento)
```json
{
  "area_id": 2,
  "starts_at": "2025-10-05T18:00:00Z",
  "ends_at": "2025-10-05T22:00:00Z",
  "title": "Celebración de cumpleaños",
  "notes": "Fiesta de cumpleaños para 50 personas"
}
```

### 3. Crear Reserva - Sauna (tarde)
```json
{
  "area_id": 7,
  "starts_at": "2025-10-03T16:00:00Z",
  "ends_at": "2025-10-03T16:15:00Z",
  "title": "Sesión de sauna",
  "notes": "Turno de 15 minutos en sauna"
}
```

### 4. Actualizar Reserva
```json
{
  "starts_at": "2025-10-02T14:00:00Z",
  "ends_at": "2025-10-02T16:00:00Z",
  "title": "Sesión de natación actualizada",
  "notes": "Cambio de horario por disponibilidad"
}
```

### 5. Cancelar Reserva
```json
{
  "reason": "Cambio de planes, no podré asistir"
}
```

### 6. Rechazar Reserva (Admin)
```json
{
  "reason": "Conflicto con sesión de academia de natación"
}
```

---

## Query Parameters para GET

### Listar Reservas (Admin)
```
GET /api/v1/reservations?user_id=1&area_id=1&status=pendiente&from=2025-10-01&to=2025-10-07&search=juan&page=1&per_page=15
```

### Ver Disponibilidad - Piscina (próxima semana)
```
GET /api/v1/reservations/availability?area_id=1&from=2025-10-06&to=2025-10-12&slot_minutes=60
```

### Ver Disponibilidad - Salón Primavera (fin de semana)
```
GET /api/v1/reservations/availability?area_id=2&from=2025-10-04&to=2025-10-06
```

---

## Headers Necesarios

```
Authorization: Bearer {tu_token_aqui}
Content-Type: application/json
Accept: application/json
```

---

## Notas Importantes

- **Fecha actual**: 1 octubre 2025, 9:45 PM
- **Roles permitidos**: Profesor, Estudiante, Invitado (status = solvente)
- **Admin**: Solo para aprobar/rechazar
- **Fechas**: Formato ISO-8601 con timezone UTC
- **Anticipación mínima**: Configurable (por defecto 24h)
- **Horarios**: Según seeder de áreas
