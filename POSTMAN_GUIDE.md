# Guía de uso de Postman para LEGAL-IA API

## 🚀 Configuración inicial

### 1. Importar la colección a Postman

1. Abre Postman
2. Haz clic en **"Import"** (arriba a la izquierda)
3. Selecciona el archivo `LEGAL-IA.postman_collection.json`
4. La colección aparecerá en tu sidebar

### 2. Verificar variables de entorno

La colección tiene las siguientes variables configuradas:

```
base_url: http://127.0.0.1:8000
case_uuid: (se guarda automáticamente al crear un caso)
analysis_uuid: (se guarda automáticamente al iniciar análisis)
evidence_uuid: (se guarda automáticamente al subir evidencia)
jurisprudence_uuid: (se guarda automáticamente al crear jurisprudencia)
```

---

## ✅ Flujo de pruebas recomendado

### Paso 1: Health Check
📍 **Health Check** → GET `/api/health`

Respuesta esperada:
```json
{
    "status": "ok",
    "service": "LEGAL-IA API",
    "version": "1.0.0",
    "timestamp": "2025-10-21T..."
}
```

---

### Paso 2: Crear un caso legal
📍 **Cases → Create Case** → POST `/api/cases`

El body ya está pre-configurado:
```json
{
    "title": "Accidente de tránsito en intersección",
    "description": "Colisión entre dos vehículos con testimonios contradictorios",
    "case_type": "civil",
    "parties": {
        "plaintiff": "Juan Pérez Rodríguez",
        "defendant": "María García López"
    },
    "incident_date": "2025-10-15",
    "facts": "El día 15 de octubre de 2025..."
}
```

✅ **NOTA**: El UUID del caso se guardará automáticamente en la variable `{{case_uuid}}`

Respuesta esperada (201 Created):
```json
{
    "success": true,
    "message": "Caso legal creado exitosamente",
    "data": {
        "uuid": "e421ce49-9fee-4acb-9a03-57fe5c793cd8",
        "title": "Accidente de tránsito en intersección",
        "status": "draft",
        ...
    }
}
```

---

### Paso 3: Ver el caso creado
📍 **Cases → Get Case** → GET `/api/cases/{{case_uuid}}`

Respuesta esperada (200 OK):
```json
{
    "success": true,
    "data": {
        "id": 1,
        "uuid": "e421ce49-9fee-4acb-9a03-57fe5c793cd8",
        "title": "Accidente de tránsito en intersección",
        "case_type": "civil",
        "status": "draft",
        "evidence": [],
        "analyses": []
    }
}
```

---

### Paso 4: Subir evidencia
📍 **Evidence → Upload Evidence (File)** → POST `/api/cases/{{case_uuid}}/evidence`

**IMPORTANTE**: Para subir un archivo:
1. Ve a la pestaña **Body**
2. Asegúrate que está en modo **form-data**
3. En el campo **file**, haz clic en "Select Files" y elige una imagen o video

O usa la versión con URL:
📍 **Evidence → Upload Evidence (URL)**

✅ **NOTA**: El UUID de la evidencia se guardará automáticamente en `{{evidence_uuid}}`

---

### Paso 5: Crear jurisprudencia
📍 **Jurisprudence → Create Jurisprudence** → POST `/api/jurisprudence`

El body está pre-configurado con un caso de ejemplo:
```json
{
    "case_number": "STC-2024-12345",
    "court": "Corte Suprema",
    "case_title": "Responsabilidad civil en accidente de tránsito...",
    "summary": "La Corte Suprema establece criterios...",
    "keywords": ["accidente_transito", "responsabilidad_civil"],
    "relevance_level": "high"
}
```

✅ **NOTA**: El UUID se guardará automáticamente en `{{jurisprudence_uuid}}`

---

### Paso 6: Iniciar análisis del caso
📍 **Analysis → Start Analysis** → POST `/api/cases/{{case_uuid}}/analyze`

Respuesta esperada (202 Accepted):
```json
{
    "success": true,
    "message": "Análisis iniciado exitosamente",
    "data": {
        "case": {...},
        "analysis": {
            "uuid": "abc-123...",
            "status": "pending",
            "version": 1
        }
    }
}
```

✅ **NOTA**: El UUID del análisis se guardará automáticamente en `{{analysis_uuid}}`

---

### Paso 7: Ver análisis del caso
📍 **Analysis → Get Latest Analysis** → GET `/api/cases/{{case_uuid}}/analysis/latest`

---

## 📊 Endpoints de estadísticas

Puedes probar estos endpoints en cualquier momento:

1. **Cases → Case Stats** → GET `/api/cases/stats`
2. **Analysis → Analysis Stats** → GET `/api/analysis/stats`
3. **Jurisprudence → Jurisprudence Stats** → GET `/api/jurisprudence/stats`

---

## 🔍 Búsquedas de jurisprudencia

### Búsqueda full-text
📍 **Jurisprudence → Search Jurisprudence (Full-Text)**

```json
{
    "query": "responsabilidad civil accidente semáforo",
    "limit": 10
}
```

### Búsqueda semántica (requiere LLM configurado)
📍 **Jurisprudence → Semantic Search (Embeddings)**

```json
{
    "query": "casos de colisión vehicular con testimonios contradictorios",
    "limit": 10,
    "min_similarity": 0.75
}
```

⚠️ **NOTA**: Este endpoint retornará 501 Not Implemented hasta que se configure la API key de LLM

---

## 🧪 Casos de prueba adicionales

### Crear múltiples casos de diferentes tipos

**Caso Penal:**
```json
{
    "title": "Robo con intimidación",
    "description": "Robo a mano armada en establecimiento comercial",
    "case_type": "penal",
    "facts": "El acusado ingresó al local...",
    "incident_date": "2025-09-20"
}
```

**Caso Laboral:**
```json
{
    "title": "Despido improcedente",
    "description": "Trabajador despedido sin causa justificada",
    "case_type": "laboral",
    "facts": "El trabajador fue despedido...",
    "incident_date": "2025-08-10"
}
```

---

## 🎨 Filtros y paginación

### Listar casos con filtros
📍 **Cases → List Cases**

Puedes activar estos query params:
- `?status=draft` - Solo casos en borrador
- `?case_type=civil` - Solo casos civiles
- `?per_page=10` - Cambiar cantidad por página

### Listar jurisprudencia con filtros
📍 **Jurisprudence → List Jurisprudence**

Query params disponibles:
- `?court=Suprema` - Filtrar por tribunal
- `?relevance_level=high` - Solo alta relevancia
- `?per_page=15`

---

## 🔄 CRUD completo

Cada recurso (Cases, Evidence, Jurisprudence) tiene los endpoints:

- **GET** `/api/{resource}` - Listar todos
- **POST** `/api/{resource}` - Crear nuevo
- **GET** `/api/{resource}/{uuid}` - Ver uno específico
- **PUT** `/api/{resource}/{uuid}` - Actualizar
- **DELETE** `/api/{resource}/{uuid}` - Eliminar

---

## ⚡ Scripts automáticos en Postman

La colección incluye scripts automáticos que:

1. **Guardan UUIDs automáticamente** cuando creas recursos
2. **Los usan en requests posteriores** sin que tengas que copiar/pegar

Por ejemplo, después de crear un caso, puedes inmediatamente:
- Ver el caso: usa `{{case_uuid}}`
- Subir evidencia: usa `{{case_uuid}}`
- Iniciar análisis: usa `{{case_uuid}}`

---

## 🐛 Troubleshooting

### Error "Could not get any response"
✅ Verifica que el servidor Laravel esté corriendo:
```bash
cd legal-ia-backend
php artisan serve
```

### Error 404 "Not Found"
✅ Verifica que la URL base sea correcta:
- Debería ser: `http://127.0.0.1:8000`
- NO: `http://localhost:8000/api/api/...`

### Error 422 "Validation Error"
✅ Revisa el body de tu request y compara con los ejemplos

### Error 500 "Server Error"
✅ Verifica los logs de Laravel:
```bash
tail -f storage/logs/laravel.log
```

---

## 📝 Respuestas comunes

### Éxito (200, 201)
```json
{
    "success": true,
    "message": "Mensaje descriptivo",
    "data": { ... }
}
```

### Error de validación (422)
```json
{
    "success": false,
    "errors": {
        "title": ["El campo título es obligatorio"]
    }
}
```

### No encontrado (404)
```json
{
    "success": false,
    "message": "Caso no encontrado"
}
```

### Procesamiento asíncrono (202)
```json
{
    "success": true,
    "message": "Análisis iniciado exitosamente",
    "data": { ... }
}
```

---

## 🎯 Flujo completo de ejemplo

1. ✅ Health Check
2. ✅ Create Case (guarda UUID)
3. ✅ Upload Evidence (imagen o video)
4. ✅ Create Jurisprudence (varios casos de ejemplo)
5. ✅ Start Analysis
6. ✅ Get Latest Analysis
7. ✅ View Case Stats

---

## 🔑 Próximos pasos

Una vez que implementes los Services y Agentes:

1. El endpoint de **análisis** retornará resultados reales de IA
2. La **búsqueda semántica** funcionará con embeddings
3. El **análisis visual** procesará imágenes/videos con GPT-4 Vision

---

**¡El servidor está funcionando correctamente! 🚀**

URL del servidor: http://127.0.0.1:8000

Puedes comenzar a probar todos los endpoints desde Postman.
