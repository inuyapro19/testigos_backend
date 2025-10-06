<?php

namespace App\Swagger\Controllers;

use OpenApi\Attributes as OA;

class TransactionController
{
    #[OA\Get(
        path: "/transactions",
        summary: "Listar transacciones del usuario autenticado (o todas si es admin)",
        security: [["bearerAuth" => []]],
        tags: ["Transactions"],
        parameters: [
            new OA\Parameter(
                name: "type",
                in: "query",
                description: "Filtrar por tipo de transacción",
                schema: new OA\Schema(
                    type: "string",
                    enum: ["investment", "platform_commission", "success_commission", "lawyer_payment", "investor_return", "withdrawal", "gateway_fee", "refund"]
                )
            ),
            new OA\Parameter(
                name: "status",
                in: "query",
                description: "Filtrar por estado",
                schema: new OA\Schema(
                    type: "string",
                    enum: ["pending", "processing", "completed", "failed", "cancelled"]
                )
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
                description: "Lista de transacciones",
                content: new OA\JsonContent(ref: "#/components/schemas/TransactionList")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: "/transactions/{id}",
        summary: "Obtener detalles de una transacción",
        security: [["bearerAuth" => []]],
        tags: ["Transactions"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de la transacción",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Detalles de la transacción",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "transaction", ref: "#/components/schemas/Transaction"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado"),
            new OA\Response(response: 404, description: "Transacción no encontrada"),
        ]
    )]
    public function show() {}

    #[OA\Get(
        path: "/transactions/case/{caseId}",
        summary: "Obtener todas las transacciones de un caso específico",
        security: [["bearerAuth" => []]],
        tags: ["Transactions"],
        parameters: [
            new OA\Parameter(
                name: "caseId",
                in: "path",
                required: true,
                description: "ID del caso",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Transacciones del caso",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: "transactions",
                            type: "array",
                            items: new OA\Items(ref: "#/components/schemas/Transaction")
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado para ver este caso"),
            new OA\Response(response: 404, description: "Caso no encontrado"),
        ]
    )]
    public function caseTransactions() {}

    #[OA\Get(
        path: "/transactions/statistics/all",
        summary: "Obtener estadísticas de transacciones (solo admin)",
        security: [["bearerAuth" => []]],
        tags: ["Transactions"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Estadísticas de transacciones",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "statistics", ref: "#/components/schemas/TransactionStatistics"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "Solo admins pueden ver estadísticas"),
        ]
    )]
    public function statistics() {}
}
