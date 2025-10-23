# ✅ Checklist de Despliegue Railway

## Antes de Desplegar

- [ ] Asegúrate de tener cuenta en Railway.app
- [ ] Conecta tu repositorio de GitHub a Railway
- [ ] Ten lista tu API key de Gemini

## 1️⃣ Genera tu APP_KEY

```bash
cd legal-ia-backend
php artisan key:generate --show
```

Copia el resultado (será algo como `base64:xxxxxxx...`)

## 2️⃣ Variables de Entorno en Railway

En Railway Dashboard → Variables, agrega:

**Esenciales:**
```
APP_KEY=base64:TU_KEY_QUE_GENERASTE_ARRIBA
GEMINI_API_KEY=tu_gemini_api_key_aqui
```

**Recomendadas:**
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-proyecto.up.railway.app
DB_CONNECTION=sqlite
LOG_LEVEL=error
```

**CORS (tu dominio de Vercel):**
```
SANCTUM_STATEFUL_DOMAINS=tu-app.vercel.app
```

## 3️⃣ Desplegar

1. Railway detectará automáticamente PHP
2. Instalará dependencias
3. Ejecutará migraciones
4. ¡Listo en 2-3 minutos!

## 4️⃣ Verificar que Funciona

Abre en tu navegador:
```
https://tu-proyecto.up.railway.app/api/test
```

Deberías ver:
```json
{
  "message": "API is working! 🚀",
  "status": "success"
}
```

## 5️⃣ Conectar con el Frontend

En Vercel (tu frontend), actualiza:
```
VITE_API_URL=https://tu-proyecto.up.railway.app
```

Redespliega el frontend.

## 6️⃣ Probar Todo

1. Abre tu app en Vercel
2. Intenta registrarte/loguearte
3. Crea un caso legal
4. ¡Todo debería funcionar!

## 🔧 Solución de Problemas

### "No encryption key"
- Verifica que configuraste `APP_KEY` correctamente

### "CORS error"
- Agrega tu dominio de Vercel a `SANCTUM_STATEFUL_DOMAINS`

### "Connection refused"
- Verifica que la URL del backend en el frontend sea correcta

### Build muy lento
- ✅ Ya optimizado! Ahora debería tomar solo 2-3 minutos

## 📊 Optimizaciones Aplicadas

✅ **PHP Built-in Server** en lugar de `php artisan serve`
✅ **Composer optimizado** con `--prefer-dist --no-dev`
✅ **Sin caching innecesario** en el build
✅ **SQLite** (sin necesidad de base de datos externa)
✅ **Archivos innecesarios ignorados** con `.railwayignore`

## 🎯 Próximos Pasos

Una vez desplegado:
1. Configura un dominio personalizado (opcional)
2. Monitorea logs en Railway Dashboard
3. Configura alertas de uptime

---

**Tiempo estimado de despliegue:** 2-3 minutos ⚡
**Costo:** Gratis con el plan Hobby de Railway

