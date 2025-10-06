<?php

namespace App\Swagger\Controllers;

use OpenApi\Attributes as OA;

class WithdrawalController
{
    #[OA\Get(
        path: "/withdrawals",
        summary: "Listar retiros del usuario autenticado (o todos si es admin)",
        security: [["bearerAuth" => []]],
        tags: ["Withdrawals"],
        parameters: [
            new OA\Parameter(
                name: "status",
                in: "query",
                description: "Filtrar por estado",
                schema: new OA\Schema(
                    type: "string",
                    enum: ["pending", "approved", "processing", "completed", "rejected", "cancelled"]
                )
            ),
            new OA\Parameter(
                name: "pending_only",
                in: "query",
                description: "Solo retiros pendientes (admin)",
                schema: new OA\Schema(type: "boolean")
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
                description: "Lista de retiros",
                content: new OA\JsonContent(ref: "#/components/schemas/WithdrawalList")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
        ]
    )]
    public function index() {}

    #[OA\Post(
        path: "/withdrawals",
        summary: "Solicitar un retiro",
        security: [["bearerAuth" => []]],
        tags: ["Withdrawals"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateWithdrawalRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Retiro solicitado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Withdrawal request created successfully"),
                        new OA\Property(property: "withdrawal", ref: "#/components/schemas/Withdrawal"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Balance insuficiente"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "Solo inversionistas y abogados pueden solicitar retiros"),
            new OA\Response(response: 422, description: "Error de validación"),
        ]
    )]
    public function store() {}

    #[OA\Get(
        path: "/withdrawals/{id}",
        summary: "Obtener detalles de un retiro",
        security: [["bearerAuth" => []]],
        tags: ["Withdrawals"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del retiro",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Detalles del retiro",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "withdrawal", ref: "#/components/schemas/Withdrawal"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado"),
            new OA\Response(response: 404, description: "Retiro no encontrado"),
        ]
    )]
    public function show() {}

    #[OA\Post(
        path: "/withdrawals/{id}/approve",
        summary: "Aprobar un retiro (solo admin)",
        security: [["bearerAuth" => []]],
        tags: ["Withdrawals"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del retiro",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Retiro aprobado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Withdrawal approved successfully"),
                        new OA\Property(property: "withdrawal", ref: "#/components/schemas/Withdrawal"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "El retiro no puede ser aprobado en su estado actual"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "Solo admins pueden aprobar retiros"),
            new OA\Response(response: 404, description: "Retiro no encontrado"),
        ]
    )]
    public function approve() {}

    #[OA\Post(
        path: "/withdrawals/{id}/reject",
        summary: "Rechazar un retiro (solo admin)",
        security: [["bearerAuth" => []]],
        tags: ["Withdrawals"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del retiro",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["rejection_reason"],
                properties: [
                    new OA\Property(property: "rejection_reason", type: "string", example: "Datos bancarios incorrectos"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Retiro rechazado",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Withdrawal rejected"),
                        new OA\Property(property: "withdrawal", ref: "#/components/schemas/Withdrawal"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "El retiro no puede ser rechazado en su estado actual"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "Solo admins pueden rechazar retiros"),
            new OA\Response(response: 404, description: "Retiro no encontrado"),
        ]
    )]
    public function reject() {}

    #[OA\Post(
        path: "/withdrawals/{id}/process",
        summary: "Procesar un retiro aprobado (solo admin)",
        security: [["bearerAuth" => []]],
        tags: ["Withdrawals"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del retiro",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Retiro en proceso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Withdrawal processing started"),
                        new OA\Property(property: "withdrawal", ref: "#/components/schemas/Withdrawal"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "El retiro no puede ser procesado en su estado actual"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "Solo admins pueden procesar retiros"),
            new OA\Response(response: 404, description: "Retiro no encontrado"),
        ]
    )]
    public function process() {}

    #[OA\Post(
        path: "/withdrawals/{id}/complete",
        summary: "Completar un retiro procesado (solo admin)",
        security: [["bearerAuth" => []]],
        tags: ["Withdrawals"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del retiro",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["transfer_reference"],
                properties: [
                    new OA\Property(property: "transfer_reference", type: "string", example: "REF-123456789"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Retiro completado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Withdrawal completed successfully"),
                        new OA\Property(property: "withdrawal", ref: "#/components/schemas/Withdrawal"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Error al completar retiro"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "Solo admins pueden completar retiros"),
            new OA\Response(response: 404, description: "Retiro no encontrado"),
        ]
    )]
    public function complete() {}

    #[OA\Delete(
        path: "/withdrawals/{id}",
        summary: "Cancelar un retiro",
        security: [["bearerAuth" => []]],
        tags: ["Withdrawals"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del retiro",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Retiro cancelado exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Withdrawal cancelled successfully"),
                        new OA\Property(property: "withdrawal", ref: "#/components/schemas/Withdrawal"),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "No se puede cancelar un retiro completado"),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado"),
            new OA\Response(response: 404, description: "Retiro no encontrado"),
        ]
    )]
    public function destroy() {}

    #[OA\Get(
        path: "/withdrawals/balance/available",
        summary: "Obtener balance disponible para retiro",
        security: [["bearerAuth" => []]],
        tags: ["Withdrawals"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Balance disponible",
                content: new OA\JsonContent(ref: "#/components/schemas/AvailableBalance")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "Solo inversionistas y abogados pueden consultar balance"),
        ]
    )]
    public function availableBalance() {}

    #[OA\Get(
        path: "/withdrawals/statistics/all",
        summary: "Obtener estadísticas de retiros (solo admin)",
        security: [["bearerAuth" => []]],
        tags: ["Withdrawals"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Estadísticas de retiros",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "statistics", ref: "#/components/schemas/WithdrawalStatistics"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "Solo admins pueden ver estadísticas"),
        ]
    )]
    public function statistics() {}
}
