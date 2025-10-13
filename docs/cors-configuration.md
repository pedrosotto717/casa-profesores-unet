# CORS Configuration

## Variables de Entorno

Para configurar CORS desde variables de entorno, añade las siguientes variables a tu archivo `.env`:

```env
# CORS Configuration
CORS_ALLOWED_ORIGINS=https://casa-profesor-universita-def92.web.app
CORS_SUPPORTS_CREDENTIALS=true
```

## Configuración Múltiples Orígenes

Para permitir múltiples orígenes, separa las URLs con comas:

```env
CORS_ALLOWED_ORIGINS=https://casa-profesor-universita-def92.web.app,https://localhost:3000,https://staging.casa-profesor.com
```

## Configuración por Ambiente

### Desarrollo Local
```env
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:5173,http://127.0.0.1:3000
CORS_SUPPORTS_CREDENTIALS=true
```

### Staging
```env
CORS_ALLOWED_ORIGINS=https://staging.casa-profesor.com,https://casa-profesor-staging.web.app
CORS_SUPPORTS_CREDENTIALS=true
```

### Producción
```env
CORS_ALLOWED_ORIGINS=https://casa-profesor-universita-def92.web.app
CORS_SUPPORTS_CREDENTIALS=true
```

## Explicación de Variables

- **CORS_ALLOWED_ORIGINS**: URLs permitidas para hacer peticiones CORS. Separadas por comas.
- **CORS_SUPPORTS_CREDENTIALS**: Si debe incluir cookies y headers de autenticación en las peticiones CORS.

## Notas Importantes

1. **No uses `*` en producción**: Es un riesgo de seguridad permitir todos los orígenes.
2. **Incluye protocolo**: Asegúrate de incluir `https://` o `http://` en las URLs.
3. **Sin barra final**: No incluyas `/` al final de las URLs.
4. **Credentials**: Si usas autenticación con cookies/tokens, mantén `CORS_SUPPORTS_CREDENTIALS=true`.
