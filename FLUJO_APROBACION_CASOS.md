# Flujo de Aprobación y Publicación de Casos

Este documento describe el flujo completo de un caso desde que es enviado por una víctima hasta que es visible para abogados e inversores.

## 📋 Estados del Caso

```
SUBMITTED → UNDER_ADMIN_REVIEW → APPROVED_FOR_BIDDING → RECEIVING_BIDS → BIDS_CLOSED → LAWYER_ASSIGNED → PUBLISHED → FUNDED → IN_PROGRESS → COMPLETED
    ↓
REJECTED (terminal)
```

## 🔄 Flujo Detallado

### 1️⃣ SUBMITTED (Víctima envía el caso)
**Actor**: Víctima
**Endpoint**: `POST /api/v1/cases`

La víctima registra su caso con:
- Título y descripción
- Empresa involucrada
- Categoría (despido_injustificado, acoso_laboral, etc.)
- Documentos adjuntos

**Estado inicial**: `submitted`

---

### 2️⃣ UNDER_ADMIN_REVIEW (Revisión por Admin)
**Actor**: Admin
**Acción**: El caso automáticamente o manualmente pasa a revisión

El admin revisa:
- Validez del caso
- Documentación completa
- Viabilidad legal preliminar

---

### 3️⃣ APPROVED_FOR_BIDDING (Aprobado para Licitación)
**Actor**: Admin
**Endpoint**: `POST /api/v1/admin/cases/{case}/approve-for-bidding`

**Request Body**:
```json
{
  "bid_deadline": "2025-12-31T23:59:59Z",
  "is_public_marketplace": true,
  "admin_notes": "Caso viable, documentación completa"
}
```

**Cambios en el caso**:
- `status` → `approved_for_bidding`
- `bid_deadline` → Fecha límite para recibir licitaciones
- `is_public_marketplace` → `true` (visible en marketplace público) o `false` (solo abogados autenticados)
- `admin_review_notes` → Notas del admin
- `reviewed_by` → ID del admin
- `reviewed_at` → Timestamp

**Resultado**:
- ✅ Si `is_public_marketplace = true`: **El caso es visible en `/marketplace` (página pública)**
- ✅ Abogados autenticados pueden ver el caso en `/lawyer/marketplace`
- ✅ Abogados pueden enviar licitaciones

**Alternativa - Rechazar Caso**:
**Endpoint**: `POST /api/v1/admin/cases/{case}/reject`
```json
{
  "rejection_reason": "Motivo detallado del rechazo"
}
```
- `status` → `rejected` (estado terminal)

---

### 4️⃣ RECEIVING_BIDS (Recibiendo Licitaciones)
**Actores**: Abogados
**Endpoints**:
- `GET /api/v1/lawyer/available-cases` - Ver casos disponibles
- `POST /api/v1/lawyer/cases/{case}/bid` - Enviar licitación

Los abogados envían propuestas que incluyen:
- `success_probability` - Probabilidad de éxito (%)
- `funding_goal_proposed` - Monto solicitado
- `expected_return_percentage` - Retorno esperado para inversores (%)
- `technical_proposal` - Propuesta técnica
- `economic_proposal` - Propuesta económica
- Honorarios del abogado

El admin puede:
- **Ver todas las licitaciones**: `GET /api/v1/admin/cases/{case}/bids`
- **Evaluar licitaciones**: `POST /api/v1/admin/bids/{bid}/review`
  ```json
  {
    "admin_score": 8,
    "admin_feedback": "Buena propuesta, experiencia comprobada"
  }
  ```

---

### 5️⃣ BIDS_CLOSED (Licitación Cerrada)
**Actor**: Admin
**Endpoint**: `POST /api/v1/admin/cases/{case}/close-bidding`

El admin cierra la recepción de nuevas licitaciones cuando:
- Se alcanzó el `bid_deadline`
- Ya hay suficientes propuestas de calidad

**Resultado**: No se aceptan más licitaciones para este caso.

---

### 6️⃣ LAWYER_ASSIGNED (Abogado Asignado)
**Actor**: Admin
**Endpoint**: `POST /api/v1/admin/cases/{case}/assign-lawyer/{bid}`

El admin selecciona la mejor propuesta y asigna el abogado ganador.

**Cambios en el caso**:
- `status` → `lawyer_assigned`
- `lawyer_id` → ID del abogado ganador
- `funding_goal` → Monto de la licitación ganadora
- `expected_return` → Retorno esperado de la licitación ganadora
- `success_rate` → Probabilidad de éxito de la licitación ganadora
- Honorarios del abogado (evaluation_fee, success_fee, fixed_fee)

**Cambios en licitaciones**:
- Licitación ganadora: `status` → `accepted`
- Otras licitaciones: `status` → `rejected`

**Notificaciones**:
- ✅ Abogado ganador recibe notificación
- ✅ Abogados no seleccionados reciben notificación de rechazo
- ✅ Víctima recibe notificación de abogado asignado

---

### 7️⃣ PUBLISHED (Publicado para Inversores)
**Actor**: Admin
**Endpoint**: `POST /api/v1/admin/cases/{case}/publish-for-investors`

Una vez asignado el abogado, el admin publica el caso para que inversores puedan financiarlo.

**Requisitos**:
- El caso debe estar en estado `lawyer_assigned`
- Debe tener un `lawyer_id` asignado

**Cambios en el caso**:
- `status` → `published`
- `published_by` → ID del admin
- `published_at` → Timestamp

**Resultado**:
- ✅ **El caso es visible en `/investor/opportunities`**
- ✅ Inversores pueden revisar detalles del caso
- ✅ Inversores pueden crear inversiones para financiar el caso

---

### 8️⃣ FUNDED (Financiado)
**Actores**: Inversores
**Endpoint**: `POST /api/v1/investments`

Los inversores financian el caso hasta alcanzar el `funding_goal`.

**Cambio automático de estado**:
Cuando `current_funding >= funding_goal`:
- `status` → `funded`

---

### 9️⃣ IN_PROGRESS (En Progreso Legal)
**Actor**: Admin o Abogado
**Endpoint**: `POST /api/v1/cases/{case}/start`

El abogado comienza el proceso legal.

---

### 🔟 COMPLETED (Completado)
**Actor**: Admin o Abogado
**Endpoint**: `POST /api/v1/cases/{case}/close`

El caso se cierra con un resultado:
- `outcome` → `won` (ganado) o `lost` (perdido)

Si el caso fue ganado:
- **Distribuir retornos**: `POST /api/v1/cases/{case}/distribute-returns`
- Los inversores reciben sus retornos

---

## 🔐 Visibilidad del Marketplace Público

### Toggle Visibilidad Pública
**Actor**: Admin
**Endpoint**: `POST /api/v1/admin/cases/{case}/toggle-public-marketplace`

El admin puede cambiar la visibilidad pública del caso en cualquier momento durante la fase de licitación.

**Restricciones**:
- Solo casos en estados `approved_for_bidding` o `receiving_bids` pueden cambiar visibilidad

**Efecto**:
- Si `is_public_marketplace = true`: El caso aparece en `/marketplace` (sin autenticación)
- Si `is_public_marketplace = false`: El caso solo es visible para abogados autenticados en `/lawyer/marketplace`

---

## 📊 Resumen de Endpoints de Admin

| Acción | Método | Endpoint | Estado Requerido |
|--------|--------|----------|------------------|
| Ver casos pendientes | GET | `/api/v1/admin/cases/pending-review` | - |
| Aprobar para licitación | POST | `/api/v1/admin/cases/{case}/approve-for-bidding` | `submitted`, `under_admin_review` |
| Rechazar caso | POST | `/api/v1/admin/cases/{case}/reject` | `submitted`, `under_admin_review` |
| Ver licitaciones de un caso | GET | `/api/v1/admin/cases/{case}/bids` | - |
| Cerrar licitación | POST | `/api/v1/admin/cases/{case}/close-bidding` | `receiving_bids` |
| Evaluar licitación | POST | `/api/v1/admin/bids/{bid}/review` | - |
| Asignar abogado | POST | `/api/v1/admin/cases/{case}/assign-lawyer/{bid}` | `receiving_bids`, `bids_closed`, `approved_for_bidding` |
| Publicar para inversores | POST | `/api/v1/admin/cases/{case}/publish-for-investors` | `lawyer_assigned` |
| Toggle marketplace público | POST | `/api/v1/admin/cases/{case}/toggle-public-marketplace` | `approved_for_bidding`, `receiving_bids` |

---

## 🎯 Flujo Resumido para el Admin

```
1. Víctima envía caso (SUBMITTED)
   ↓
2. Admin revisa caso
   ↓
3. Admin aprueba caso para licitación (APPROVED_FOR_BIDDING)
   - Define bid_deadline
   - Marca is_public_marketplace = true (visible en /marketplace)
   ↓
4. Abogados envían licitaciones (múltiples)
   ↓
5. Admin cierra licitación (BIDS_CLOSED)
   ↓
6. Admin evalúa licitaciones
   - Asigna puntuación (admin_score)
   - Agrega feedback
   ↓
7. Admin asigna abogado ganador (LAWYER_ASSIGNED)
   - Se copian datos de la licitación ganadora al caso
   - Otras licitaciones se rechazan automáticamente
   ↓
8. Admin publica para inversores (PUBLISHED)
   - Caso visible en /investor/opportunities
   ↓
9. Inversores financian el caso
   ↓
10. Estado cambia automáticamente a FUNDED
    ↓
11. Abogado inicia proceso legal (IN_PROGRESS)
    ↓
12. Caso se completa (COMPLETED)
    - Admin distribuye retornos si ganó
```

---

## ✅ Checklist para el Admin

### Al aprobar un caso:
- [ ] Revisar documentación completa
- [ ] Validar viabilidad legal
- [ ] Definir fecha límite de licitación (bid_deadline)
- [ ] Decidir si será público (is_public_marketplace)
- [ ] Agregar notas de revisión

### Al asignar abogado:
- [ ] Evaluar todas las licitaciones
- [ ] Comparar propuestas técnicas y económicas
- [ ] Verificar experiencia del abogado
- [ ] Seleccionar la mejor propuesta
- [ ] Confirmar asignación

### Al publicar para inversores:
- [ ] Verificar que el abogado esté asignado
- [ ] Revisar que funding_goal sea razonable
- [ ] Validar expected_return sea atractivo
- [ ] Confirmar publicación

---

## 🔔 Notificaciones (Pendientes de Implementar)

- `CaseApprovedForBidding` → Víctima y abogados
- `CaseRejected` → Víctima
- `LawyerAssigned` → Abogado ganador, abogados rechazados, víctima
- `CasePublishedForInvestors` → Inversores, víctima, abogado
- `CaseFunded` → Todos los actores
- `CaseCompleted` → Todos los actores

---

## 📝 Notas Importantes

1. **Marketplace Público vs. Privado**:
   - Público (`is_public_marketplace = true`): Cualquiera puede ver el caso en `/marketplace`
   - Privado (`is_public_marketplace = false`): Solo abogados autenticados ven el caso

2. **Estados No Reversibles**:
   - `rejected` es terminal (no se puede cambiar)
   - Una vez asignado un abogado, no se puede revertir

3. **Validaciones Importantes**:
   - Solo casos en revisión pueden aprobarse/rechazarse
   - Solo casos con abogado asignado pueden publicarse para inversores
   - Solo casos en licitación pueden cambiar visibilidad pública

4. **Seguridad**:
   - Todos los endpoints de admin requieren autenticación
   - Middleware `admin` valida el rol del usuario
   - Se registra quién realiza cada acción (`reviewed_by`, `published_by`)
