# API de Configuración Institucional - Resumen

## 📋 Cambios en Backend

**Archivos creados:**
- Migración: `database/migrations/2025_11_03_235029_create_settings_table.php`
- Modelo: `app/Models/Setting.php`
- Service: `app/Services/InstitutionConfigService.php`
- Form Request: `app/Http/Requests/UpdateInstitutionConfigRequest.php`
- Resource: `app/Http/Resources/InstitutionConfigResource.php`
- Controlador: `app/Http/Controllers/Api/V1/InstitutionConfigController.php`

**Archivos modificados:**
- `routes/api.php` - Añadidas rutas públicas y admin

---

## 🌐 Endpoints

### 1. GET `/api/v1/institution/config` (Público)

**Autenticación:** No requerida

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "mission": "Texto de misión...",
    "vision": "Texto de visión...",
    "members": [
      {
        "id": "uuid",
        "name": "Nombre",
        "role": "Cargo",
        "image_url": "http://localhost/storage/board_members/image.jpg"
      }
    ]
  }
}
```

**Valores por defecto si no existe configuración:**
```json
{
  "success": true,
  "data": {
    "mission": "Nuestra misión es...",
    "vision": "Nuestra visión es...",
    "members": []
  }
}
```

---

### 2. POST `/api/v1/admin/institution/config` (Admin)

**Autenticación:** Bearer Token + Rol `administrador`

**Content-Type:** `multipart/form-data`

**Body Parameters:**
- `mission` (string, requerido)
- `vision` (string, requerido)
- `members[index][id]` (string, requerido)
- `members[index][name]` (string, requerido, máx. 255)
- `members[index][role]` (string, requerido, máx. 255)
- `member_images[member_id]` (file, opcional - imagen nueva: jpeg, png, jpg, webp, máx. 2MB)

**⚠️ IMPORTANTE**: 
- **NO envíes** `file_id` ni `image_url` al actualizar
- El backend automáticamente preserva los `file_id` existentes de la BD
- Solo envía `member_images[member_id]` cuando hay una imagen nueva

**Respuesta exitosa (200):**
```json
{
  "success": true,
  "data": { "mission": "...", "vision": "...", "members": [...] },
  "message": "Configuración institucional actualizada con éxito."
}
```

**Errores:** `401` (no autenticado), `403` (no autorizado), `422` (validación), `500` (error servidor)

---

## 💡 Uso en Frontend

### Obtener Configuración (React Query)

```typescript
const { data, isLoading } = useQuery({
  queryKey: ['institutionConfig'],
  queryFn: async () => {
    const res = await fetch('/api/v1/institution/config');
    return res.json().data; // { mission, vision, members }
  },
});
```

### Actualizar Configuración (FormData)

```typescript
const formData = new FormData();
formData.append('mission', config.mission);
formData.append('vision', config.vision);

config.members.forEach((member, index) => {
  formData.append(`members[${index}][id]`, member.id);
  formData.append(`members[${index}][name]`, member.name);
  formData.append(`members[${index}][role]`, member.role);
  
  // Solo enviar imagen si hay una nueva
  if (member.newImageFile) {
    formData.append(`member_images[${member.id}]`, member.newImageFile);
  }
  // NO enviar file_id ni image_url - el backend los preserva automáticamente
});

await fetch('/api/v1/admin/institution/config', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json',
  },
  body: formData,
});
```

---

## 📝 Estructura de Datos

```typescript
interface InstitutionConfig {
  mission: string;
  vision: string;
  members: BoardMember[];
}

interface BoardMember {
  id: string;
  name: string;
  role: string;
  image_url: string;
  newImageFile?: File; // Solo en frontend antes de enviar
}
```

---

## ⚠️ Notas Importantes

1. **Preview de imágenes**: Usa `URL.createObjectURL()` para preview local antes de subir
2. **Limpieza**: Revoca URLs con `URL.revokeObjectURL()` al eliminar miembros/imágenes
3. **Formato imágenes**: Enviar como `member_images[member_id]` donde `member_id` es el ID del miembro
4. **Imágenes existentes**: Si no hay nueva imagen, incluir `members[index][image_url]` con URL existente
5. **Cache**: Invalidar con `queryClient.invalidateQueries({ queryKey: ['institutionConfig'] })` después de actualizar

---

## 🔗 URLs

- Base: `http://localhost/api/v1`
- GET: `/api/v1/institution/config`
- POST: `/api/v1/admin/institution/config`

