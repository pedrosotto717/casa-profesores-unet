# Actualización de Estatus de Pago - Nuevo Valor "Gratis"

## Resumen

Se ha agregado un nuevo valor `'Gratis'` al campo `estatus_pago` de las reservaciones para distinguir entre reservaciones pagadas y gratuitas de agremiados.

## Cambios en el Backend

### Nuevo Valor de Estatus de Pago

**Valor agregado:** `'Gratis'`

**Valores posibles de `estatus_pago`:**
- `'Pendiente'`: Requiere pago
- `'Pagado'`: Ya fue pagado
- `'Gratis'`: Reservación gratuita (no requiere pago)

### Cuándo se Usa

El estatus `'Gratis'` se asigna automáticamente cuando:
- Un **profesor** (`role: 'profesor'`) crea una reservación
- En un área que tiene `es_gratis_agremiados: true` (como la piscina)

### Comportamiento del Sistema

1. **Factura automática**: Se genera una factura con `monto: 0.00`
2. **Estatus de reservación**: Sigue siendo `'Pendiente'` (requiere aprobación del admin)
3. **Estatus de pago**: Se marca como `'Gratis'`
4. **Notificaciones**: Se envían a los admins para aprobación

## Cambios Requeridos en el Frontend

### 1. Actualizar Tipos TypeScript

```typescript
// Antes
type EstatusPago = 'Pendiente' | 'Pagado';

// Después
type EstatusPago = 'Pendiente' | 'Pagado' | 'Gratis';
```

### 2. Actualizar Función de Color de Badge

```typescript
const getStatusColor = (estatusPago: EstatusPago) => {
  switch (estatusPago) {
    case 'Pendiente':
      return 'warning'; // Amarillo
    case 'Pagado':
      return 'success'; // Verde
    case 'Gratis':
      return 'info'; // Azul
    default:
      return 'secondary';
  }
};
```

### 3. Ocultar Botón "Marcar como Pagado"

```typescript
// Ocultar el botón cuando estatus_pago es 'Gratis'
{estatusPago !== 'Gratis' && (
  <Button onClick={handleMarkAsPaid}>
    Marcar como Pagado
  </Button>
)}
```

### 4. Actualizar Badge de Estatus

```typescript
<Badge color={getStatusColor(estatusPago)}>
  {estatusPago === 'Gratis' ? 'Gratis' : estatusPago}
</Badge>
```

## Endpoints Afectados

Los siguientes endpoints ahora pueden devolver `estatus_pago: 'Gratis'`:

- `GET /api/v1/reservations` - Lista de reservaciones
- `GET /api/v1/reservations/{id}` - Reservación específica
- `GET /api/v1/me/reservations` - Mis reservaciones

## Ejemplo de Respuesta

```json
{
  "success": true,
  "data": {
    "id": 123,
    "status": "Pendiente",
    "estatus_pago": "Gratis",
    "estatus_pago_label": "Gratis",
    "area": {
      "name": "Piscina"
    },
    "factura": {
      "id": 456,
      "monto": 0.00,
      "moneda": "USD",
      "estatus_pago": "Pagado"
    }
  }
}
```

## Archivos del Frontend a Modificar

1. **`src/services/authService.ts`**
   - Actualizar tipo `EstatusPago`

2. **`src/pages/AdminReservations.tsx`**
   - Actualizar función `getStatusColor`
   - Ocultar botón "Marcar como Pagado" para estatus 'Gratis'
   - Actualizar badges de visualización

3. **`src/pages/UserReservations.tsx`**
   - Actualizar badge de estatus de pago
   - Ocultar botón "Ver Factura" si no hay factura (para reservas gratuitas)

4. **`src/pages/Facturas.tsx`**
   - Posiblemente agregar filtro para excluir o incluir facturas de reservas gratuitas

## Notas Importantes

- Las reservaciones con `estatus_pago: 'Gratis'` **SÍ requieren aprobación del administrador**
- El botón "Marcar como Pagado" debe estar **oculto** para estatus 'Gratis'
- El badge "Gratis" debe ser **azul** para distinguirlo visualmente
- La factura se genera automáticamente con monto 0.00

## Fecha de Implementación

**Backend:** 29 de enero de 2025 - ✅ COMPLETADO
**Frontend:** Pendiente de implementación
