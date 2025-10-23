# 🆓 Opciones GRATUITAS de LLM APIs para LEGAL-IA

## ⭐ RECOMENDACIÓN: Google Gemini (100% Gratis)

### Por qué usar Gemini:
- ✅ **Totalmente GRATIS** sin tarjeta de crédito
- ✅ **Multimodal:** Texto + Imágenes + Videos
- ✅ **Potente:** Comparable a GPT-4
- ✅ **Generoso:** 60 requests/minuto
- ✅ **Embeddings incluidos**

### Cómo obtener API Key (2 minutos):

1. Ve a: https://aistudio.google.com/app/apikey
2. Inicia sesión con tu cuenta de Google
3. Click en "Get API Key"
4. Click en "Create API Key in new project" (o usa proyecto existente)
5. ¡Copia tu key! Empieza con `AIza...`

### Límites Gratuitos:

```
Gemini 1.5 Flash (Recomendado):
- 15 RPM (Requests Por Minuto)
- 1 millón RPD (Requests Por Día)
- 4 millones TPM (Tokens Por Minuto)

Gemini 1.5 Pro:
- 2 RPM
- 50 RPD
- 32,000 TPM

GRATIS PARA SIEMPRE (tier gratuito permanente)
```

---

## 🔧 Configuración en tu Proyecto

### Opción 1: Google Gemini (Recomendada)

**1. Instalar SDK:**
```bash
composer require google/generative-ai-php
```

**2. Agregar a `.env`:**
```env
# Google Gemini (GRATIS)
GEMINI_API_KEY=AIza...tu-key-aqui
GEMINI_MODEL=gemini-1.5-flash
```

**3. Actualizar `config/services.php`:**
```php
'gemini' => [
    'api_key' => env('GEMINI_API_KEY'),
    'model' => env('GEMINI_MODEL', 'gemini-1.5-flash'),
],
```

---

## 📊 Comparación de Opciones Gratuitas

| Proveedor | Costo | Límite/mes | Multimodal | Embeddings | Calidad |
|-----------|-------|------------|------------|------------|---------|
| **Google Gemini** | 🆓 GRATIS | 1.5M requests | ✅ Sí | ✅ Sí | ⭐⭐⭐⭐⭐ |
| Hugging Face | 🆓 GRATIS | ~30K requests | ⚠️ Limitado | ✅ Sí | ⭐⭐⭐⭐ |
| Cohere | 🆓 Trial | 10K requests | ❌ No | ✅ Sí | ⭐⭐⭐⭐ |
| Together AI | 💵 $25 crédito | ~5M tokens | ✅ Sí | ✅ Sí | ⭐⭐⭐⭐ |
| Anthropic | 💳 Pago | $5 crédito inicial | ❌ No | ❌ No | ⭐⭐⭐⭐⭐ |
| OpenAI | 💳 Pago | $5 min | ✅ Sí | ✅ Sí | ⭐⭐⭐⭐⭐ |

---

## 🚀 Opciones Adicionales Gratuitas

### 2. **Hugging Face Inference API**

**Ventajas:**
- Completamente gratis
- Miles de modelos
- Open source

**Modelos recomendados:**
```
Texto: mistralai/Mixtral-8x7B-Instruct-v0.1
Visión: Salesforce/blip-image-captioning-large
Embeddings: sentence-transformers/all-MiniLM-L6-v2
```

**Obtener token:**
```
https://huggingface.co/settings/tokens
→ New token → Read
```

**Configuración:**
```env
HUGGINGFACE_TOKEN=hf_...tu-token
```

---

### 3. **Cohere (Trial Gratuito)**

**Ventajas:**
- Excelentes embeddings
- Modelos de texto potentes
- Diseñado para búsqueda

**Límites Trial:**
- 100 requests/minuto
- 10,000 requests/mes

**Obtener API key:**
```
https://dashboard.cohere.com/
→ Sign up (gratis)
→ API Keys → Copiar Trial key
```

**Configuración:**
```env
COHERE_API_KEY=tu-key
```

---

### 4. **Together AI ($25 gratis)**

**Ventajas:**
- $25 de crédito gratis
- Modelos rápidos
- Llama 3, Mixtral, etc.

**Obtener crédito:**
```
https://api.together.xyz/
→ Sign up
→ $25 gratis automáticos
```

---

### 5. **Replicate**

**Ventajas:**
- Pay-per-use muy barato
- Crédito inicial
- Llama 3, SDXL, etc.

**Obtener:**
```
https://replicate.com/
→ Sign up
```

---

## 💡 Estrategia Recomendada para tu Hackathon

### Stack 100% GRATIS:

```
┌─────────────────────────────────────────────────┐
│  OPCIÓN A: Solo Google Gemini (MÁS SIMPLE)      │
└─────────────────────────────────────────────────┘

Gemini 1.5 Flash para TODO:
✅ CoordinatorAgent → Gemini
✅ JurisprudenceAgent → Gemini + Gemini Embeddings
✅ VisualAnalysisAgent → Gemini (multimodal nativo)
✅ ArgumentsAgent → Gemini

PROS:
- Un solo API key
- Todo funciona igual
- Multimodal incluido
- Límites generosos

┌─────────────────────────────────────────────────┐
│  OPCIÓN B: Gemini + Hugging Face (MÁS POTENTE)  │
└─────────────────────────────────────────────────┘

Gemini para análisis:
✅ CoordinatorAgent → Gemini
✅ VisualAnalysisAgent → Gemini
✅ ArgumentsAgent → Gemini

Hugging Face para embeddings:
✅ JurisprudenceAgent → HF sentence-transformers

PROS:
- Mejor calidad de embeddings
- Todo gratis
- Más especializado
```

---

## 📦 Instalación según Opción

### OPCIÓN A: Solo Gemini (Recomendada)

```bash
# En legal-ia-backend/
composer require google/generative-ai-php
```

### OPCIÓN B: Gemini + Hugging Face

```bash
composer require google/generative-ai-php
composer require guzzlehttp/guzzle  # Para llamar API de HF
```

---

## ⚙️ Configuración Final en .env

### Para Opción A (Solo Gemini):
```env
# Google Gemini (GRATIS - RECOMENDADO)
GEMINI_API_KEY=AIza...
GEMINI_MODEL=gemini-1.5-flash

# Dejar comentadas las de pago:
# OPENAI_API_KEY=
# ANTHROPIC_API_KEY=
```

### Para Opción B (Gemini + HF):
```env
# Google Gemini
GEMINI_API_KEY=AIza...
GEMINI_MODEL=gemini-1.5-flash

# Hugging Face (para embeddings)
HUGGINGFACE_TOKEN=hf_...

# Dejar comentadas:
# OPENAI_API_KEY=
# ANTHROPIC_API_KEY=
```

---

## 🎯 Próximos Pasos

1. **Obtén tu API key de Gemini** (2 minutos)
   → https://aistudio.google.com/app/apikey

2. **Dime qué opción prefieres:**
   - Opción A: Solo Gemini (más simple)
   - Opción B: Gemini + Hugging Face (más potente)

3. **Yo modifico tu LLMService** para soportar Gemini

4. **¡Pruebas con IA REAL y GRATIS!** 🚀

---

## 📊 Costos Comparativos (para referencia)

```
Google Gemini:     $0.00/mes  ← TU OPCIÓN 🎉
Hugging Face:      $0.00/mes
Together AI:       $0.00 (hasta agotar $25)
Cohere Trial:      $0.00 (10K requests/mes)
───────────────────────────────────
OpenAI GPT-4:      ~$30/mes para uso medio
Anthropic Claude:  ~$25/mes para uso medio
```

---

## ✅ Verificación Rápida

Para verificar que tu API key de Gemini funciona:

```bash
curl "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=TU_API_KEY" \
  -H 'Content-Type: application/json' \
  -d '{"contents":[{"parts":[{"text":"Hola"}]}]}'
```

Si recibes una respuesta JSON, ¡funciona! ✅

---

## 🎓 Recursos Adicionales

- **Gemini Docs:** https://ai.google.dev/docs
- **Gemini Pricing:** https://ai.google.dev/pricing
- **Gemini Playground:** https://aistudio.google.com/
- **Rate Limits:** https://ai.google.dev/gemini-api/docs/quota

---

**¿Cuál opción prefieres? Te ayudo a implementarla de inmediato.** 🚀
