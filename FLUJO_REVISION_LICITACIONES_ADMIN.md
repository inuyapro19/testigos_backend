# 📋 Flujo de Revisión de Licitaciones por el Admin

## 🎯 Resumen

El **administrador** es el responsable de revisar todas las licitaciones recibidas, evaluar a los abogados y **decidir quién es asignado al caso**. Este documento explica el proceso completo.

---

## 🔄 Flujo Completo de Licitaciones

```
┌────────────────────────────────────────────────────────────┐
│ 1. ADMIN APRUEBA CASO                                      │
│    Acción: approve-for-bidding                             │
│    Estado: SUBMITTED → APPROVED_FOR_BIDDING                │
│    Resultado: Caso visible para abogados                   │
└──────────────────┬─────────────────────────────────────────┘
                   │
                   ▼
┌────────────────────────────────────────────────────────────┐
│ 2. ABOGADOS ENVÍAN LICITACIONES                           │
│    Los abogados ven el caso y envían propuestas:          │
│    - Propuesta técnica (estrategia legal)                 │
│    - Propuesta económica (funding, retorno, honorarios)   │
│    - Probabilidad de éxito                                 │
│    - Duración estimada                                     │
│    - Casos similares ganados                               │
│    Estado: APPROVED_FOR_BIDDING → RECEIVING_BIDS          │
└──────────────────┬─────────────────────────────────────────┘
                   │
                   ▼
┌────────────────────────────────────────────────────────────┐
│ 3. ADMIN REVISA LICITACIONES (TÚ DECIDES)                 │
│    Página: /admin/cases/:caseId/bids                      │
│    El admin:                                               │
│    ✅ Ve TODAS las licitaciones en una tabla comparativa  │
│    ✅ Hace click en "Ver Detalles" de cada una            │
│    ✅ Revisa propuesta técnica y económica                │
│    ✅ Evalúa experiencia del abogado                      │
│    ✅ Asigna puntaje (1-10) y feedback                    │
│    ✅ Compara todas las propuestas                        │
└──────────────────┬─────────────────────────────────────────┘
                   │
                   ▼
┌────────────────────────────────────────────────────────────┐
│ 4. ADMIN DECIDE Y ASIGNA ABOGADO GANADOR                  │
│    El admin hace click en "Asignar" en la mejor propuesta │
│    Resultado automático:                                   │
│    ✅ Abogado es asignado al caso                         │
│    ✅ Datos de la licitación → copiados al caso           │
│    ✅ Otras licitaciones → rechazadas automáticamente     │
│    ✅ Abogados no seleccionados → reciben notificación    │
│    Estado: RECEIVING_BIDS → LAWYER_ASSIGNED                │
└──────────────────┬─────────────────────────────────────────┘
                   │
                   ▼
┌────────────────────────────────────────────────────────────┐
│ 5. ADMIN PUBLICA PARA INVERSORES                          │
│    Botón: "Publicar para Inversores"                      │
│    Estado: LAWYER_ASSIGNED → PUBLISHED                     │
│    Resultado: Caso visible en /investor/opportunities     │
└──────────────────┬─────────────────────────────────────────┘
                   │
                   ▼
┌────────────────────────────────────────────────────────────┐
│ 6. INVERSORES FINANCIAN EL CASO                           │
│    Estado: PUBLISHED → FUNDED → IN_PROGRESS → COMPLETED   │
└────────────────────────────────────────────────────────────┘
```

---

## 📊 Página de Revisión de Licitaciones

### **Ruta**: `/admin/cases/:caseId/bids`

### **Vista Principal**

La página muestra una **tabla comparativa** con todas las licitaciones:

| Abogado | Funding | Retorno | Prob. Éxito | Duración | Score Admin | Estado | Acciones |
|---------|---------|---------|-------------|----------|-------------|--------|----------|
| Juan Pérez ⭐ | $5.000.000 | 35% | 85% | 12 meses | 9/10 | submitted | Ver Detalles \| **Asignar** |
| María López | $6.500.000 | 30% | 80% | 14 meses | 7/10 | under_review | Ver Detalles \| **Asignar** |
| Carlos Silva | $4.800.000 | 40% | 75% | 16 meses | 6/10 | submitted | Ver Detalles \| **Asignar** |

**Características**:
- ⭐ La licitación con **mejor puntaje** aparece con fondo verde
- 🔵 La licitación **asignada** aparece con fondo azul y borde izquierdo
- 🔴 Licitaciones rechazadas aparecen atenuadas

---

## 🔍 Proceso de Revisión (Paso a Paso)

### **Paso 1: Acceder a las Licitaciones**

1. Ve al caso desde `/admin/cases`
2. Haz click en el caso con licitaciones pendientes
3. O navega directamente a `/admin/cases/:caseId/bids`

### **Paso 2: Revisar Cada Licitación**

Para cada licitación, haz click en **"Ver Detalles"**:

#### **Información que verás**:

**A. Información del Abogado**
- Nombre completo
- Email
- Estudio jurídico
- Años de experiencia
- Tasa de éxito histórica
- Número de casos manejados

**B. Propuesta Económica**
- **Monto de financiamiento propuesto** (funding_goal_proposed)
- **Retorno para inversores** (expected_return_percentage)
- **Fee de evaluación** (cobro inicial por evaluar el caso)
- **Fee de éxito** (% del monto recuperado)
- **Fee fijo** (honorarios fijos)

**C. Propuesta Técnica**
- **Probabilidad de éxito** (%)
- **Duración estimada** (meses)
- **Estrategia legal** (texto detallado)
- **Resumen de experiencia** (casos similares)
- **Por qué es el mejor candidato**
- **Casos similares ganados** (cantidad y descripción)

**D. Evaluación del Admin**
- Formulario para asignar **puntaje 1-10**
- Campo de **feedback detallado** para el abogado
- Botón **"Guardar Evaluación"**
- Botón **"Aceptar y Asignar"** (asigna al abogado)
- Botón **"Rechazar Licitación"**

### **Paso 3: Evaluar y Comparar**

1. **Evalúa cada licitación**:
   - Asigna un puntaje del 1 al 10
   - Escribe feedback constructivo
   - Guarda la evaluación

2. **Compara todas las licitaciones**:
   - Vuelve a la tabla comparativa
   - Analiza funding vs retorno vs éxito
   - Considera experiencia del abogado
   - Considera plazo de ejecución

3. **Identifica la mejor propuesta**:
   - La licitación con mejor score aparece destacada (⭐)
   - Pero **TÚ decides** cuál es la mejor (no es automático)
   - Puedes elegir cualquier licitación

### **Paso 4: Asignar Abogado Ganador**

Hay **2 formas** de asignar al abogado:

#### **Opción A: Desde la Tabla Comparativa**
1. Haz click en **"Asignar"** junto a la licitación elegida
2. Confirma la asignación en el diálogo
3. ✅ Abogado asignado automáticamente

#### **Opción B: Desde el Detalle de la Licitación**
1. Abre los detalles de la licitación elegida
2. Haz click en **"Aceptar y Asignar"**
3. Confirma la asignación
4. ✅ Abogado asignado automáticamente

**Al asignar, sucede automáticamente**:
- ✅ El abogado es asignado al caso (`lawyer_id`)
- ✅ Los datos de la licitación se copian al caso:
  - `funding_goal` → monto a recaudar
  - `expected_return` → retorno para inversores
  - `success_rate` → probabilidad de éxito
  - `lawyer_evaluation_fee` → fee de evaluación
  - `lawyer_success_fee_percentage` → fee de éxito
  - `lawyer_fixed_fee` → fee fijo
- ✅ La licitación ganadora → `status = accepted`
- ✅ Otras licitaciones → `status = rejected` (automático)
- ✅ Estado del caso → `LAWYER_ASSIGNED`
- ✅ Notificaciones enviadas:
  - Abogado ganador: "¡Felicidades! Fuiste seleccionado"
  - Abogados rechazados: "Tu propuesta no fue seleccionada"
  - Víctima: "Hemos asignado un abogado a tu caso"

---

## 🎯 Criterios de Decisión

### **Factores a Considerar**:

1. **Experiencia del Abogado**
   - Años de experiencia en el área
   - Tasa de éxito histórica
   - Número de casos similares ganados
   - Calidad del estudio jurídico

2. **Propuesta Económica**
   - **Funding Goal**: ¿Es razonable el monto solicitado?
   - **Retorno**: ¿Es atractivo para inversores? (20-40% típico)
   - **Honorarios**: ¿Son competitivos?

3. **Propuesta Técnica**
   - **Probabilidad de éxito**: ¿Es realista? (60-90% típico)
   - **Estrategia legal**: ¿Es sólida y viable?
   - **Duración**: ¿Es razonable? (6-18 meses típico)
   - **Casos similares**: ¿Ha ganado casos parecidos?

4. **Balance Riesgo/Retorno**
   - Funding bajo + Retorno alto = Atractivo para inversores
   - Éxito alto + Duración corta = Mejor para víctima
   - Experiencia alta = Menor riesgo

### **Ejemplo de Comparación**:

| Criterio | Juan Pérez ⭐ | María López | Carlos Silva |
|----------|--------------|-------------|--------------|
| Experiencia | 15 años, 89% éxito | 10 años, 85% éxito | 8 años, 78% éxito |
| Funding | $5M (óptimo) | $6.5M (alto) | $4.8M (bajo) |
| Retorno | 35% (bueno) | 30% (bajo) | 40% (muy alto) |
| Prob. Éxito | 85% (muy buena) | 80% (buena) | 75% (aceptable) |
| Duración | 12 meses (rápido) | 14 meses (medio) | 16 meses (lento) |
| Casos Similares | 23 ganados | 15 ganados | 8 ganados |
| **Decisión** | ✅ **MEJOR OPCIÓN** | Segunda opción | Tercera opción |

**Análisis**:
- **Juan Pérez** tiene la mejor combinación de experiencia, éxito, rapidez y funding razonable
- **María López** es buena opción pero funding alto puede dificultar conseguir inversores
- **Carlos Silva** tiene buen retorno pero menor experiencia y mayor duración

---

## 🔒 Acciones Disponibles Según Estado del Caso

| Estado del Caso | Puede Cerrar Licitación | Puede Asignar Abogado | Puede Publicar para Inversores |
|-----------------|------------------------|----------------------|-------------------------------|
| `approved_for_bidding` | ✅ Sí | ✅ Sí | ❌ No |
| `receiving_bids` | ✅ Sí | ✅ Sí | ❌ No |
| `bids_closed` | ❌ No (ya cerrado) | ✅ Sí | ❌ No |
| `lawyer_assigned` | ❌ No | ❌ No (ya asignado) | ✅ Sí |
| `published` | ❌ No | ❌ No | ❌ No (ya publicado) |

---

## 🔧 Acciones Opcionales

### **Cerrar Licitación**
- **Cuándo**: Cuando ya no quieres recibir más licitaciones
- **Efecto**: No se aceptan más licitaciones nuevas
- **Estado**: `RECEIVING_BIDS` → `BIDS_CLOSED`
- **Reversible**: Sí, puedes reabrir con "Reabrir Licitación"

### **Toggle Marketplace Público**
- **Cuándo**: Durante el proceso de licitación
- **Efecto**: Hace el caso visible/invisible en `/marketplace` (sin login)
- **Estados válidos**: `approved_for_bidding`, `receiving_bids`

---

## 📱 Interfaz de Usuario

### **Tabla Comparativa**

```
┌─────────────────────────────────────────────────────────────────────┐
│ Comparación de Licitaciones                                         │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│ ⭐ Abogado con mejor score aparece con fondo verde                  │
│ 🔵 Abogado asignado aparece con fondo azul y badge "✓ Asignado"   │
│                                                                      │
│ Cada fila muestra:                                                  │
│ - Nombre y experiencia del abogado                                 │
│ - Propuesta económica resumida                                     │
│ - Score del admin                                                   │
│ - Botones: "Ver Detalles" | "Asignar"                             │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

### **Detalle de Licitación**

```
┌─────────────────────────────────────────────────────────────────────┐
│ Evaluar Licitación                                                  │
├─────────────────────────────────────────────────────────────────────┤
│                                                                      │
│ [Información del Abogado]   [Información del Caso]                 │
│                                                                      │
│ [Propuesta Económica - 5 métricas principales]                     │
│                                                                      │
│ [Propuesta Técnica - Estrategia, experiencia, casos similares]     │
│                                                                      │
│ [Evaluación del Admin]                                              │
│ Score (1-10): [____]                                                │
│ Feedback: [____________________________]                            │
│                                                                      │
│ [Guardar Evaluación] [Rechazar] [✅ Aceptar y Asignar]            │
│                                                                      │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 🎓 Instrucciones en la Interfaz

La página `/admin/cases/:caseId/bids` incluye un panel de instrucciones detallado:

**Paso 1: Revisar Licitaciones**
- Haz click en "Ver Detalles" en cada licitación
- Revisa la propuesta técnica y económica completa
- Revisa la experiencia y casos similares del abogado
- Asigna un puntaje (1-10) y feedback detallado

**Paso 2: Comparar y Decidir**
- La tabla muestra todas las licitaciones para comparar
- La licitación con mejor puntaje aparece destacada (⭐)
- Compara: funding, retorno, probabilidad de éxito, duración
- Considera experiencia y tasa de éxito del abogado

**Paso 3: Asignar Abogado Ganador**
- Haz click en "Asignar" en la mejor licitación
- Esto asignará automáticamente al abogado al caso
- Las demás licitaciones serán rechazadas automáticamente
- El caso pasará a estado LAWYER_ASSIGNED

**Paso 4: Publicar para Inversores**
- Después de asignar abogado, haz click en "Publicar para Inversores"
- El caso será visible en /investor/opportunities
- Los inversores podrán financiar el caso

---

## 📊 Endpoint del Backend

**Asignar Abogado**:
```
POST /api/v1/admin/cases/{caseId}/assign-lawyer/{bidId}
```

**Parámetros**:
- `caseId`: ID del caso
- `bidId`: ID de la licitación ganadora

**Resultado**:
- Actualiza el caso con los datos de la licitación
- Marca la licitación como `accepted`
- Rechaza otras licitaciones automáticamente
- Cambia estado del caso a `LAWYER_ASSIGNED`

---

## ✅ Resumen Ejecutivo

### **¿Quién decide qué abogado se asigna?**
**El ADMIN** (tú) decides. No es automático.

### **¿Cómo decide el admin?**
1. Ve todas las licitaciones en una tabla comparativa
2. Revisa cada licitación en detalle
3. Evalúa con puntaje y feedback
4. Compara propuestas económicas y técnicas
5. Considera experiencia del abogado
6. **Hace click en "Asignar"** en la licitación elegida

### **¿Qué pasa al asignar?**
- Abogado asignado al caso automáticamente
- Datos copiados de la licitación al caso
- Otras licitaciones rechazadas automáticamente
- Notificaciones enviadas a todos
- Caso listo para publicar a inversores

### **¿Es reversible?**
❌ No, la asignación de abogado no se puede deshacer.

---

**Documento actualizado**: 2025-01-05
