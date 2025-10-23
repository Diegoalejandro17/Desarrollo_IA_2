# Ejemplos de uso de la API LEGAL-IA

Base URL: `http://localhost:8000/api`

## Health Check

```bash
curl http://localhost:8000/api/health
```

---

## 1. CASOS LEGALES

### Crear un caso
```bash
curl -X POST http://localhost:8000/api/cases \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Accidente de tránsito en intersección",
    "description": "Colisión entre dos vehículos con testimonios contradictorios",
    "case_type": "civil",
    "parties": {
      "plaintiff": "Juan Pérez",
      "defendant": "María García"
    },
    "incident_date": "2025-10-15",
    "facts": "El día 15 de octubre de 2025, aproximadamente a las 14:30 hrs, se produjo una colisión en la intersección de Av. Principal con Calle 5..."
  }'
```

### Listar casos
```bash
# Todos los casos
curl http://localhost:8000/api/cases

# Filtrar por estado
curl http://localhost:8000/api/cases?status=draft

# Filtrar por tipo
curl http://localhost:8000/api/cases?case_type=civil

# Con paginación
curl http://localhost:8000/api/cases?per_page=10
```

### Ver caso específico
```bash
curl http://localhost:8000/api/cases/{uuid}
```

### Actualizar caso
```bash
curl -X PUT http://localhost:8000/api/cases/{uuid} \
  -H "Content-Type: application/json" \
  -d '{
    "status": "analyzed",
    "facts": "Información actualizada..."
  }'
```

### Estadísticas
```bash
curl http://localhost:8000/api/cases/stats
```

---

## 2. EVIDENCIA

### Subir evidencia (imagen/video)
```bash
curl -X POST http://localhost:8000/api/cases/{case_uuid}/evidence \
  -F "title=Video de cámara de seguridad" \
  -F "description=Grabación del momento del accidente" \
  -F "type=video" \
  -F "file=@/path/to/video.mp4"
```

### Subir evidencia desde Supabase Storage
```bash
curl -X POST http://localhost:8000/api/cases/{case_uuid}/evidence \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Video de cámara de seguridad",
    "description": "Grabación del momento del accidente",
    "type": "video",
    "file_url": "https://your-supabase-url.supabase.co/storage/v1/object/public/evidence/video.mp4",
    "mime_type": "video/mp4",
    "file_size": 15728640
  }'
```

### Listar evidencia de un caso
```bash
curl http://localhost:8000/api/cases/{case_uuid}/evidence

# Filtrar por tipo
curl http://localhost:8000/api/cases/{case_uuid}/evidence?type=image

# Solo evidencia analizada
curl http://localhost:8000/api/cases/{case_uuid}/evidence?is_analyzed=true
```

### Carga masiva de evidencia
```bash
curl -X POST http://localhost:8000/api/cases/{case_uuid}/evidence/bulk \
  -F "type=image" \
  -F "files[]=@/path/to/image1.jpg" \
  -F "files[]=@/path/to/image2.jpg" \
  -F "files[]=@/path/to/image3.jpg"
```

### Analizar evidencia visual
```bash
curl -X POST http://localhost:8000/api/evidence/{evidence_uuid}/analyze
```

### Ver resultado del análisis
```bash
curl http://localhost:8000/api/evidence/{evidence_uuid}/analysis
```

---

## 3. ANÁLISIS

### Iniciar análisis de un caso
```bash
curl -X POST http://localhost:8000/api/cases/{case_uuid}/analyze
```

Respuesta:
```json
{
  "success": true,
  "message": "Análisis iniciado exitosamente",
  "data": {
    "case": { ... },
    "analysis": {
      "uuid": "...",
      "status": "pending",
      "version": 1
    }
  }
}
```

### Ver análisis de un caso
```bash
# Todos los análisis
curl http://localhost:8000/api/cases/{case_uuid}/analysis

# Por versión específica
curl http://localhost:8000/api/cases/{case_uuid}/analysis?version=2

# Solo completados
curl http://localhost:8000/api/cases/{case_uuid}/analysis?status=completed
```

### Ver último análisis completado
```bash
curl http://localhost:8000/api/cases/{case_uuid}/analysis/latest
```

### Re-analizar caso (nueva versión)
```bash
curl -X POST http://localhost:8000/api/cases/{case_uuid}/re-analyze
```

### Cancelar análisis en proceso
```bash
curl -X POST http://localhost:8000/api/analysis/{analysis_uuid}/cancel
```

### Estadísticas de análisis
```bash
curl http://localhost:8000/api/analysis/stats
```

---

## 4. JURISPRUDENCIA

### Crear jurisprudencia
```bash
curl -X POST http://localhost:8000/api/jurisprudence \
  -H "Content-Type: application/json" \
  -d '{
    "case_number": "STC-2024-12345",
    "court": "Corte Suprema",
    "jurisdiction": "Nacional",
    "decision_date": "2024-08-15",
    "case_title": "Responsabilidad civil en accidente de tránsito",
    "summary": "La Corte Suprema establece que...",
    "ruling": "Se confirma la sentencia...",
    "legal_reasoning": "Considerando que...",
    "keywords": ["accidente_transito", "responsabilidad_civil", "daños_perjuicios"],
    "articles_cited": ["Art. 1902 CC", "Art. 1903 CC"],
    "relevance_level": "high",
    "url": "https://ejemplo.com/sentencia/12345"
  }'
```

### Listar jurisprudencia
```bash
# Toda la jurisprudencia
curl http://localhost:8000/api/jurisprudence

# Por tribunal
curl http://localhost:8000/api/jurisprudence?court=Suprema

# Alta relevancia
curl http://localhost:8000/api/jurisprudence?relevance_level=high

# Últimos 5 años
curl http://localhost:8000/api/jurisprudence?recent_years=5

# Por palabra clave
curl http://localhost:8000/api/jurisprudence?keyword=accidente_transito
```

### Búsqueda full-text
```bash
curl -X POST http://localhost:8000/api/jurisprudence/search \
  -H "Content-Type: application/json" \
  -d '{
    "query": "responsabilidad civil accidente",
    "limit": 10
  }'
```

### Búsqueda semántica (requiere API key LLM)
```bash
curl -X POST http://localhost:8000/api/jurisprudence/semantic-search \
  -H "Content-Type: application/json" \
  -d '{
    "query": "casos similares de colisión vehicular con testimonios contradictorios",
    "limit": 10,
    "min_similarity": 0.75
  }'
```

### Encontrar precedentes similares
```bash
curl -X POST http://localhost:8000/api/jurisprudence/find-similar \
  -H "Content-Type: application/json" \
  -d '{
    "case_description": "Accidente de tránsito en intersección con cámara de seguridad y testimonios contradictorios. El demandante alega que el semáforo estaba en verde...",
    "case_type": "civil",
    "limit": 5
  }'
```

### Estadísticas
```bash
curl http://localhost:8000/api/jurisprudence/stats
```

---

## Flujo completo de ejemplo

### 1. Crear un caso
```bash
CASE_RESPONSE=$(curl -s -X POST http://localhost:8000/api/cases \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Accidente de tránsito - Caso de práctica",
    "description": "Caso simulado para estudiante de derecho",
    "case_type": "civil",
    "parties": {
      "plaintiff": "Juan Pérez",
      "defendant": "María García"
    },
    "incident_date": "2025-10-15",
    "facts": "Colisión en intersección con testimonios contradictorios"
  }')

CASE_UUID=$(echo $CASE_RESPONSE | jq -r '.data.uuid')
echo "Caso creado: $CASE_UUID"
```

### 2. Subir evidencia
```bash
curl -X POST http://localhost:8000/api/cases/$CASE_UUID/evidence \
  -F "title=Video de cámara de seguridad" \
  -F "type=video" \
  -F "file=@/path/to/video.mp4"

curl -X POST http://localhost:8000/api/cases/$CASE_UUID/evidence \
  -F "title=Testimonio escrito del demandante" \
  -F "type=document" \
  -F "file=@/path/to/testimonio.pdf"
```

### 3. Iniciar análisis
```bash
ANALYSIS_RESPONSE=$(curl -s -X POST http://localhost:8000/api/cases/$CASE_UUID/analyze)
ANALYSIS_UUID=$(echo $ANALYSIS_RESPONSE | jq -r '.data.analysis.uuid')
echo "Análisis iniciado: $ANALYSIS_UUID"
```

### 4. Verificar estado del análisis
```bash
curl http://localhost:8000/api/analysis/$ANALYSIS_UUID
```

### 5. Obtener último análisis completado
```bash
curl http://localhost:8000/api/cases/$CASE_UUID/analysis/latest
```

---

## Testing con Postman

Puedes importar esta colección a Postman:

```json
{
  "info": {
    "name": "LEGAL-IA API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "variable": [
    {
      "key": "base_url",
      "value": "http://localhost:8000/api"
    }
  ]
}
```

---

## Respuestas comunes

### Éxito
```json
{
  "success": true,
  "message": "Mensaje descriptivo",
  "data": { ... }
}
```

### Error de validación
```json
{
  "success": false,
  "errors": {
    "title": ["El campo título es obligatorio"],
    "case_type": ["El tipo de caso seleccionado no es válido"]
  }
}
```

### No encontrado
```json
{
  "success": false,
  "message": "Caso no encontrado"
}
```

### Procesamiento asíncrono
```json
{
  "success": true,
  "message": "Análisis iniciado exitosamente",
  "data": { ... }
}
```
HTTP Status: 202 Accepted
