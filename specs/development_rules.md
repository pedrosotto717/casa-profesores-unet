# Reglas de Desarrollo Backend – UNET Casa del Profesor (Laravel 12 + PHP 8.3)

> **Objetivo**: establecer un estándar **claro, estricto y práctico** para escribir, revisar y mantener el backend del proyecto (API Laravel 12 + PHP 8.3). Estas reglas reflejan nuestros acuerdos de arquitectura y los lineamientos del proyecto definidos en `main.md` y `database_structure.md`.

---

## 0) Principios rectores

1. **Código limpio, sólido y consistente**: SOLID, DRY, KISS, YAGNI.
2. **Fuertemente tipado**: Tipos explícitos en parámetros y retornos de métodos.
3. **Capas bien separadas**: Controladores delgados, **Form Requests** (validación), **Services** (lógica), **Policies** (autorización), **Resources** (formato de salida).
4. **Autenticación**: **SSO/LDAP CETI** (docentes/personal) y **Laravel Sanctum** como **fallback** y para SPA tokens (invitados/operadores).
5. **Terminología institucional**: usar **aportes**/**solvencia** (no “alquiler”, no “membresía”).
6. **API estable** y versionada: prefijo `/api/v1/*`. Cambios incompatibles ⇒ nueva versión.
7. **Base de datos como fuente de verdad**; cache como acelerador (nunca única fuente).
8. **Auditoría**: operaciones sensibles se registran en `audit_logs`.

---

## 1) Estructura de carpetas (resumen)

```
app/
  Exceptions/
  Http/
    Controllers/  ← controladores finales, delgados
    Requests/     ← Form Requests
    Resources/    ← API Resources
    Middleware/
  Policies/       ← políticas de acceso por modelo
  Services/       ← servicios finales y de solo lectura de dependencias
  Observers/      ← observers de modelos (auditoría, sincronización)
  Enums/          ← PHP Enums para estados/roles
  Models/         ← modelos finales (Eloquent)
Database/
  factories, migrations, seeders
config/
```

> Los archivos de rutas por dominio se cargan desde `RouteServiceProvider`.

---

## 2) Convenciones de código

* **PSR-12** + **Laravel Pint** (formato) + **PHPStan/Larastan** nivel alto (sin errores).
* **Nombres**: modelos en singular (`User`), controladores en plural (`UsersController`), columnas **snake\_case**.
* **Clases** `final`; propiedades `private` y `readonly` cuando aplique.
* **Imports** ordenados alfabéticamente, **tipos explícitos** en parámetros y retornos.

```php
<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\User\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

final class UsersController
{
    /**
     * Evitar inyección por constructor. Usar inyección por método.
     */
    public function store(StoreUserRequest $request, UserService $service): JsonResponse
    {
        $user = $service->create($request->validated());
        return (new UserResource($user))
            ->additional(['success' => true])
            ->response();
    }
}
```

---

## 3) Validación (Form Requests)

* **Siempre** usar `FormRequest` por endpoint de escritura.
* `authorize()` delega a **Policies**; `rules()` define validaciones.
* Reglas comunes en `app/Http/Requests/Concerns/*` (traits reutilizables).

```php
<?php

namespace App\Http\Requests\User;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

final class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'name'     => ['required','string','max:150'],
            'email'    => ['required','email','max:180','unique:users,email'],
            'password' => ['required','string','min:8'],
        ];
    }
}
```

---

## 4) Servicios (app/Services)

* Un **Service** encapsula reglas de negocio y **transacciones**.
* No exponer Eloquent fuera de la capa si no es necesario; devolver modelos o DTOs.
* Manejar **concurrencia** (reservas) con transacciones + bloqueos cuando aplique.

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UserService
{
    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name'  => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
            ]);
            // ... lógica extra (eventos, notificaciones)
            return $user;
        });
    }
}
```

> **Regla**: si un método toca 2+ tablas o cambia estados, **debe** estar en una transacción.

---

## 5) Autenticación y autorización

* **SSO/LDAP CETI** para docentes/personal; **Sanctum** para SPA tokens y fallback (invitados/operadores).
* **Policies** por cada modelo expuesto; usar `Gate::policy()` en `AuthServiceProvider`.
* **Rate limiting** por usuario/IP para endpoints sensibles.
* **CORS** configurado para el dominio del frontend.

```php
// app/Policies/ReservationPolicy.php
public function create(User $user): bool
{
    return $user->is_solvent === true && $user->role === 'docente';
}
```

---

## 6) API Resources (respuesta consistente)

* **Siempre** usar `JsonResource`/`ResourceCollection`.
* Envoltura común `{ success, data, meta }`; paginación con `->additional(['meta' => [...]] )`.
* **Fechas y TZ**: guardar en **UTC**; serializar ISO8601 (`->toISOString()`).

```php
final class ReservationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'      => $this->id,
            'area_id' => $this->area_id,
            'status'  => $this->status,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at'   => $this->ends_at?->toISOString(),
        ];
    }
}
```

---

## 7) Rutas y versionado

* Prefijo **`/api/v1`** + archivos por dominio en `routes/api/v1/*.php`.
* Convención REST: `index, show, store, update, destroy`; acciones de estado como `approve/reject` vía endpoints `POST /reservations/{id}/approve`.

---

## 8) Modelos y Enums

* Modelos **final** con `fillable` explícito y **casts**.
* Estados/roles como **Backed Enums** (PHP 8.1+). Mapear con `enum` DB o `string` + validación.

```php
// app/Enums/ReservationStatus.php
enum ReservationStatus: string { case Pendiente='pendiente'; case Aprobada='aprobada'; case Rechazada='rechazada'; case Cancelada='cancelada'; case Completada='completada'; case Expirada='expirada'; }

// app/Models/Reservation.php
protected $casts = [ 'status' => ReservationStatus::class, 'starts_at' => 'datetime', 'ends_at' => 'datetime', ];
```

---

## 9) Reglas de negocio clave (alineadas al dominio)

1. **Solvencia obligatoria** (`users.is_solvent && solvent_until >= today`) para **reservar** o **invitar**.
2. **Invitaciones**: crea **docente**; aprueba/deniega **administrador**.
3. **Reservas**: verificación estricta de **solapamiento** por área; transición de estados auditada con `approved_by`, `reviewed_at`, `decision_reason`.
4. **Tarifas**: `services.hourly_rate` sobreescribe `areas.hourly_rate` (si no `NULL`).
5. **Documentos**: flujo `borrador → revisión → publicado → archivado` y control de acceso por `visibility`.
6. **Auditoría** de cambios relevantes en `reservations`, `contributions`, `invitations`, `documents`.

---

## 10) Migraciones y base de datos

* Esquema de referencia: `database_structure.md`.
* **Soft Deletes** donde aplica; **índices** y **únicos** según especificación (ej. `contributions(user_id, period)`).
* **Semillas** mínimas: roles, áreas iniciales, servicios por área.

---

## 11) Transacciones, concurrencia y bloqueo

* Usar `DB::transaction()` para operaciones multi-tabla.
* En **aprobación de reservas**, verificar colisión dentro de la **transacción** (consulta por rango) y, si procede, aplicar `for update` cuando se modele por *time slots*.
* Manejar **409 Conflict** ante colisiones.

---

## 12) Cache y performance

* **Cache**: catálogos, vistas de disponibilidad, contadores; invalidar en **Observers** tras cambios.
* **Paginación** por defecto: `per_page` ∈ \[10, 100] con tope server-side.
* Evitar **N+1** (`->with()`), índices en columnas de filtro/orden.

---

## 13) Errores, excepciones y logging

* Excepciones de dominio (p.ej., `ReservationConflictException`) mapeadas a HTTP coherente (409, 422, 403, 404).
* `app/Exceptions/Handler.php` centraliza la respuesta JSON estándar.
* Logs en formato **JSON** (canal `daily`), con `context` suficiente (user\_id, ip, request\_id).

```php
return response()->json([
  'success' => false,
  'error' => [ 'code' => 'RESERVATION_CONFLICT', 'message' => 'El horario no está disponible.' ]
], 409);
```

---

## 14) Seguridad

* **Sanctum**: SPA tokens con vencimiento y revocación; **remember tokens** no se exponen por API.
* **Rate limiting** granular (login, invitaciones, reservas).
* **CORS** restringido; **headers** de seguridad (HSTS, no sniff).
* Validar **entradas** (Form Requests), **salidas** con Resources; **escape** de contenido en vistas (si las hubiera).

---

## 15) Estándar de respuesta API

```json
{
  "success": true,
  "data": { /* resource */ },
  "meta": { "version": "v1", "request_id": "uuid", "pagination": { /* si aplica */ } }
}
```

* Errores: `success=false`, `error.code` estable y documentado, `message` legible, `fields` (si es validación).

---

## 16) Trazabilidad y auditoría

* **Observer** por modelo sensible para crear registros en `audit_logs`.
* Guardar `before/after` mínimo razonable (IDs/estados), evitando datos sensibles.
* Requerir `request_id` (header) para correlación en logs.

---

## 17) Testing

* **Pest/PHPUnit**: unit (servicios), feature (endpoints), policies y validaciones.
* **Factories** para todos los modelos; **DatabaseTransactions** o **RefreshDatabase**.
* Tests de **reglas de negocio** (solvencia, colisiones de reservas, transiciones de estado, visibilidad de documentos).

---

## 18) Estándar de git, PR y CI

* **Ramas**: `feature/<slug>`, `fix/<slug>`, `chore/<slug>`.
* **Commits**: Conventional Commits (`feat:`, `fix:`, `refactor:`, `test:`, `docs:`).
* **PR checklist**: pruebas pasan, cobertura mínima (acordada), sin *code smells*, endpoints documentados.
* CI: `composer ci` (pint + phpstan + pest) debe pasar antes de merge.

---

## 19) Documentación y contratos

* Documentar endpoints (OpenAPI o `scribe`) **por versión**.
* Cada endpoint enlaza su **FormRequest** y **Resource**.
* Tablas/estados sincronizados con `database_structure.md`.

---

## 20) Reglas específicas por módulo

### 20.1 Usuarios y Solvencia

* `users.is_solvent` y `users.solvent_until` se **derivan** de `contributions`. Actualizar vía eventos/Jobs.
* Docentes (SSO) crean **invitaciones** sólo si están solventes.

### 20.2 Invitaciones

* Estados: `pendiente → aceptada | rechazada | expirada | revocada`.
* Token seguro (`64 chars`), expiración configurable.
* Aprobación **solo** por admin; auditar cambios.

### 20.3 Reservas

* Estados: `pendiente → aprobada | rechazada | cancelada → completada | expirada`.
* Verificar **disponibilidad** íntegra por área, dentro de **transacción**.
* Cambios de estado **siempre** auditados.

### 20.4 Aportes

* Periodicidad por **period (DATE)** (día 1 del mes).
* Estados: `pendiente | pagado | vencido`.
* Al marcar `pagado`, recalcular `solvent_until`.
* Adjuntar `receipt_url` cuando aplique.

### 20.5 Documentos

* `visibility`: `publico | interno | solo_admin`.
* Publicación con versión y metadatos; descargar auditable.

### 20.6 Academias

* Respetar `capacity` por franja; inscripciones únicas por (academy, user).

---

## 21) Configuración, tiempo y fechas

* **TZ del servidor/DB**: **UTC**.
* Serialización ISO8601; conversión de zona horaria (vista) es responsabilidad del frontend.
* Validar `starts_at < ends_at` y rangos futuros razonables.

---

## 22) Utilidades recomendadas

* **Laravel Pint** (`vendor/bin/pint`), **Larastan** (`phpstan.neon`), **Telescope** (solo dev), **Horizon** si hay colas.
* **Artisan makers** personalizados para scaffolding de Request/Resource/Service/Policy.

---

## 23) Checklist de revisión (PR)

1. `strict_types` y tipos en firmas.
2. Controller delgado; validación en **FormRequest**; lógica en **Service**.
3. Autorización en **Policy**.
4. Respuesta con **Resource** y contrato estable.
5. Transacción donde corresponda.
6. Tests incluidos/actualizados.
7. Logs y auditoría si aplica.
8. Terminología correcta (**aportes**, **solvencia**).
9. Documentación de endpoint.

---

## 24) Plantillas rápidas (snippets)

**Comando de scaffolding (ejemplo de alias local):**

```bash
# Crea Request, Resource y Service para <Entity>
php artisan make:request V1/<Entity>/Store<Entity>Request \
  && php artisan make:resource V1/<Entity>Resource \
  && php artisan make:policy <Entity>Policy --model=<Entity> \
  && php artisan make:observer <Entity>Observer --model=<Entity>
```

**Handler de conflicto de reservas (esqueleto)**

```php
if ($this->hasOverlap($areaId, $from, $to)) {
    throw new ReservationConflictException('El horario no está disponible.');
}
```

---

> **Cumplimiento**: estas reglas son **obligatorias**. Cualquier excepción debe justificarse en el PR y documentarse.
