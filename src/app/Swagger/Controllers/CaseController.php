<?php

namespace App\Swagger\Controllers;

use OpenApi\Attributes as OA;

class CaseController
{
    #[OA\Get(
        path: "/cases",
        summary: "Listar casos",
        tags: ["Cases"],
        parameters: [
            new OA\Parameter(
                name: "status",
                in: "query",
                description: "Filtrar por estado",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "category",
                in: "query",
                description: "Filtrar por categoría",
                schema: new OA\Schema(type: "string")
            ),
            new OA\Parameter(
                name: "page",
                in: "query",
                description: "Número de página",
                schema: new OA\Schema(type: "integer", default: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de casos",
                content: new OA\JsonContent(ref: "#/components/schemas/CaseList")
            ),
        ]
    )]
    public function index() {}

    #[OA\Post(
        path: "/cases",
        summary: "Crear nuevo caso",
        security: [["bearerAuth" => []]],
        tags: ["Cases"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateCaseRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Caso creado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Case")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 422, description: "Error de validación"),
        ]
    )]
    public function store() {}

    #[OA\Get(
        path: "/cases/{id}",
        summary: "Obtener detalles de un caso",
        tags: ["Cases"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del caso",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Detalles del caso",
                content: new OA\JsonContent(ref: "#/components/schemas/Case")
            ),
            new OA\Response(response: 404, description: "Caso no encontrado"),
        ]
    )]
    public function show() {}

    #[OA\Put(
        path: "/cases/{id}",
        summary: "Actualizar caso",
        security: [["bearerAuth" => []]],
        tags: ["Cases"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del caso",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateCaseRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Caso actualizado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Case")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado"),
            new OA\Response(response: 404, description: "Caso no encontrado"),
        ]
    )]
    public function update() {}

    #[OA\Delete(
        path: "/cases/{id}",
        summary: "Eliminar caso",
        security: [["bearerAuth" => []]],
        tags: ["Cases"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del caso",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: "Caso eliminado exitosamente"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado"),
            new OA\Response(response: 404, description: "Caso no encontrado"),
        ]
    )]
    public function destroy() {}

    #[OA\Post(
        path: "/cases/{id}/assign-lawyer",
        summary: "Asignar abogado a un caso",
        security: [["bearerAuth" => []]],
        tags: ["Cases"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del caso",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Abogado asignado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Case")
            ),
            new OA\Response(response: 400, description: "El caso no está disponible para asignación"),
            new OA\Response(response: 403, description: "Solo abogados pueden asignarse a casos"),
        ]
    )]
    public function assignLawyer() {}

    #[OA\Post(
        path: "/cases/{id}/evaluate",
        summary: "Evaluar caso (aprobar o rechazar)",
        security: [["bearerAuth" => []]],
        tags: ["Cases"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del caso",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["action"],
                properties: [
                    new OA\Property(property: "action", type: "string", enum: ["approve", "reject"], example: "approve"),
                    new OA\Property(property: "legal_analysis", type: "string", example: "El caso tiene alta probabilidad de éxito..."),
                    new OA\Property(property: "success_rate", type: "number", example: 85),
                    new OA\Property(property: "funding_goal", type: "number", example: 5000000),
                    new OA\Property(property: "expected_return", type: "number", example: 30),
                    new OA\Property(property: "deadline", type: "string", format: "date", example: "2025-12-31"),
                    new OA\Property(property: "rejection_reason", type: "string", example: "Falta evidencia suficiente"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Caso evaluado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Case")
            ),
            new OA\Response(response: 400, description: "El caso debe estar en revisión para ser evaluado"),
            new OA\Response(response: 403, description: "Solo abogados y admins pueden evaluar casos"),
        ]
    )]
    public function evaluate() {}

    #[OA\Post(
        path: "/cases/{id}/publish",
        summary: "Publicar caso aprobado (hacerlo visible a inversionistas)",
        security: [["bearerAuth" => []]],
        tags: ["Cases"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del caso",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Caso publicado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Case")
            ),
            new OA\Response(response: 400, description: "Solo casos aprobados pueden ser publicados"),
            new OA\Response(response: 403, description: "Solo abogados y admins pueden publicar casos"),
        ]
    )]
    public function publish() {}

    #[OA\Post(
        path: "/cases/{id}/start",
        summary: "Iniciar caso financiado (cambiar a in_progress)",
        security: [["bearerAuth" => []]],
        tags: ["Cases"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del caso",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Caso iniciado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Case")
            ),
            new OA\Response(response: 400, description: "El caso debe estar financiado para iniciarse"),
            new OA\Response(response: 403, description: "Solo abogados y admins pueden iniciar casos"),
        ]
    )]
    public function start() {}

    #[OA\Post(
        path: "/cases/{id}/close",
        summary: "Cerrar caso con resultado final",
        security: [["bearerAuth" => []]],
        tags: ["Cases"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del caso",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["outcome"],
                properties: [
                    new OA\Property(property: "outcome", type: "string", enum: ["won", "lost", "settled", "dismissed"], example: "won"),
                    new OA\Property(property: "amount_recovered", type: "number", example: 15000000),
                    new OA\Property(property: "legal_costs", type: "number", example: 2000000),
                    new OA\Property(property: "outcome_description", type: "string", example: "Se llegó a un acuerdo favorable..."),
                    new OA\Property(property: "resolution_date", type: "string", format: "date", example: "2025-11-15"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Caso cerrado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Case")
            ),
            new OA\Response(response: 400, description: "El caso debe estar en progreso para cerrarse"),
            new OA\Response(response: 403, description: "Solo abogados y admins pueden cerrar casos"),
        ]
    )]
    public function close() {}

    #[OA\Post(
        path: "/cases/{id}/distribute-returns",
        summary: "Distribuir retornos a inversionistas después de ganar",
        security: [["bearerAuth" => []]],
        tags: ["Cases"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del caso",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Retornos distribuidos exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Returns distributed successfully"),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "case_id", type: "integer", example: 1),
                                new OA\Property(property: "lawyer_payment", ref: "#/components/schemas/Transaction"),
                                new OA\Property(property: "investor_returns_count", type: "integer", example: 5),
                                new OA\Property(
                                    property: "investor_returns",
                                    type: "array",
                                    items: new OA\Items(type: "object")
                                ),
                                new OA\Property(property: "total_platform_commission", type: "number", example: 500000),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "El caso debe estar completo y ganado para distribuir retornos"),
            new OA\Response(response: 403, description: "Solo admins pueden distribuir retornos"),
        ]
    )]
    public function distributeReturns() {}
}
