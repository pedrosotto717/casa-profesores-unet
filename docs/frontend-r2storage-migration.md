# Guía de Migración Frontend: Imágenes a R2Storage

## 📋 Cambios Realizados en Backend

El backend ahora usa **R2Storage (Cloudflare R2)** en lugar de almacenamiento local para las imágenes de miembros de la Junta Administradora.

### Cambios Principales

1. **Almacenamiento**: Las imágenes se suben a R2 y se crea un registro en la tabla `files`
2. **Estructura de Datos**: Los miembros ahora incluyen:
   - `file_id` (integer, opcional) - ID del archivo en R2 (preferido)
   - `image_url` (string, opcional) - URL pública de la imagen (siempre disponible)

---

## 🔄 Cambios en la Estructura de Respuesta

### Antes
```json
{
  "members": [
    {
      "id": "uuid",
      "name": "Juan Pérez",
      "role": "Presidente",
      "image_url": "http://localhost/storage/board_members/abc123.jpg"
    }
  ]
}
```

### Ahora
```json
{
  "members": [
    {
      "id": "uuid",
      "name": "Juan Pérez",
      "role": "Presidente",
      "file_id": 123,           // Nuevo: ID del archivo en R2
      "image_url": "https://r2.example.com/..."  // URL de R2
    }
  ]
}
```

**Nota**: El backend siempre incluye `image_url` para compatibilidad, así que el frontend puede seguir usando `image_url` sin cambios.

---

## 💡 Uso en Frontend

### ✅ No Requiere Cambios (Compatibilidad)

Si tu frontend solo usa `image_url`, **no necesitas cambiar nada**. El backend siempre proporciona `image_url` en la respuesta.

```typescript
// Esto sigue funcionando igual
{data.members.map(member => (
  <img src={member.image_url} alt={member.name} />
))}
```

### 🔧 Si Quieres Usar `file_id` (Opcional)

Si prefieres usar `file_id` para mayor robustez (por si cambia la URL), puedes:

```typescript
interface BoardMember {
  id: string;
  name: string;
  role: string;
  file_id?: number;      // Nuevo campo disponible
  image_url: string;     // Siempre disponible
}

// El backend siempre resuelve file_id a image_url automáticamente
// Pero puedes guardar file_id si quieres para futuras referencias
```

---

## 📤 Envío de Datos al Actualizar

### Formato de Envío (Sin Cambios)

El formato de envío **no cambia**. Sigue enviando:

```typescript
const formData = new FormData();
formData.append('mission', config.mission);
formData.append('vision', config.vision);

config.members.forEach((member, index) => {
  formData.append(`members[${index}][id]`, member.id);
  formData.append(`members[${index}][name]`, member.name);
  formData.append(`members[${index}][role]`, member.role);
  
  // Si hay nueva imagen
  if (member.newImageFile) {
    formData.append(`member_images[${member.id}]`, member.newImageFile);
  }
  // Si NO hay nueva imagen, NO envíes nada más - el backend preserva automáticamente
  // el file_id existente de la base de datos
});
```

**⚠️ IMPORTANTE**: 
- **NO envíes** `file_id` ni `image_url` en el FormData cuando no hay nueva imagen
- El backend automáticamente **preserva** el `file_id` existente de la base de datos
- Solo envía `member_images[member_id]` cuando hay una imagen nueva

### Opcional: Guardar `file_id` en el Estado Local (Solo para referencia)

Si quieres mantener el `file_id` en tu estado local (opcional, solo para debugging):

```typescript
// Después de obtener la configuración
const { data } = useQuery({
  queryKey: ['institutionConfig'],
  queryFn: async () => {
    const res = await fetch('/api/v1/institution/config');
    const result = await res.json();
    // data.data.members ahora incluye file_id (opcional guardarlo)
    return result.data;
  },
});

// Los miembros ahora incluyen file_id automáticamente:
// member.file_id - ID del archivo en R2 (para referencia)
// member.image_url - URL pública (para mostrar)
```

---

## ⚠️ Notas Importantes

1. **URLs Cambian**: Las URLs ahora son de R2 (Cloudflare), no de storage local
2. **Siempre Usa `image_url`**: El backend siempre proporciona `image_url` resuelto, úsalo directamente
3. **`file_id` es Opcional**: Solo úsalo si necesitas tracking adicional en el frontend
4. **Compatibilidad**: El código existente que usa `image_url` sigue funcionando sin cambios

---

## ✅ Checklist de Migración

- [x] **No requiere cambios** - El frontend que usa `image_url` funciona automáticamente
- [ ] Opcional: Actualizar tipos TypeScript para incluir `file_id?: number`
- [ ] Opcional: Guardar `file_id` en el estado local si quieres tracking adicional
- [ ] Verificar que las URLs de imágenes se cargan correctamente (ahora son de R2)

---

## 🔍 Ejemplo Completo (Sin Cambios Necesarios)

```typescript
// Hook para obtener configuración
const { data, isLoading } = useQuery({
  queryKey: ['institutionConfig'],
  queryFn: async () => {
    const res = await fetch('/api/v1/institution/config');
    return res.json().data;
  },
});

// Render - NO CAMBIA NADA
{data.members.map(member => (
  <div key={member.id}>
    <img src={member.image_url} alt={member.name} /> {/* ✅ Funciona igual */}
    <p>{member.name} - {member.role}</p>
  </div>
))}
```

**Conclusión**: El frontend puede seguir funcionando exactamente igual. Las URLs ahora vienen de R2 en lugar de storage local, pero el campo `image_url` siempre está disponible y funciona de la misma manera.

