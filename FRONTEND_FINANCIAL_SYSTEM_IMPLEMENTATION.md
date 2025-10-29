# Guía de Implementación del Sistema Financiero - Frontend

## 📋 Resumen del Sistema

El sistema financiero de la Casa del Profesor UNET incluye:

1. **Gestión de Aportes Mensuales** - Solo para profesores/agremiados
2. **Sistema de Facturas** - Para aportes y pagos de reservas
3. **Cálculo de Costos de Reservas** - Con descuentos para agremiados
4. **Gestión de Pagos** - Marcado de reservas como pagadas

## 🔗 Endpoints Disponibles

### Aportes (Solo Profesores/Agremiados)
```http
GET    /api/v1/aportes              # Listar aportes del usuario
POST   /api/v1/aportes              # Crear aporte (admin)
GET    /api/v1/aportes/{id}         # Ver aporte específico
PUT    /api/v1/aportes/{id}         # Actualizar aporte (admin)
DELETE /api/v1/aportes/{id}         # Eliminar aporte (admin)
```

### Facturas
```http
GET    /api/v1/me/facturas          # Mis facturas (usuario autenticado)
GET    /api/v1/facturas/{id}        # Ver factura específica
GET    /api/v1/facturas             # Todas las facturas (admin)
GET    /api/v1/users/{id}/facturas  # Facturas de usuario específico (admin)
```

### Reservas y Pagos
```http
GET    /api/v1/reservations                    # Listar reservas
POST   /api/v1/reservations                    # Crear reserva
POST   /api/v1/reservations/{id}/approve       # Aprobar reserva (admin)
POST   /api/v1/reservations/{id}/reject        # Rechazar reserva (admin)
POST   /api/v1/reservations/{id}/mark-as-paid  # Marcar como pagada (admin)
```

### Áreas (con Pricing)
```http
GET    /api/v1/areas                # Listar áreas con precios
GET    /api/v1/areas/{id}           # Ver área específica con pricing
```

## 📊 Estructura de Datos

### Aporte
```typescript
interface Aporte {
  id: number;
  user_id: number;
  amount: number;
  aporte_date: string;
  factura_id: number | null;
  created_at: string;
  updated_at: string;
  user?: User;
  factura?: Factura;
}
```

### Factura
```typescript
interface Factura {
  id: number;
  user_id: number;
  tipo: 'Aporte Solvencia' | 'Pago Reserva';
  tipo_label: string;
  monto: number;
  moneda: string;
  fecha_emision: string;
  fecha_pago: string | null;
  estatus_pago: 'Pagado' | 'Pendiente';
  estatus_pago_label: string;
  descripcion: string | null;
  created_at: string;
  updated_at: string;
  user?: User;
}
```

### Reserva (con campos de pago)
```typescript
interface Reservation {
  id: number;
  user: User;
  area: Area;
  starts_at: string;
  ends_at: string;
  status: 'pendiente' | 'aprobada' | 'rechazada' | 'cancelada' | 'completada' | 'expirada';
  status_label: string;
  estatus_pago: 'Pendiente' | 'Pagado';
  estatus_pago_label: string;
  fecha_cancelacion: string | null;
  title: string;
  notes: string | null;
  decision_reason: string | null;
  approver?: User;
  reviewed_at: string | null;
  duration_minutes: number;
  can_be_canceled: boolean;
  factura?: {
    id: number;
    monto: number;
    moneda: string;
    fecha_pago: string | null;
    estatus_pago: string;
  };
  created_at: string;
  updated_at: string;
}
```

### Área (con pricing)
```typescript
interface Area {
  id: number;
  name: string;
  slug: string;
  description: string;
  capacity: number | null;
  is_reservable: boolean;
  is_active: boolean;
  pricing: {
    monto_hora_externo: number;
    porcentaje_descuento_agremiado: number;
    moneda: string;
    es_gratis_agremiados: boolean;
  };
  schedules: AreaSchedule[];
  images: Image[];
  created_at: string;
  updated_at: string;
}
```

## 🎯 Flujos de Usuario

### 1. Flujo de Aportes (Profesores)

```typescript
// 1. Listar aportes del usuario
const aportes = await api.get('/api/v1/aportes');

// 2. Ver aporte específico con factura
const aporte = await api.get(`/api/v1/aportes/${id}?include=factura`);

// 3. Crear aporte (solo admin)
const nuevoAporte = await api.post('/api/v1/aportes', {
  user_id: 123,
  amount: 50.00,
  aporte_date: '2025-01-29'
});
```

### 2. Flujo de Facturas

```typescript
// 1. Ver mis facturas
const misFacturas = await api.get('/api/v1/me/facturas');

// 2. Ver factura específica
const factura = await api.get(`/api/v1/facturas/${id}`);

// 3. Filtrar facturas (admin)
const facturasAportes = await api.get('/api/v1/facturas?tipo=Aporte Solvencia');
const facturasPagadas = await api.get('/api/v1/facturas?estatus_pago=Pagado');
```

### 3. Flujo de Reservas y Pagos

```typescript
// 1. Crear reserva
const reserva = await api.post('/api/v1/reservations', {
  area_id: 1,
  starts_at: '2025-02-01T10:00:00Z',
  ends_at: '2025-02-01T12:00:00Z',
  title: 'Evento familiar',
  notes: 'Cumpleaños de mi hijo'
});

// 2. Aprobar reserva (admin)
await api.post(`/api/v1/reservations/${id}/approve`, {
  decision_reason: 'Reserva aprobada'
});

// 3. Marcar como pagada (admin)
await api.post(`/api/v1/reservations/${id}/mark-as-paid`, {
  fecha_pago: '2025-01-29',
  moneda: 'USD'
});
```

### 4. Cálculo de Costos

```typescript
// El cálculo de costos se hace automáticamente en el backend
// pero puedes mostrar la información de pricing de las áreas

const area = await api.get('/api/v1/areas/1');
const { pricing } = area.data;

// Mostrar información de precios
console.log(`Precio por hora: ${pricing.monto_hora_externo} ${pricing.moneda}`);
console.log(`Descuento agremiados: ${pricing.porcentaje_descuento_agremiado}%`);
console.log(`Gratis para agremiados: ${pricing.es_gratis_agremiados ? 'Sí' : 'No'}`);
```

## 🎨 Componentes Sugeridos

### 1. AporteCard
```typescript
interface AporteCardProps {
  aporte: Aporte;
  showFactura?: boolean;
  onEdit?: (aporte: Aporte) => void;
  onDelete?: (aporte: Aporte) => void;
}
```

### 2. FacturaCard
```typescript
interface FacturaCardProps {
  factura: Factura;
  showUser?: boolean;
  onView?: (factura: Factura) => void;
}
```

### 3. ReservationCard (actualizado)
```typescript
interface ReservationCardProps {
  reservation: Reservation;
  showPaymentStatus?: boolean;
  showFactura?: boolean;
  onApprove?: (reservation: Reservation) => void;
  onReject?: (reservation: Reservation) => void;
  onMarkAsPaid?: (reservation: Reservation) => void;
}
```

### 4. AreaCard (con pricing)
```typescript
interface AreaCardProps {
  area: Area;
  showPricing?: boolean;
  onReserve?: (area: Area) => void;
}
```

## 🔐 Permisos y Roles

### Usuarios Regulares
- Ver sus propias facturas
- Ver sus propios aportes
- Crear reservas
- Ver áreas con precios

### Profesores/Agremiados
- Todo lo anterior
- Ver descuentos aplicados
- Acceso gratuito a piscina y sauna

### Administradores
- Todo lo anterior
- Crear/editar/eliminar aportes
- Ver todas las facturas
- Aprobar/rechazar reservas
- Marcar reservas como pagadas
- Ver facturas de cualquier usuario

## 📱 Pantallas Sugeridas

### 1. Dashboard Financiero
- Resumen de aportes del mes
- Facturas recientes
- Reservas pendientes de pago
- Estado de solvencia

### 2. Gestión de Aportes
- Lista de aportes con filtros
- Formulario de creación/edición
- Historial de facturas asociadas

### 3. Gestión de Facturas
- Lista paginada con filtros
- Detalle de factura
- Filtros por tipo, estatus, fecha

### 4. Gestión de Reservas (Admin)
- Lista de reservas con estatus de pago
- Aprobación/rechazo
- Marcado de pagos
- Cálculo automático de costos

### 5. Configuración de Áreas (Admin)
- Edición de precios
- Configuración de descuentos
- Áreas gratuitas para agremiados

## 🚀 Implementación Paso a Paso

### Fase 1: Base
1. Crear tipos TypeScript
2. Configurar servicios API
3. Crear componentes básicos

### Fase 2: Aportes
1. Lista de aportes
2. Formulario de creación
3. Integración con facturas

### Fase 3: Facturas
1. Lista de facturas
2. Detalle de factura
3. Filtros y búsqueda

### Fase 4: Reservas y Pagos
1. Actualizar componentes de reservas
2. Agregar estatus de pago
3. Integración con facturas

### Fase 5: Admin
1. Panel de administración
2. Gestión de pagos
3. Reportes financieros

## 🔍 Notas Importantes

1. **Moneda por defecto**: USD (configurable)
2. **Descuentos**: 20% para agremiados en la mayoría de áreas
3. **Excepciones**: Piscina y Sauna gratis para agremiados
4. **Facturas**: Se generan automáticamente al crear aportes
5. **Pagos**: Solo admins pueden marcar reservas como pagadas
6. **Solvencia**: Se actualiza automáticamente al crear aportes

## 🐛 Casos Edge a Considerar

1. Usuario no solvente intentando hacer reserva
2. Área con precio 0 para agremiados
3. Reserva cancelada después del pago
4. Cambio de moneda en el sistema
5. Facturas con estatus pendiente

## 📞 Soporte

Para dudas sobre la implementación, consultar:
- Documentación de la API
- Código fuente del backend
- Changelog del proyecto
