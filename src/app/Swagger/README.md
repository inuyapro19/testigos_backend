# Documentación API Swagger

Esta carpeta contiene toda la documentación de Swagger/OpenAPI para la API de Testigos, manteniendo los controladores limpios y separando la documentación del código.

## Estructura

```
app/Swagger/
├── OpenApiSpec.php          # Configuración general de la API
├── Schemas/                 # Esquemas de datos (DTOs)
│   ├── UserSchema.php
│   ├── CaseSchema.php
│   ├── InvestmentSchema.php
│   └── RoleSchema.php
└── Controllers/             # Documentación de endpoints
    ├── AuthController.php
    ├── CaseController.php
    ├── InvestmentController.php
    ├── RoleController.php
    └── PermissionController.php
```

## Acceso a la documentación

- **Swagger UI**: http://localhost:8080/api/documentation
- **JSON**: http://localhost:8080/docs/api-docs.json

## Cómo agregar nueva documentación

### 1. Para un nuevo schema (modelo)

Crear archivo en `app/Swagger/Schemas/`:

```php
<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "MiModelo",
    type: "object",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Ejemplo"),
    ]
)]
class MiModeloSchema
{
}
```

### 2. Para nuevos endpoints

Crear archivo en `app/Swagger/Controllers/`:

```php
<?php

namespace App\Swagger\Controllers;

use OpenApi\Attributes as OA;

class MiController
{
    #[OA\Get(
        path: "/mi-ruta",
        summary: "Descripción del endpoint",
        tags: ["MiTag"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Respuesta exitosa",
                content: new OA\JsonContent(ref: "#/components/schemas/MiModelo")
            ),
        ]
    )]
    public function miMetodo() {}
}
```

### 3. Regenerar documentación

Después de agregar o modificar la documentación, ejecutar:

```bash
docker exec testigos_app php artisan l5-swagger:generate
```

## Ventajas de esta estructura

✅ **Controladores limpios**: Sin anotaciones Swagger en el código de negocio
✅ **Organización clara**: Separación entre schemas y endpoints
✅ **Fácil mantenimiento**: Toda la documentación en un solo lugar
✅ **Sin duplicación**: Los modelos reales no se mezclan con la documentación
✅ **Mejor colaboración**: Los equipos pueden trabajar en docs y código por separado

## Roles y Permisos

### Roles disponibles
- **admin**: Acceso total al sistema
- **victim**: Víctimas que crean casos
- **lawyer**: Abogados que gestionan casos
- **investor**: Inversores que financian casos

### Permisos principales
- `view_cases`, `create_case`, `edit_case`, `delete_case`, `publish_case`
- `view_investments`, `create_investment`, `manage_investments`
- `upload_documents`, `view_documents`, `delete_documents`
- `manage_users`, `verify_lawyers`
- `create_updates`, `view_updates`

### Endpoints documentados
- **GET /roles**: Listar roles
- **POST /users/{userId}/roles**: Asignar rol a usuario
- **GET /users/{userId}/roles**: Obtener roles de usuario
- **GET /permissions**: Listar permisos
- **POST /users/{userId}/permissions**: Asignar permiso a usuario
- **POST /roles/{roleId}/permissions**: Asignar permiso a rol

## Usuarios de prueba

- **Admin**: admin@testigos.cl / password
- **Víctima**: maria@testigos.cl / password
- **Abogado**: carlos@testigos.cl / password
- **Inversor**: pedro@testigos.cl / password
