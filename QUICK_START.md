# LEGAL-IA Backend - Quick Start

## 🚀 Configuración inicial

### 1. Verificar que estés en el directorio correcto
```bash
cd legal-ia-backend
```

### 2. Copiar y configurar el archivo .env
```bash
cp .env.example .env
```

Edita `.env` y configura:
```env
# Base de datos (Supabase PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=db.your-supabase-project.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=tu-password-supabase

# API Keys de LLM (para análisis con IA)
OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...

# Configuración de la aplicación
APP_URL=http://localhost:8000
```

### 3. Instalar dependencias (si no lo has hecho)
```bash
composer install
```

### 4. Generar key de la aplicación
```bash
php artisan key:generate
```

### 5. Ejecutar migraciones
```bash
php artisan migrate
```

Esperarás ver:
```
2025_10_21_145108_create_legal_cases_table ............... DONE
2025_10_21_145130_create_evidence_table .................. DONE
2025_10_21_145201_create_jurisprudence_table ............. DONE
2025_10_21_145249_create_cases_analysis_table ............ DONE
```

### 6. Crear enlace de storage (para archivos)
```bash
php artisan storage:link
```

### 7. Iniciar el servidor
```bash
php artisan serve
```

El servidor estará corriendo en: **http://localhost:8000**

---

## 🧪 Probar la API

### Health check
```bash
curl http://localhost:8000/api/health
```

Deberías ver:
```json
{
  "status": "ok",
  "service": "LEGAL-IA API",
  "version": "1.0.0",
  "timestamp": "2025-10-21T..."
}
```

### Ver todas las rutas disponibles
```bash
php artisan route:list --path=api
```

### Crear tu primer caso
```bash
curl -X POST http://localhost:8000/api/cases \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Mi primer caso de prueba",
    "description": "Caso de ejemplo para la hackathon",
    "case_type": "civil",
    "facts": "Este es un caso de práctica"
  }'
```

---

## 📋 Rutas API principales

### Casos (Cases)
- `GET /api/cases` - Listar casos
- `POST /api/cases` - Crear caso
- `GET /api/cases/{uuid}` - Ver caso
- `PUT /api/cases/{uuid}` - Actualizar caso
- `DELETE /api/cases/{uuid}` - Eliminar caso
- `GET /api/cases/stats` - Estadísticas

### Análisis (Analysis)
- `POST /api/cases/{uuid}/analyze` - Iniciar análisis
- `GET /api/cases/{uuid}/analysis` - Ver análisis
- `GET /api/cases/{uuid}/analysis/latest` - Último análisis
- `POST /api/cases/{uuid}/re-analyze` - Re-analizar
- `GET /api/analysis/stats` - Estadísticas

### Evidencia (Evidence)
- `GET /api/cases/{uuid}/evidence` - Listar evidencia
- `POST /api/cases/{uuid}/evidence` - Subir evidencia
- `POST /api/cases/{uuid}/evidence/bulk` - Subir múltiples
- `POST /api/evidence/{uuid}/analyze` - Analizar visual
- `GET /api/evidence/{uuid}/analysis` - Ver análisis

### Jurisprudencia (Jurisprudence)
- `GET /api/jurisprudence` - Listar
- `POST /api/jurisprudence` - Crear
- `POST /api/jurisprudence/search` - Búsqueda full-text
- `POST /api/jurisprudence/semantic-search` - Búsqueda semántica
- `POST /api/jurisprudence/find-similar` - Precedentes similares

---

## 📁 Estructura del proyecto

```
legal-ia-backend/
├── app/
│   ├── Models/
│   │   ├── LegalCase.php         ✅ Modelo de casos
│   │   ├── Evidence.php          ✅ Modelo de evidencia
│   │   ├── Jurisprudence.php     ✅ Modelo de jurisprudencia
│   │   └── CaseAnalysis.php      ✅ Modelo de análisis
│   │
│   └── Http/Controllers/Api/
│       ├── CaseController.php            ✅ CRUD casos
│       ├── AnalysisController.php        ✅ Análisis con agentes
│       ├── JurisprudenceController.php   ✅ Jurisprudencia
│       └── EvidenceController.php        ✅ Evidencia multimedia
│
├── database/
│   └── migrations/
│       ├── *_create_legal_cases_table.php      ✅
│       ├── *_create_evidence_table.php         ✅
│       ├── *_create_jurisprudence_table.php    ✅
│       └── *_create_cases_analysis_table.php   ✅
│
├── routes/
│   └── api.php                   ✅ Rutas configuradas
│
├── API_ENDPOINTS.md             ✅ Documentación endpoints
├── API_EXAMPLES.md              ✅ Ejemplos de uso
└── QUICK_START.md               ✅ Esta guía
```

---

## 🔧 Próximos pasos para la hackathon

### Implementar los Services (IMPORTANTE)
```
app/Services/
├── LLMService.php              ⏳ Servicio para OpenAI/Anthropic
├── MCPService.php              ⏳ Model Context Protocol
└── AgentOrchestrator.php       ⏳ Orquestador de agentes A2A
```

### Implementar los Agentes A2A
```
app/Services/Agents/
├── CoordinatorAgent.php        ⏳ Agente coordinador
├── JurisprudenceAgent.php      ⏳ Búsqueda de precedentes
├── VisualAnalysisAgent.php     ⏳ Análisis de imágenes/videos
└── ArgumentsAgent.php          ⏳ Generación de argumentos
```

### Seeders con datos de prueba
```bash
php artisan make:seeder JurisprudenceSeeder
php artisan db:seed --class=JurisprudenceSeeder
```

---

## 🐛 Troubleshooting

### Error de conexión a la base de datos
Verifica tu configuración en `.env` y que tu base de datos Supabase esté activa.

### Error "Storage not linked"
```bash
php artisan storage:link
```

### Ver logs
```bash
tail -f storage/logs/laravel.log
```

### Limpiar cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## 📊 Estado actual del proyecto

✅ **Completado:**
- Base de datos (4 tablas con migraciones)
- Modelos Eloquent con relaciones
- Controllers API completos
- Rutas configuradas
- Documentación de endpoints

⏳ **Pendiente (siguiente fase):**
- Services (LLM, MCP, AgentOrchestrator)
- Agentes A2A (4 agentes)
- Jobs para procesamiento asíncrono
- Seeders con datos de prueba
- Tests unitarios

---

## 🎯 Para la demo de la hackathon

1. **Crear casos de ejemplo** con `POST /api/cases`
2. **Subir evidencia visual** con `POST /api/cases/{uuid}/evidence`
3. **Cargar jurisprudencia** con `POST /api/jurisprudence`
4. **Iniciar análisis** con `POST /api/cases/{uuid}/analyze`
5. **Mostrar resultados** con `GET /api/cases/{uuid}/analysis/latest`

---

## 🆘 Ayuda

- **Documentación completa:** Ver `API_ENDPOINTS.md`
- **Ejemplos de uso:** Ver `API_EXAMPLES.md`
- **Laravel Docs:** https://laravel.com/docs

**¡Éxito en tu hackathon! 🚀**
