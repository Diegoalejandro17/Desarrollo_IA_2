# 🇨🇴 MCP Fetch Server - CONFIGURADO PARA COLOMBIA

## ✅ **ACTUALIZADO: Jurisprudencia Colombiana**

Tu sistema ahora está configurado para buscar **jurisprudencia y leyes de COLOMBIA**.

---

## 🌐 **Sitios Web Colombianos Configurados:**

El MCP Fetch Server ahora consulta estos sitios oficiales:

### **1. Corte Constitucional de Colombia**
```
https://www.corteconstitucional.gov.co/relatoria/
```
- Sentencias de tutela
- Sentencias de constitucionalidad
- Autos

### **2. Rama Judicial de Colombia**
```
https://www.ramajudicial.gov.co/web/jurisprudencia
```
- Jurisprudencia de la Corte Suprema de Justicia
- Sentencias de tribunales
- Precedentes judiciales

### **3. Función Pública**
```
https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php
```
- Normatividad colombiana
- Leyes y decretos
- Códigos

### **4. Alcaldía de Bogotá - Sistema Jurídico**
```
https://www.alcaldiabogota.gov.co/sisjur/
```
- Normatividad distrital
- Acuerdos y decretos de Bogotá

---

## 🔧 **Cambios Realizados:**

### **1. URLs Actualizadas**
📍 `app/Services/MCPWebSearchService.php` - Línea 66-71

**Antes (Chile):**
```php
$urls = [
    'https://www.bcn.cl/leychile/...',
];
```

**Ahora (Colombia):**
```php
$urls = [
    'https://www.corteconstitucional.gov.co/relatoria/',
    'https://www.ramajudicial.gov.co/web/jurisprudencia',
    'https://www.funcionpublica.gov.co/eva/gestornormativo/norma.php',
    'https://www.alcaldiabogota.gov.co/sisjur/',
];
```

### **2. Filtro de País**
📍 `app/Services/MCPWebSearchService.php` - Línea 175

```php
// Agregar filtro por país: COLOMBIA
$query .= " Colombia";
```

### **3. Mock Server Actualizado**
📍 `app/Services/MCPLocalMockServer.php`

Casos simulados ahora incluyen:
- Sentencias Corte Constitucional (T-XXX/XXXX)
- Radicados colombianos (11001-31-03-001-2023-00456-01)
- Artículos del Código Civil Colombiano
- Tribunales de Bogotá

---

## 📊 **Ejemplo de Resultado:**

Cuando ejecutes un análisis, obtendrás resultados como:

```json
{
  "jurisprudence_result": {
    "precedents": [
      {
        "case_title": "Jurisprudencia Colombia - www.corteconstitucional.gov.co",
        "url": "https://www.corteconstitucional.gov.co/relatoria/",
        "court": "Fuente Web",
        "summary": "Contenido legal obtenido de la Corte Constitucional de Colombia...",
        "source": "MCP Fetch Server (Colombia)",
        "relevance": 0.85,
        "is_web_result": true
      },
      {
        "case_title": "Sentencia Corte Constitucional T-123/2024",
        "url": "https://www.corteconstitucional.gov.co/relatoria/2024/T-123-24.htm",
        "summary": "Incumplimiento de contrato de servicios conforme al Código Civil colombiano...",
        "source": "MCP Mock Server (Colombia Demo)",
        "relevance": 0.92
      }
    ],
    "mcp_client_used": true,
    "web_results": 4,
    "local_results": 3,
    "confidence": 0.87
  }
}
```

---

## 🧪 **Probar Ahora:**

### **1. Servidor corriendo:**
```
http://127.0.0.1:8000
```

### **2. Actualiza caso a "draft":**
```http
PUT /api/cases/28615f0b-62b4-46eb-9644-baba58bddbc1

Headers:
  Accept: application/json
  Content-Type: application/json

Body:
{
  "status": "draft"
}
```

### **3. Ejecuta análisis:**
```http
POST /api/cases/28615f0b-62b4-46eb-9644-baba58bddbc1/analyze

Headers:
  Accept: application/json
  Content-Type: application/json
```

### **4. Busca en la respuesta:**
```json
{
  "jurisprudence_result": {
    "precedents": [
      {
        "source": "MCP Fetch Server (Colombia)",  ← Colombia!
        "url": "https://www.corteconstitucional.gov.co/...",
      }
    ],
    "mcp_client_used": true
  }
}
```

---

## 🎬 **Para la Presentación del Hackathon:**

### **Qué resaltar:**

**1. MCP Server Real**
> "Nuestro sistema usa MCP Fetch Server oficial de Anthropic, que es 100% gratuito y sin límites."

**2. Jurisprudencia Colombiana**
> "Configuramos el sistema para consultar fuentes oficiales colombianas: Corte Constitucional, Rama Judicial, Función Pública y Alcaldía de Bogotá."

**3. Híbrido: Local + Web**
> "Combinamos precedentes de nuestra base de datos local con jurisprudencia obtenida en tiempo real de internet mediante MCP."

**4. Demostración**
> "Pueden ver en la respuesta que los precedentes incluyen URLs reales de sitios colombianos oficiales."

---

## 📚 **Fuentes Legales Colombianas:**

### **Código Civil Colombiano:**
- Artículo 1546: Condición resolutoria tácita
- Artículo 1613: Indemnización de perjuicios
- Artículo 2341: Responsabilidad civil extracontractual

### **Tipos de Sentencias:**
- **T-XXX/XXXX**: Sentencias de tutela (Corte Constitucional)
- **C-XXX/XXXX**: Sentencias de constitucionalidad
- **SU-XXX/XXXX**: Sentencias de unificación

### **Radicados:**
Formato: `11001-31-03-001-2023-00456-01`
- 11001: Código de Bogotá
- Resto: Identificador único del proceso

---

## 🚀 **Ventajas del Sistema:**

### ✅ **100% Gratis**
- MCP Fetch Server sin costo
- Sin tarjetas de crédito
- Sin límites de uso

### ✅ **Jurisprudencia Real**
- Consulta sitios oficiales colombianos
- Contenido actualizado
- URLs verificables

### ✅ **MCP Protocol Completo**
- Cliente MCP estándar
- Servidor MCP oficial de Anthropic
- Protocolo JSON-RPC 2.0

### ✅ **Contextualizado para Colombia**
- Cortes y tribunales colombianos
- Código Civil colombiano
- Normatividad local

---

## 🎯 **Resumen:**

Tu sistema **LEGAL-IA** ahora:

1. ✅ Usa **Google Gemini** (IA gratis)
2. ✅ Consulta **jurisprudencia colombiana** vía MCP
3. ✅ Conecta a **sitios oficiales**:
   - Corte Constitucional
   - Rama Judicial
   - Función Pública
   - Alcaldía de Bogotá
4. ✅ **MCP Fetch Server** (100% gratis)
5. ✅ Arquitectura **A2A + MCP** completa

**¡Todo listo para tu hackathon con leyes de COLOMBIA!** 🇨🇴🎉
