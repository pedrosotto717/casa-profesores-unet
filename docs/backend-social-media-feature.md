# Backend: Gestión de Redes Sociales y Correo Institucional

## Resumen

Se ha agregado funcionalidad para gestionar las redes sociales (Facebook, Instagram, Twitter) y el correo electrónico institucional de la Casa del Profesor Universitario. Esta información se almacena en la base de datos bajo una **key separada** (`social_media_config`) pero se gestiona a través del **mismo endpoint** existente de configuración institucional.

---

## 🎯 Cambios Principales

### 1. **Almacenamiento en Base de Datos**

La información de redes sociales se almacena en:
- **Tabla:** `settings`
- **Key:** `social_media_config` (separada de `institution_info`)
- **Estructura JSON:**
```json
{
  "facebook": "https://facebook.com/casa-profesor-unet",
  "instagram": "https://instagram.com/casa_profesor_unet",
  "twitter": "https://twitter.com/casa_profesor",
  "email": "contacto@casaprofesor.unet.edu.ve"
}
```

### 2. **Endpoints API (Sin Cambios en la URL)**

Los endpoints **NO han cambiado**, solo se ha extendido su funcionalidad:

#### **GET `/api/v1/institution/config`** (Público)
Ahora devuelve la información de redes sociales junto con misión, visión y junta directiva.

**Respuesta:**
```json
{
  "success": true,
  "data": {
    "mission": "...",
    "vision": "...",
    "members": [...],
    "social_media": {
      "facebook": "https://facebook.com/...",
      "instagram": "https://instagram.com/...",
      "twitter": "https://twitter.com/...",
      "email": "contacto@example.com"
    }
  }
}
```

#### **POST `/api/v1/admin/institution/config`** (Admin Only)
Ahora acepta el campo adicional `social_media` en el body del request.

**Request Body (`multipart/form-data` o `application/json`):**
```json
{
  "mission": "...",
  "vision": "...",
  "members": [...],
  "social_media": {
    "facebook": "https://facebook.com/...",
    "instagram": "https://instagram.com/...",
    "twitter": "https://twitter.com/...",
    "email": "contacto@example.com"
  }
}
```

**Notas importantes:**
- Todos los campos de `social_media` son **opcionales**
- Si no se envía `social_media`, se mantienen los valores existentes
- Los valores pueden ser `null` o strings vacíos para limpiar un campo

---

## 🔍 Validaciones

El backend valida automáticamente:

| Campo | Reglas |
|-------|--------|
| `social_media.facebook` | Opcional, debe ser URL válida, máximo 500 caracteres |
| `social_media.instagram` | Opcional, debe ser URL válida, máximo 500 caracteres |
| `social_media.twitter` | Opcional, debe ser URL válida, máximo 500 caracteres |
| `social_media.email` | Opcional, debe ser email válido, máximo 255 caracteres |

**Ejemplo de error de validación:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "social_media.facebook": [
      "The social media.facebook must be a valid URL."
    ],
    "social_media.email": [
      "The social media.email must be a valid email address."
    ]
  }
}
```

---

## 📝 Logs de Auditoría

Cada actualización de la configuración institucional (incluyendo redes sociales) ahora genera un registro en la tabla `audit_logs`:

```json
{
  "user_id": 1,
  "entity_type": "InstitutionConfig",
  "entity_id": null,
  "action": "institution_config_updated",
  "before": {
    "mission": "...",
    "vision": "...",
    "members_count": 5,
    "social_media": {
      "facebook": "https://old-url.com",
      ...
    }
  },
  "after": {
    "mission": "...",
    "vision": "...",
    "members_count": 5,
    "social_media": {
      "facebook": "https://new-url.com",
      ...
    },
    "updated_at": "2025-11-04T03:00:04.000000Z",
    "user_agent": "...",
    "ip_address": "..."
  }
}
```

---

## 🚀 Guía para el Frontend

### 1. **Obtener Configuración (GET)**

```typescript
// Usando React Query / Tanstack Query
const { data } = useQuery({
  queryKey: ['institutionConfig'],
  queryFn: async () => {
    const res = await apiClient.get('/api/v1/institution/config');
    return res.data.data;
  }
});

// Acceder a redes sociales
const { facebook, instagram, twitter, email } = data.social_media;
```

### 2. **Actualizar Configuración (POST) - Admin**

**Opción A: Usando `application/json` (Recomendado si NO hay imágenes nuevas)**

```typescript
const updateConfig = async (configData) => {
  const res = await apiClient.post('/api/v1/admin/institution/config', {
    mission: configData.mission,
    vision: configData.vision,
    members: configData.members,
    social_media: {
      facebook: configData.socialMedia.facebook || null,
      instagram: configData.socialMedia.instagram || null,
      twitter: configData.socialMedia.twitter || null,
      email: configData.socialMedia.email || null,
    }
  });
  return res.data.data;
};
```

**Opción B: Usando `multipart/form-data` (Si hay imágenes de miembros)**

```typescript
const updateConfig = async (configData, memberImages) => {
  const formData = new FormData();
  
  formData.append('mission', configData.mission);
  formData.append('vision', configData.vision);
  
  // Members
  configData.members.forEach((member, index) => {
    formData.append(`members[${index}][id]`, member.id);
    formData.append(`members[${index}][name]`, member.name);
    formData.append(`members[${index}][role]`, member.role);
  });
  
  // Member images (solo si hay nuevas imágenes)
  Object.entries(memberImages).forEach(([memberId, file]) => {
    if (file) {
      formData.append(`member_images[${memberId}]`, file);
    }
  });
  
  // Social media
  formData.append('social_media[facebook]', configData.socialMedia.facebook || '');
  formData.append('social_media[instagram]', configData.socialMedia.instagram || '');
  formData.append('social_media[twitter]', configData.socialMedia.twitter || '');
  formData.append('social_media[email]', configData.socialMedia.email || '');
  
  const res = await apiClient.post('/api/v1/admin/institution/config', formData, {
    headers: { 'Content-Type': 'multipart/form-data' }
  });
  
  return res.data.data;
};
```

### 3. **Ejemplo de Formulario (React)**

```tsx
interface SocialMediaForm {
  facebook: string;
  instagram: string;
  twitter: string;
  email: string;
}

const AdminSocialMediaSection = () => {
  const { data } = useQuery(['institutionConfig'], getInstitutionConfig);
  const [socialMedia, setSocialMedia] = useState<SocialMediaForm>({
    facebook: '',
    instagram: '',
    twitter: '',
    email: '',
  });

  useEffect(() => {
    if (data?.social_media) {
      setSocialMedia({
        facebook: data.social_media.facebook || '',
        instagram: data.social_media.instagram || '',
        twitter: data.social_media.twitter || '',
        email: data.social_media.email || '',
      });
    }
  }, [data]);

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    // Combinar con otros datos de configuración (misión, visión, miembros)
    updateMutation.mutate({
      ...existingConfig,
      social_media: socialMedia
    });
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Redes Sociales y Contacto</CardTitle>
      </CardHeader>
      <CardContent>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <Label htmlFor="facebook">Facebook URL</Label>
            <Input
              id="facebook"
              type="url"
              placeholder="https://facebook.com/..."
              value={socialMedia.facebook}
              onChange={(e) => setSocialMedia(prev => ({ ...prev, facebook: e.target.value }))}
            />
          </div>
          
          <div>
            <Label htmlFor="instagram">Instagram URL</Label>
            <Input
              id="instagram"
              type="url"
              placeholder="https://instagram.com/..."
              value={socialMedia.instagram}
              onChange={(e) => setSocialMedia(prev => ({ ...prev, instagram: e.target.value }))}
            />
          </div>
          
          <div>
            <Label htmlFor="twitter">Twitter URL</Label>
            <Input
              id="twitter"
              type="url"
              placeholder="https://twitter.com/..."
              value={socialMedia.twitter}
              onChange={(e) => setSocialMedia(prev => ({ ...prev, twitter: e.target.value }))}
            />
          </div>
          
          <div>
            <Label htmlFor="email">Correo Institucional</Label>
            <Input
              id="email"
              type="email"
              placeholder="contacto@example.com"
              value={socialMedia.email}
              onChange={(e) => setSocialMedia(prev => ({ ...prev, email: e.target.value }))}
            />
          </div>
          
          <Button type="submit">Guardar Cambios</Button>
        </form>
      </CardContent>
    </Card>
  );
};
```

### 4. **Ejemplo de Uso Público (Footer con Redes Sociales)**

```tsx
const Footer = () => {
  const { data } = useQuery(['institutionConfig'], getInstitutionConfig);

  return (
    <footer className="bg-primary text-primary-foreground py-8">
      <div className="container mx-auto">
        <div className="flex justify-center gap-4">
          {data?.social_media?.facebook && (
            <a href={data.social_media.facebook} target="_blank" rel="noopener noreferrer">
              <Facebook className="h-6 w-6" />
            </a>
          )}
          {data?.social_media?.instagram && (
            <a href={data.social_media.instagram} target="_blank" rel="noopener noreferrer">
              <Instagram className="h-6 w-6" />
            </a>
          )}
          {data?.social_media?.twitter && (
            <a href={data.social_media.twitter} target="_blank" rel="noopener noreferrer">
              <Twitter className="h-6 w-6" />
            </a>
          )}
        </div>
        {data?.social_media?.email && (
          <p className="text-center mt-4">
            <Mail className="inline h-4 w-4 mr-2" />
            {data.social_media.email}
          </p>
        )}
      </div>
    </footer>
  );
};
```

---

## ✅ Checklist para Frontend

- [ ] Actualizar el tipo TypeScript `InstitutionConfig` para incluir `social_media`
- [ ] Agregar inputs en el panel de admin para Facebook, Instagram, Twitter y Email
- [ ] Validar URLs en el frontend antes de enviar (feedback rápido al usuario)
- [ ] Mostrar íconos de redes sociales en el footer del sitio público
- [ ] Mostrar correo institucional en la página "Sobre Nosotros" o "Contacto"
- [ ] Manejar casos donde `social_media` es `null` o campos individuales son `null`

---

## 🐛 Troubleshooting

**Problema:** Las redes sociales no aparecen después de actualizar
- **Solución:** Verifica que estés enviando el objeto `social_media` en el request

**Problema:** Error de validación "must be a valid URL"
- **Solución:** Asegúrate de que las URLs incluyan el protocolo (`https://`)

**Problema:** Los cambios no se reflejan en el frontend
- **Solución:** Invalida el cache de React Query: `queryClient.invalidateQueries(['institutionConfig'])`

---

## 📚 Archivos Modificados

- `app/Http/Requests/UpdateInstitutionConfigRequest.php` - Validación
- `app/Services/InstitutionConfigService.php` - Lógica de negocio
- `app/Http/Resources/InstitutionConfigResource.php` - Respuesta API
- `app/Http/Controllers/Api/V1/InstitutionConfigController.php` - Logs de auditoría
- `changelog.md` - Registro de cambios



