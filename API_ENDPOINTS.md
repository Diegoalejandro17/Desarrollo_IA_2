# LEGAL-IA API Endpoints

## Casos Legales (CaseController)

### Listar casos
```
GET /api/cases
Query params: status, case_type, recent_days, per_page
```

### Crear caso
```
POST /api/cases
Body: {
  "title": "string",
  "description": "string",
  "case_type": "civil|penal|laboral|administrativo|constitucional|otro",
  "parties": {
    "plaintiff": "string",
    "defendant": "string"
  },
  "incident_date": "date",
  "facts": "string",
  "metadata": {}
}
```

### Ver caso específico
```
GET /api/cases/{uuid}
```

### Actualizar caso
```
PUT/PATCH /api/cases/{uuid}
```

### Eliminar caso
```
DELETE /api/cases/{uuid}
```

### Estadísticas de casos
```
GET /api/cases/stats
```

---

## Análisis (AnalysisController)

### Iniciar análisis de caso
```
POST /api/cases/{uuid}/analyze
```

### Obtener análisis de un caso
```
GET /api/cases/{uuid}/analysis
Query params: version, status
```

### Ver análisis específico
```
GET /api/analysis/{uuid}
```

### Obtener último análisis completado
```
GET /api/cases/{uuid}/analysis/latest
```

### Re-analizar caso (nueva versión)
```
POST /api/cases/{uuid}/re-analyze
```

### Cancelar análisis en proceso
```
POST /api/analysis/{uuid}/cancel
```

### Estadísticas de análisis
```
GET /api/analysis/stats
```

---

## Jurisprudencia (JurisprudenceController)

### Listar jurisprudencia
```
GET /api/jurisprudence
Query params: court, jurisdiction, relevance_level, recent_years, keyword, per_page
```

### Crear jurisprudencia
```
POST /api/jurisprudence
Body: {
  "case_number": "string (único)",
  "court": "string",
  "jurisdiction": "string",
  "decision_date": "date",
  "case_title": "string",
  "summary": "string",
  "ruling": "string",
  "legal_reasoning": "string",
  "keywords": ["keyword1", "keyword2"],
  "articles_cited": ["art1", "art2"],
  "url": "url",
  "full_text": "string",
  "relevance_level": "high|medium|low",
  "metadata": {}
}
```

### Ver jurisprudencia específica
```
GET /api/jurisprudence/{uuid}
```

### Actualizar jurisprudencia
```
PUT/PATCH /api/jurisprudence/{uuid}
```

### Eliminar jurisprudencia
```
DELETE /api/jurisprudence/{uuid}
```

### Búsqueda full-text
```
POST /api/jurisprudence/search
Body: {
  "query": "string",
  "limit": 10
}
```

### Búsqueda semántica (con embeddings)
```
POST /api/jurisprudence/semantic-search
Body: {
  "query": "string",
  "limit": 10,
  "min_similarity": 0.7
}
Note: Requiere API key de LLM configurada
```

### Encontrar precedentes similares
```
POST /api/jurisprudence/find-similar
Body: {
  "case_description": "string",
  "case_type": "string",
  "limit": 10
}
Note: Será implementado con AgentOrchestrator
```

### Estadísticas de jurisprudencia
```
GET /api/jurisprudence/stats
```

---

## Evidencia (EvidenceController)

### Listar evidencia de un caso
```
GET /api/cases/{uuid}/evidence
Query params: type, is_analyzed
```

### Subir evidencia
```
POST /api/cases/{uuid}/evidence
Body (multipart/form-data): {
  "title": "string",
  "description": "string",
  "type": "document|image|video|audio|testimony|other",
  "file": file (max 50MB)
}
O con URL de Supabase:
{
  "title": "string",
  "description": "string",
  "type": "document|image|video|audio|testimony|other",
  "file_url": "url",
  "mime_type": "string",
  "file_size": number
}
```

### Ver evidencia específica
```
GET /api/evidence/{uuid}
```

### Actualizar metadatos de evidencia
```
PUT/PATCH /api/evidence/{uuid}
Body: {
  "title": "string",
  "description": "string",
  "metadata": {}
}
```

### Eliminar evidencia
```
DELETE /api/evidence/{uuid}
```

### Analizar evidencia visual
```
POST /api/evidence/{uuid}/analyze
Note: Solo para evidencia tipo image/video
```

### Obtener resultado de análisis
```
GET /api/evidence/{uuid}/analysis
```

### Carga masiva de evidencia
```
POST /api/cases/{uuid}/evidence/bulk
Body (multipart/form-data): {
  "files[]": [file1, file2, ...] (max 10 archivos),
  "type": "document|image|video|audio|testimony|other"
}
```

---

## Modelos de Datos

### LegalCase
- uuid, title, description
- case_type: civil, penal, laboral, administrativo, constitucional, otro
- status: draft, analyzing, analyzed, archived
- parties (JSON), incident_date, facts, metadata
- Relaciones: evidence, analyses, user

### Evidence
- uuid, title, description, type
- file_path, file_url, mime_type, file_size
- analysis_result (JSON), is_analyzed, analyzed_at
- Relación: legalCase

### Jurisprudence
- uuid, case_number, court, jurisdiction, decision_date
- case_title, summary, ruling, legal_reasoning
- keywords (array), articles_cited (array)
- embedding (array) - para búsqueda semántica
- relevance_level: high, medium, low

### CaseAnalysis
- uuid, status: pending, processing, completed, failed
- coordinator_result, jurisprudence_result, visual_analysis_result, arguments_result (JSON)
- legal_elements, relevant_precedents, defense_lines, alternative_scenarios (JSON)
- confidence_scores (JSON), processing_time, agent_execution_log (JSON)
- version, previous_analysis_id
- Relaciones: legalCase, previousAnalysis

---

## Próximos pasos

### Configuración pendiente:
1. **API Keys de LLM**: Configurar en `.env`
   - OPENAI_API_KEY=tu-key
   - ANTHROPIC_API_KEY=tu-key

2. **Ejecutar migraciones**:
   ```bash
   php artisan migrate
   ```

3. **Configurar rutas API** en `routes/api.php`

4. **Implementar Servicios**:
   - LLMService (OpenAI + Anthropic)
   - MCPService (Model Context Protocol)
   - AgentOrchestrator (Coordinador de Agentes A2A)

5. **Implementar Agentes A2A**:
   - CoordinatorAgent
   - JurisprudenceAgent
   - VisualAnalysisAgent
   - ArgumentsAgent

6. **Opcional**: Configurar Jobs/Queues para procesamiento asíncrono
   - AnalyzeCaseJob
   - AnalyzeVisualEvidenceJob
   - GenerateJurisprudenceEmbeddingJob
