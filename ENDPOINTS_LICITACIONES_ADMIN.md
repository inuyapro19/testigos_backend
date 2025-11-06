# Endpoints de Licitaciones para Administrador

## Resumen

Se han implementado dos endpoints faltantes que el frontend estaba llamando pero no existían en el backend:

- `GET /api/v1/admin/bids` - Listar todas las licitaciones con filtros
- `GET /api/v1/admin/bids/statistics` - Obtener estadísticas de licitaciones

## Endpoints Implementados

### 1. GET /api/v1/admin/bids

**Descripción**: Lista todas las licitaciones con filtros opcionales y paginación.

**URL**: `http://localhost:8080/api/v1/admin/bids`

**Método**: `GET`

**Autenticación**: Requerida (role: admin)

**Parámetros de Query (opcionales)**:
- `case_id` (integer): Filtrar por caso específico
- `lawyer_id` (integer): Filtrar por abogado específico
- `status` (string): Filtrar por estado (submitted, under_review, accepted, rejected, withdrawn)
- `page` (integer): Número de página (default: 1)
- `per_page` (integer): Resultados por página (default: 20)

**Respuesta Exitosa (200)**:
```json
{
  "data": [
    {
      "id": 1,
      "case_id": 5,
      "lawyer_id": 3,
      "funding_goal_proposed": 5000000,
      "expected_return_percentage": 150,
      "success_probability": 75,
      "estimated_duration_months": 18,
      "legal_strategy": "Estrategia legal...",
      "status": "submitted",
      "admin_score": null,
      "admin_feedback": null,
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-15T10:30:00.000000Z",
      "lawyer": {
        "id": 3,
        "name": "Juan Pérez",
        "email": "juan.perez@example.com",
        "lawyer_profile": {
          "license_number": "12345",
          "specializations": ["Derecho Civil", "Derecho Laboral"],
          "years_experience": 10,
          "success_rate": 85,
          "cases_handled": 45
        }
      },
      "case": {
        "id": 5,
        "title": "Caso contra empresa X",
        "status": "receiving_bids",
        "funding_goal": 5000000,
        "current_funding": 0
      },
      "reviewer": null
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 45
  }
}
```

**Ejemplo de uso**:
```bash
# Listar todas las licitaciones
curl -H "Authorization: Bearer TOKEN" http://localhost:8080/api/v1/admin/bids

# Filtrar por estado
curl -H "Authorization: Bearer TOKEN" http://localhost:8080/api/v1/admin/bids?status=submitted

# Filtrar por caso
curl -H "Authorization: Bearer TOKEN" http://localhost:8080/api/v1/admin/bids?case_id=5

# Con paginación
curl -H "Authorization: Bearer TOKEN" http://localhost:8080/api/v1/admin/bids?page=2&per_page=10
```

---

### 2. GET /api/v1/admin/bids/statistics

**Descripción**: Obtiene estadísticas generales del sistema de licitaciones.

**URL**: `http://localhost:8080/api/v1/admin/bids/statistics`

**Método**: `GET`

**Autenticación**: Requerida (role: admin)

**Parámetros**: Ninguno

**Respuesta Exitosa (200)**:
```json
{
  "data": {
    "total_bids": 45,
    "submitted_bids": 12,
    "under_review_bids": 8,
    "accepted_bids": 15,
    "rejected_bids": 7,
    "withdrawn_bids": 3,
    "acceptance_rate": 33.33,
    "average_admin_score": 7.5
  }
}
```

**Campo de respuesta**:
- `total_bids`: Total de licitaciones en el sistema
- `submitted_bids`: Licitaciones enviadas (estado: submitted)
- `under_review_bids`: Licitaciones en revisión (estado: under_review)
- `accepted_bids`: Licitaciones aceptadas (estado: accepted)
- `rejected_bids`: Licitaciones rechazadas (estado: rejected)
- `withdrawn_bids`: Licitaciones retiradas (estado: withdrawn)
- `acceptance_rate`: Tasa de aceptación en porcentaje
- `average_admin_score`: Puntuación promedio de admin (1-10), puede ser null

**Ejemplo de uso**:
```bash
curl -H "Authorization: Bearer TOKEN" http://localhost:8080/api/v1/admin/bids/statistics
```

---

## Implementación

### Archivos Modificados

1. **`src/app/Http/Controllers/Api/V1/AdminCaseController.php`**
   - Se agregó el método `getAllBids()` (líneas 362-404)
   - Se agregó el método `getBidStatistics()` (líneas 406-440)

2. **`src/routes/api.php`**
   - Se agregaron las rutas en el grupo admin middleware (líneas 126-127):
     ```php
     Route::get('/bids', [\App\Http\Controllers\Api\V1\AdminCaseController::class, 'getAllBids']);
     Route::get('/bids/statistics', [\App\Http\Controllers\Api\V1\AdminCaseController::class, 'getBidStatistics']);
     ```

### Características Implementadas

✅ **Listado de licitaciones**:
- Filtrado por caso, abogado y estado
- Paginación configurable
- Carga eager loading de relaciones (lawyer, case, reviewer)
- Ordenamiento por fecha de creación (más recientes primero)

✅ **Estadísticas**:
- Conteo por estado
- Tasa de aceptación calculada automáticamente
- Puntuación promedio de admin
- Respuesta en formato compatible con frontend

## Integración con Frontend

El frontend ya estaba preparado para usar estos endpoints:

1. **AdminDashboard.tsx** (línea 20):
   - Llama a `adminApi.getBidStatistics()` al cargar el dashboard
   - Muestra las estadísticas en tarjetas

2. **AdminBidsList.tsx** (línea 58):
   - Llama a `adminApi.getBids(params)` con filtros opcionales
   - Muestra la lista de licitaciones en una tabla

3. **admin.api.ts** (líneas 169-217):
   - Ya tenía definidos los métodos `getBids()` y `getBidStatistics()`
   - Ahora funcionan correctamente con los endpoints creados

## Testing

Para probar los endpoints:

```bash
# 1. Verificar que las rutas están registradas
docker exec testigos_app php artisan route:list --path=admin/bids

# 2. Limpiar caché (ya ejecutado)
docker exec testigos_app php artisan route:clear
docker exec testigos_app php artisan config:clear
docker exec testigos_app php artisan cache:clear

# 3. Probar con curl (reemplazar TOKEN con un token de admin válido)
curl -H "Authorization: Bearer TOKEN" http://localhost:8080/api/v1/admin/bids
curl -H "Authorization: Bearer TOKEN" http://localhost:8080/api/v1/admin/bids/statistics
```

## Flujo Completo de Licitaciones

Con estos endpoints implementados, el flujo completo de administración de licitaciones es:

1. **Admin aprueba caso** → `/admin/cases/{case}/approve-for-bidding`
2. **Abogados licitan** → `/lawyer/cases/{case}/bid`
3. **Admin lista licitaciones** → `/admin/bids` ✅ (NUEVO)
4. **Admin ve estadísticas** → `/admin/bids/statistics` ✅ (NUEVO)
5. **Admin revisa caso y sus bids** → `/admin/cases/{case}/bids`
6. **Admin asigna abogado** → `/admin/cases/{case}/assign-lawyer/{bid}`
7. **Admin publica para inversores** → `/admin/cases/{case}/publish-for-investors`

## Notas

- Los endpoints están protegidos con middleware de autenticación y rol de admin
- Las respuestas incluyen eager loading para evitar el problema N+1
- La paginación ayuda con el rendimiento cuando hay muchas licitaciones
- Los filtros permiten al admin buscar licitaciones específicas de manera eficiente

## Próximos Pasos (Opcional)

Para mejorar aún más estos endpoints, se podría considerar:

- [ ] Agregar más filtros (por fecha, por rango de montos, etc.)
- [ ] Agregar ordenamiento configurable
- [ ] Agregar búsqueda por texto (nombre de abogado, título de caso)
- [ ] Agregar exportación a CSV/Excel
- [ ] Agregar gráficos de tendencias en estadísticas
