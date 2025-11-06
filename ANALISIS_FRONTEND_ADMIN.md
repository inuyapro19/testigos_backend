# Análisis del Frontend de Admin - Flujo de Casos

## 📋 Estado Actual

### ✅ Páginas Existentes

| Página | Ubicación | Funcionalidad |
|--------|-----------|---------------|
| **Cases.tsx** | `/admin/cases` | Lista todos los casos con filtros básicos |
| **AdminCaseBidsReview.tsx** | `/admin/cases/:caseId/bids` | Revisa licitaciones de un caso |
| **AdminBidDetail.tsx** | `/admin/bids/:bidId` | Detalle de una licitación específica |
| **Users.tsx** | `/admin/users` | Gestión de usuarios |
| **Investments.tsx** | `/admin/investments` | Gestión de inversiones |
| **Withdrawals.tsx** | `/admin/withdrawals` | Gestión de retiros |

### ✅ Endpoints Implementados en admin.api.ts

```typescript
// Gestión de licitaciones
getCaseBids(caseId) - Obtener licitaciones de un caso
reviewBid(bidId, reviewData) - Evaluar una licitación
acceptBid(bidId) - Aceptar licitación
rejectBid(bidId, feedback) - Rechazar licitación

// Gestión de casos
approveCaseForBidding(caseId, data) - Aprobar caso para licitación
closeBidding(caseId) - Cerrar licitación
reopenBidding(caseId) - Reabrir licitación
assignLawyerToCase(caseId, data) - Asignar abogado
```

---

## ❌ Flujos Faltantes

### 1. **Página de Casos Pendientes de Aprobación** 🔴 CRÍTICO

**Problema**: No existe una página dedicada para que el admin vea casos `SUBMITTED` o `UNDER_ADMIN_REVIEW` y los apruebe/rechace.

**Endpoint Backend**: `GET /admin/cases/pending-review`

**Acciones necesarias**:
- `POST /admin/cases/{case}/approve-for-bidding`
  ```json
  {
    "bid_deadline": "2025-12-31T23:59:59Z",
    "is_public_marketplace": true,
    "admin_notes": "Caso aprobado"
  }
  ```
- `POST /admin/cases/{case}/reject`
  ```json
  {
    "rejection_reason": "Motivo del rechazo"
  }
  ```

**Página requerida**: `src/pages/admin/PendingCasesReview.tsx`

**Ruta**: `/admin/cases/pending-review`

---

### 2. **Toggle Marketplace Público** 🔴 CRÍTICO

**Problema**: No hay UI para cambiar la visibilidad pública de un caso.

**Endpoint Backend**: `POST /admin/cases/{case}/toggle-public-marketplace`

**Dónde debe estar**:
- En la página de detalle del caso
- En la página de aprobación de casos
- Como botón toggle en la lista de casos

**Campo del modelo**: `is_public_marketplace` (boolean)

**Estados válidos**: Solo para casos en `approved_for_bidding` o `receiving_bids`

---

### 3. **Publicar Caso para Inversores** 🔴 CRÍTICO

**Problema**: No existe una página o acción para publicar casos para inversores después de asignar abogado.

**Endpoint Backend**: `POST /admin/cases/{case}/publish-for-investors`

**Estado requerido**: `lawyer_assigned`

**Acción**: Cambia el estado a `published`, haciendo visible el caso en `/investor/opportunities`

**Dónde debe estar**:
- En la página de detalle del caso
- Como botón de acción después de asignar abogado

---

### 4. **Página de Detalle Completo del Caso** 🟡 IMPORTANTE

**Problema**: La página actual `Cases.tsx` solo lista casos. No hay una página de detalle donde el admin pueda:
- Ver toda la información del caso
- Aprobar/rechazar casos pendientes
- Ver documentos adjuntos
- Cambiar visibilidad pública
- Asignar abogado (después de revisar licitaciones)
- Publicar para inversores
- Ver historial de cambios de estado

**Ruta actual**: `/admin/cases/:id` (existe en router pero usa CaseDetail genérico)

**Página requerida**: `src/pages/admin/CaseDetailAdmin.tsx` con acciones específicas de admin

---

### 5. **Flujo de Asignación de Abogado** 🟡 IMPORTANTE

**Problema**: El endpoint `assignLawyerToCase` no coincide con el backend real.

**Endpoint Real del Backend**: `POST /admin/cases/{case}/assign-lawyer/{bid}`

**Endpoint en Frontend**: `assignLawyerToCase(caseId, data)` con datos manuales

**Corrección necesaria**: El frontend debe usar el endpoint correcto que recibe el `bidId` de la licitación ganadora, no datos manuales.

```typescript
// ❌ Incorrecto (actual)
assignLawyerToCase: async (caseId: number, data: {
  lawyer_id: number;
  funding_goal: number;
  // ...
})

// ✅ Correcto (debería ser)
assignLawyerToCase: async (caseId: number, bidId: number): Promise<void> => {
  await apiClient.post(`/admin/cases/${caseId}/assign-lawyer/${bidId}`);
}
```

---

### 6. **Actualizar Endpoints en admin.api.ts** 🟡 IMPORTANTE

**Endpoints faltantes**:

```typescript
// Aprobar/Rechazar casos pendientes
getPendingCases: async (): Promise<AdminCase[]> => {
  const response = await apiClient.get('/admin/cases/pending-review');
  return response.data.data;
},

approveForBidding: async (caseId: number, data: {
  bid_deadline: string;
  is_public_marketplace: boolean;
  admin_notes?: string;
}): Promise<void> => {
  await apiClient.post(`/admin/cases/${caseId}/approve-for-bidding`, data);
},

rejectCase: async (caseId: number, data: {
  rejection_reason: string;
}): Promise<void> => {
  await apiClient.post(`/admin/cases/${caseId}/reject`, data);
},

// Toggle marketplace público
togglePublicMarketplace: async (caseId: number): Promise<{ is_public: boolean }> => {
  const response = await apiClient.post(`/admin/cases/${caseId}/toggle-public-marketplace`);
  return response.data;
},

// Publicar para inversores
publishForInvestors: async (caseId: number): Promise<void> => {
  await apiClient.post(`/admin/cases/${caseId}/publish-for-investors`);
},

// Asignar abogado (corregido)
assignLawyerToCase: async (caseId: number, bidId: number): Promise<void> => {
  await apiClient.post(`/admin/cases/${caseId}/assign-lawyer/${bidId}`);
},
```

---

## 🔄 Flujo Completo que Debe Implementarse

### **Fase 1: Aprobación Inicial**
1. Admin ve casos pendientes en `/admin/cases/pending-review`
2. Admin hace click en un caso para ver detalles
3. Admin aprueba el caso con formulario:
   - Fecha límite de licitación
   - Toggle "Visible en marketplace público" (is_public_marketplace)
   - Notas de revisión
4. O admin rechaza el caso con motivo

### **Fase 2: Gestión de Licitaciones**
5. Admin ve licitaciones en `/admin/cases/:caseId/bids` ✅ (Ya existe)
6. Admin evalúa cada licitación ✅ (Ya existe)
7. Admin cierra licitación ✅ (Ya existe)
8. Admin selecciona licitación ganadora y asigna abogado
   - ❌ Falta usar el endpoint correcto

### **Fase 3: Publicación para Inversores**
9. Admin publica el caso para inversores
   - ❌ Falta completamente
   - Debe cambiar estado a `published`
   - Hace visible en `/investor/opportunities`

---

## 📝 Tareas Pendientes

### Alta Prioridad 🔴

1. **Crear PendingCasesReview.tsx**
   - Listar casos `submitted` y `under_admin_review`
   - Formulario de aprobación con:
     - DatePicker para bid_deadline
     - Toggle para is_public_marketplace
     - TextArea para admin_notes
   - Formulario de rechazo con motivo

2. **Agregar endpoints faltantes a admin.api.ts**
   - `getPendingCases()`
   - `approveForBidding(caseId, data)`
   - `rejectCase(caseId, data)`
   - `togglePublicMarketplace(caseId)`
   - `publishForInvestors(caseId)`

3. **Corregir assignLawyerToCase**
   - Cambiar firma para recibir `bidId`
   - Actualizar llamadas en componentes

4. **Agregar botón "Publicar para Inversores"**
   - En página de detalle del caso
   - Solo visible cuando status = `lawyer_assigned`

### Media Prioridad 🟡

5. **Crear CaseDetailAdmin.tsx**
   - Vista completa del caso para admin
   - Acciones contextuales según estado
   - Historial de cambios

6. **Agregar toggle de marketplace público**
   - En página de aprobación
   - En detalle del caso
   - Solo disponible en estados válidos

### Baja Prioridad 🟢

7. **Mejorar Cases.tsx**
   - Agregar más filtros
   - Mostrar indicador de is_public_marketplace
   - Acciones rápidas

---

## 🎯 Resumen Ejecutivo

**Problemas críticos**:
1. ❌ No se pueden aprobar casos pendientes (no hay página)
2. ❌ No se puede cambiar visibilidad del marketplace público
3. ❌ No se puede publicar casos para inversores
4. ⚠️ El flujo de asignación de abogado usa endpoint incorrecto

**Impacto**:
- **El flujo completo del admin está incompleto**
- Los casos no pueden pasar de `SUBMITTED` a `APPROVED_FOR_BIDDING`
- Los casos no pueden marcarse como públicos en `/marketplace`
- Los casos no pueden publicarse para inversores

**Solución**:
Implementar las 4 páginas/endpoints faltantes en orden de prioridad.

---

## 🗺️ Mapa de Navegación Propuesto

```
/admin
  ├── /dashboard (✅ existe)
  ├── /users (✅ existe)
  ├── /cases (✅ existe - lista básica)
  │   ├── /pending-review (❌ falta - CRÍTICO)
  │   │   └── /:id/approve (formulario de aprobación)
  │   ├── /:id (🟡 mejorar - detalle completo con acciones)
  │   └── /:id/bids (✅ existe - revisar licitaciones)
  │       └── /:bidId (✅ existe - detalle de licitación)
  ├── /investments (✅ existe)
  └── /withdrawals (✅ existe)
```

---

## 📊 Endpoints Backend vs Frontend

| Endpoint Backend | Endpoint Frontend | Estado |
|------------------|-------------------|--------|
| `GET /admin/cases/pending-review` | ❌ No existe | Falta |
| `POST /admin/cases/{id}/approve-for-bidding` | ❌ No existe | Falta |
| `POST /admin/cases/{id}/reject` | ❌ No existe | Falta |
| `GET /admin/cases/{id}/bids` | ✅ `getCaseBids` | OK |
| `POST /admin/cases/{id}/close-bidding` | ✅ `closeBidding` | OK |
| `POST /admin/bids/{id}/review` | ✅ `reviewBid` | OK |
| `POST /admin/cases/{id}/assign-lawyer/{bidId}` | ⚠️ `assignLawyerToCase` | Incorrecto |
| `POST /admin/cases/{id}/publish-for-investors` | ❌ No existe | Falta |
| `POST /admin/cases/{id}/toggle-public-marketplace` | ❌ No existe | Falta |
