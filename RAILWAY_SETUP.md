# 🚀 Guía de Despliegue en Railway

## Pasos Rápidos

### 1. Variables de Entorno en Railway

Configura estas variables en Railway Dashboard:

```bash
APP_NAME=LegalIA
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:TU_KEY_AQUI
APP_URL=https://tu-backend.railway.app

DB_CONNECTION=sqlite

# API Keys (IMPORTANTE)
GEMINI_API_KEY=tu_gemini_api_key
OPENAI_API_KEY=tu_openai_api_key

# CORS - Pon tu dominio de Vercel
SANCTUM_STATEFUL_DOMAINS=tu-app.vercel.app,localhost
SESSION_DOMAIN=.railway.app

# Logs
LOG_CHANNEL=stack
LOG_LEVEL=error

# Cache y Session
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_LIFETIME=120
QUEUE_CONNECTION=database
```

### 2. Generar APP_KEY

Ejecuta en tu terminal local:

```bash
cd legal-ia-backend
php artisan key:generate --show
```

Copia el resultado y úsalo como `APP_KEY` en Railway.

### 3. Configuración Automática

Railway detectará automáticamente:
- ✅ PHP 8.2
- ✅ Composer
- ✅ SQLite Database
- ✅ Puerto dinámico

### 4. Deploy

1. Conecta tu repositorio a Railway
2. Railway detectará automáticamente la configuración
3. El build tomará 2-3 minutos
4. ¡Listo!

## Comandos que Railway ejecuta automáticamente

1. **Build**: `composer install --no-dev --optimize-autoloader --prefer-dist`
2. **Start**: `php artisan migrate --force && php -S 0.0.0.0:$PORT -t public`

## Verificar después del Deploy

1. Visita: `https://tu-backend.railway.app/api/test`
2. Deberías ver: `{"message": "API is working"}`

## Problemas Comunes

### Error: "No application encryption key"
- Asegúrate de configurar `APP_KEY` en las variables de entorno

### Error: "CORS"
- Verifica que `SANCTUM_STATEFUL_DOMAINS` incluya tu dominio de Vercel

### Error: "Database"
- SQLite se crea automáticamente, no necesitas configurar nada más

## Optimizaciones Aplicadas

✅ Eliminado `php artisan serve` (solo desarrollo)
✅ Usando PHP Built-in Server optimizado
✅ Composer con `--prefer-dist` para builds más rápidos
✅ Sin caching innecesario en build
✅ `.railwayignore` para archivos no necesarios

## Conectar Frontend

En tu Vercel (frontend), actualiza la variable de entorno:

```bash
VITE_API_URL=https://tu-backend.railway.app
```

Luego redespliega el frontend.

