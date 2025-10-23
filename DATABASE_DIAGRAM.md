# 🗄️ Diagrama de Base de Datos - LEGAL-IA

## Estructura General

```
┌─────────────────────┐
│   legal_cases       │
│  (Casos Legales)    │
└──────────┬──────────┘
           │
           │ 1:N
           ├──────────────────────┐
           │                      │
           ▼                      ▼
┌─────────────────────┐  ┌─────────────────────┐
│     evidence        │  │  cases_analysis     │
│   (Evidencias)      │  │    (Análisis)       │
└─────────────────────┘  └─────────────────────┘
                                  │
                                  │ 1:1 (versiones)
                                  ▼
                         ┌─────────────────────┐
                         │  cases_analysis     │
                         │ (Versión anterior)  │
                         └─────────────────────┘

┌─────────────────────┐
│   jurisprudence     │
│  (Precedentes)      │
│  (Tabla separada)   │
└─────────────────────┘
```

---

## 📊 Tablas Detalladas

### **1. legal_cases** (Casos Legales)
```sql
┌─────────────────────┬──────────────┬─────────────┬──────────────────┐
│ Campo               │ Tipo         │ Null        │ Descripción      │
├─────────────────────┼──────────────┼─────────────┼──────────────────┤
│ id                  │ BIGINT       │ NOT NULL PK │ ID autoincremental
│ uuid                │ UUID         │ NOT NULL UQ │ Identificador único
│ title               │ VARCHAR(255) │ NOT NULL    │ Título del caso
│ description         │ TEXT         │ NOT NULL    │ Descripción detallada
│ case_type           │ ENUM         │ NOT NULL    │ civil|penal|laboral|
│                     │              │             │ administrativo|
│                     │              │             │ constitucional|otro
│ status              │ ENUM         │ NOT NULL    │ draft|analyzing|
│                     │              │             │ analyzed|archived
│ parties             │ JSON         │ NULL        │ {demandante, demandado}
│ incident_date       │ DATE         │ NULL        │ Fecha del incidente
│ facts               │ TEXT         │ NULL        │ Hechos del caso
│ metadata            │ JSON         │ NULL        │ Metadata adicional
│ created_at          │ TIMESTAMP    │ NOT NULL    │ Fecha creación
│ updated_at          │ TIMESTAMP    │ NOT NULL    │ Fecha actualización
│ deleted_at          │ TIMESTAMP    │ NULL        │ Soft delete
└─────────────────────┴──────────────┴─────────────┴──────────────────┘

Índices:
- PRIMARY KEY (id)
- UNIQUE (uuid)
- INDEX (status)
- INDEX (case_type)
- INDEX (created_at)
```

**Ejemplo de datos JSON en `parties`:**
```json
{
  "demandante": {
    "nombre": "Juan Pérez",
    "tipo": "persona_natural",
    "representante": "Abogado María González"
  },
  "demandado": {
    "nombre": "Empresa XYZ S.A.",
    "tipo": "persona_juridica"
  }
}
```

---

### **2. evidence** (Evidencias Multimedia)
```sql
┌─────────────────────┬──────────────┬─────────────┬──────────────────┐
│ Campo               │ Tipo         │ Null        │ Descripción      │
├─────────────────────┼──────────────┼─────────────┼──────────────────┤
│ id                  │ BIGINT       │ NOT NULL PK │ ID autoincremental
│ uuid                │ UUID         │ NOT NULL UQ │ Identificador único
│ legal_case_id       │ BIGINT       │ NOT NULL FK │ → legal_cases.id
│ type                │ ENUM         │ NOT NULL    │ document|image|
│                     │              │             │ video|audio|
│                     │              │             │ testimony|other
│ title               │ VARCHAR(255) │ NOT NULL    │ Título/nombre
│ description         │ TEXT         │ NULL        │ Descripción
│ file_path           │ VARCHAR(500) │ NULL        │ Ruta en servidor
│ file_url            │ VARCHAR(500) │ NULL        │ URL pública
│ file_size           │ BIGINT       │ NULL        │ Tamaño en bytes
│ mime_type           │ VARCHAR(100) │ NULL        │ image/jpeg, etc.
│ analysis_result     │ JSON         │ NULL        │ Resultado análisis IA
│ is_analyzed         │ BOOLEAN      │ DEFAULT 0   │ ¿Ya fue analizado?
│ analyzed_at         │ TIMESTAMP    │ NULL        │ Fecha análisis
│ metadata            │ JSON         │ NULL        │ Metadata adicional
│ created_at          │ TIMESTAMP    │ NOT NULL    │ Fecha creación
│ updated_at          │ TIMESTAMP    │ NOT NULL    │ Fecha actualización
│ deleted_at          │ TIMESTAMP    │ NULL        │ Soft delete
└─────────────────────┴──────────────┴─────────────┴──────────────────┘

Índices:
- PRIMARY KEY (id)
- UNIQUE (uuid)
- FOREIGN KEY (legal_case_id) REFERENCES legal_cases(id) ON DELETE CASCADE
- INDEX (is_analyzed)
- INDEX (type)
```

**Ejemplo de datos JSON en `analysis_result`:**
```json
{
  "analysis": "Se observa un contrato firmado entre...",
  "key_elements": [
    "Firma del demandado",
    "Fecha: 2024-03-15",
    "Cláusula penal incumplida"
  ],
  "legal_relevance": "Alta - Evidencia directa del incumplimiento contractual",
  "confidence": 0.95
}
```

---

### **3. jurisprudence** (Precedentes Legales)
```sql
┌─────────────────────┬──────────────┬─────────────┬──────────────────┐
│ Campo               │ Tipo         │ Null        │ Descripción      │
├─────────────────────┼──────────────┼─────────────┼──────────────────┤
│ id                  │ BIGINT       │ NOT NULL PK │ ID autoincremental
│ uuid                │ UUID         │ NOT NULL UQ │ Identificador único
│ case_number         │ VARCHAR(255) │ NOT NULL    │ Nro. expediente
│ case_title          │ VARCHAR(500) │ NOT NULL    │ Título del caso
│ court               │ VARCHAR(255) │ NOT NULL    │ Tribunal/Corte
│ decision_date       │ DATE         │ NOT NULL    │ Fecha sentencia
│ case_type           │ ENUM         │ NOT NULL    │ civil|penal|etc.
│ summary             │ TEXT         │ NOT NULL    │ Resumen del caso
│ legal_reasoning     │ TEXT         │ NOT NULL    │ Razonamiento legal
│ decision            │ TEXT         │ NOT NULL    │ Decisión/fallo
│ embedding           │ JSON         │ NULL        │ Vector para búsqueda
│                     │              │             │ semántica (1536 dims)
│ keywords            │ JSON         │ NULL        │ ["keyword1", ...]
│ articles_cited      │ JSON         │ NULL        │ Artículos citados
│ relevance_score     │ DECIMAL(3,2) │ NULL        │ 0.00 - 1.00
│ url                 │ VARCHAR(500) │ NULL        │ URL sentencia
│ metadata            │ JSON         │ NULL        │ Metadata adicional
│ created_at          │ TIMESTAMP    │ NOT NULL    │ Fecha creación
│ updated_at          │ TIMESTAMP    │ NOT NULL    │ Fecha actualización
│ deleted_at          │ TIMESTAMP    │ NULL        │ Soft delete
└─────────────────────┴──────────────┴─────────────┴──────────────────┘

Índices:
- PRIMARY KEY (id)
- UNIQUE (uuid)
- INDEX (case_type)
- INDEX (decision_date)
- INDEX (court)
- FULLTEXT (case_title, summary, legal_reasoning)
- VECTOR INDEX en embedding (usando pgvector)
```

**Ejemplo de datos JSON en `embedding`:**
```json
[0.0234, -0.0123, 0.0456, ..., 0.0789]  // Array de 1536 números
```

**Ejemplo de datos JSON en `articles_cited`:**
```json
[
  {
    "articulo": "Art. 1546 Código Civil",
    "texto": "Todo contrato legalmente celebrado es una ley para los contratantes"
  },
  {
    "articulo": "Art. 1489 Código Civil",
    "texto": "Condición resolutoria tácita"
  }
]
```

---

### **4. cases_analysis** (Análisis de Casos)
```sql
┌─────────────────────┬──────────────┬─────────────┬──────────────────┐
│ Campo               │ Tipo         │ Null        │ Descripción      │
├─────────────────────┼──────────────┼─────────────┼──────────────────┤
│ id                  │ BIGINT       │ NOT NULL PK │ ID autoincremental
│ uuid                │ UUID         │ NOT NULL UQ │ Identificador único
│ legal_case_id       │ BIGINT       │ NOT NULL FK │ → legal_cases.id
│ status              │ ENUM         │ NOT NULL    │ pending|processing|
│                     │              │             │ completed|failed
│ coordinator_result  │ JSON         │ NULL        │ Resultado Coordinador
│ jurisprudence_result│ JSON         │ NULL        │ Resultado Jurisprudencia
│ visual_analysis_    │ JSON         │ NULL        │ Resultado Visual
│    result           │              │             │
│ arguments_result    │ JSON         │ NULL        │ Resultado Argumentos
│ executive_summary   │ TEXT         │ NULL        │ Resumen ejecutivo
│ confidence_scores   │ JSON         │ NULL        │ Scores de confianza
│ version             │ INTEGER      │ DEFAULT 1   │ Versión del análisis
│ previous_analysis_id│ BIGINT       │ NULL FK     │ → cases_analysis.id
│                     │              │             │   (versión anterior)
│ agent_execution_log │ JSON         │ NULL        │ Log de ejecución
│ started_at          │ TIMESTAMP    │ NULL        │ Inicio análisis
│ completed_at        │ TIMESTAMP    │ NULL        │ Fin análisis
│ processing_time     │ DECIMAL(10,2)│ NULL        │ Tiempo en segundos
│ created_at          │ TIMESTAMP    │ NOT NULL    │ Fecha creación
│ updated_at          │ TIMESTAMP    │ NOT NULL    │ Fecha actualización
│ deleted_at          │ TIMESTAMP    │ NULL        │ Soft delete
└─────────────────────┴──────────────┴─────────────┴──────────────────┘

Índices:
- PRIMARY KEY (id)
- UNIQUE (uuid)
- FOREIGN KEY (legal_case_id) REFERENCES legal_cases(id) ON DELETE CASCADE
- FOREIGN KEY (previous_analysis_id) REFERENCES cases_analysis(id)
- INDEX (status)
- INDEX (version)
```

**Ejemplo de datos JSON en `coordinator_result`:**
```json
{
  "legal_elements": [
    "Incumplimiento contractual",
    "Daños y perjuicios",
    "Condición resolutoria tácita"
  ],
  "complexity_level": "medio",
  "recommended_approach": "Enfoque contractual con énfasis en...",
  "jurisprudence_search_areas": [
    "incumplimiento contratos de servicios",
    "indemnización daños contractuales"
  ]
}
```

**Ejemplo de datos JSON en `jurisprudence_result`:**
```json
{
  "precedents": [
    {
      "uuid": "abc-123",
      "case_title": "González vs. Empresa ABC",
      "similarity_score": 0.89,
      "relevance": "Alta - caso idéntico de incumplimiento contractual"
    }
  ],
  "total_found": 5,
  "search_quality": "excelente"
}
```

**Ejemplo de datos JSON en `arguments_result`:**
```json
{
  "defense_lines": [
    {
      "title": "Incumplimiento contractual probado",
      "description": "Existe evidencia clara del incumplimiento...",
      "strength": "alta",
      "precedents_support": ["uuid-1", "uuid-2"]
    }
  ],
  "alternative_scenarios": ["Negociación extrajudicial", "Mediación"],
  "recommended_strategy": "Demandar por resolución del contrato...",
  "risks": ["Posible demora procesal", "Costos legales elevados"]
}
```

**Ejemplo de datos JSON en `agent_execution_log`:**
```json
[
  {
    "timestamp": "2025-10-21T18:31:25Z",
    "agent": "coordinator",
    "event": "execution_started",
    "data": {}
  },
  {
    "timestamp": "2025-10-21T18:31:27Z",
    "agent": "coordinator",
    "event": "execution_completed",
    "data": {
      "execution_time_ms": 1596.45,
      "result_size": 511
    }
  }
]
```

---

## 🔗 Relaciones entre Tablas

```
legal_cases (1) ────────── (N) evidence
    │
    │
    └─────────────────────── (N) cases_analysis
                                      │
                                      │ (self-reference)
                                      └── (1) cases_analysis
                                          (previous_analysis_id)

jurisprudence (tabla independiente, referenciada por UUID en los análisis)
```

---

## 📈 Capacidad y Rendimiento

### **Volumetría Estimada (Hackathon):**
- `legal_cases`: ~10-50 casos
- `evidence`: ~20-200 archivos
- `jurisprudence`: ~100-500 precedentes
- `cases_analysis`: ~10-50 análisis

### **Índices Críticos para Rendimiento:**
1. **UUID indexes** - Búsquedas rápidas por identificador único
2. **FULLTEXT index** en jurisprudence - Búsqueda textual
3. **Vector index** (pgvector) - Búsqueda semántica O(log n)
4. **Foreign keys** con CASCADE - Mantiene integridad referencial

### **pgvector para Búsqueda Semántica:**
```sql
-- En Supabase, debes ejecutar esto manualmente:
ALTER TABLE jurisprudence
ADD COLUMN embedding_vector vector(1536);

CREATE INDEX ON jurisprudence
USING ivfflat (embedding_vector vector_cosine_ops);
```

---

## 🎯 Casos de Uso

### 1. **Crear un caso completo**
```
INSERT legal_cases →
INSERT evidence (múltiples) →
INSERT cases_analysis (pending)
```

### 2. **Ejecutar análisis**
```
UPDATE cases_analysis (status=processing) →
Ejecutar 4 agentes A2A →
UPDATE cases_analysis (todos los resultados JSON) →
UPDATE legal_cases (status=analyzed)
```

### 3. **Buscar precedentes similares**
```
Generar embedding del caso →
SELECT jurisprudence WHERE embedding <=> query_embedding
ORDER BY similarity DESC
LIMIT 10
```

### 4. **Re-analizar caso (nueva versión)**
```
INSERT cases_analysis (version=2, previous_analysis_id=old_id) →
Ejecutar análisis →
Comparar con versión anterior
```

---

## 🛡️ Integridad y Seguridad

### **Soft Deletes:**
Todas las tablas tienen `deleted_at` para eliminación lógica (no física)

### **Cascadas:**
- Si eliminas un `legal_case`, se eliminan automáticamente:
  - Todas las `evidence` asociadas
  - Todos los `cases_analysis` asociados

### **Validaciones:**
- UUIDs únicos en todas las tablas
- ENUMs para valores predefinidos (evita datos inválidos)
- NOT NULL en campos críticos
- JSON validation en aplicación (Laravel)

---

## 📊 Diagrama Visual Simplificado

```
┌──────────────────────────────────────────────────────────────┐
│                        LEGAL-IA DB                           │
└──────────────────────────────────────────────────────────────┘

    ┌─────────────────┐
    │  legal_cases    │ ◄── Tabla principal
    │  - uuid         │
    │  - title        │
    │  - case_type    │
    │  - status       │ 
    │  - parties (JSON)│
    └────────┬────────┘
             │
         1   │   N
             │
    ┌────────┴────────┬──────────────────┐
    │                 │                  │
    ▼                 ▼                  ▼
┌─────────┐    ┌──────────────┐   ┌──────────────┐
│evidence │    │cases_analysis│   │jurisprudence │
│- uuid   │    │- uuid        │   │- uuid        │
│- type   │    │- coordinator_│   │- embedding   │
│- file   │    │  result (JSON)│   │  (vector)   │
│- analysis│    │- jurisprudence│   │- similarity │
│  (JSON) │    │  _result (JSON)│   │  search     │
└─────────┘    │- arguments_  │   └──────────────┘
               │  result (JSON)│   (Independiente)
               └──────────────┘
```

---

Este diseño está **optimizado para:**
✅ A2A (Agent-to-Agent) - Resultados JSON por agente
✅ MCP (Model Context Protocol) - Contexto estructurado
✅ Búsqueda semántica - pgvector embeddings
✅ Versionamiento - Historial de análisis
✅ Escalabilidad - Índices y relaciones eficientes
✅ Auditoría - Logs de ejecución y timestamps

