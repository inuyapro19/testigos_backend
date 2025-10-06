<?php

namespace App\Swagger\Controllers;

use OpenApi\Attributes as OA;

class InvestmentController
{
    #[OA\Get(
        path: "/investments",
        summary: "Listar inversiones",
        security: [["bearerAuth" => []]],
        tags: ["Investments"],
        parameters: [
            new OA\Parameter(
                name: "case_id",
                in: "query",
                description: "Filtrar por caso",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "status",
                in: "query",
                description: "Filtrar por estado",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de inversiones",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Investment")
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
        ]
    )]
    public function index() {}

    #[OA\Post(
        path: "/investments",
        summary: "Crear nueva inversión",
        security: [["bearerAuth" => []]],
        tags: ["Investments"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/CreateInvestmentRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Inversión creada exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Investment")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 422, description: "Error de validación"),
        ]
    )]
    public function store() {}

    #[OA\Get(
        path: "/investments/{id}",
        summary: "Obtener detalles de una inversión",
        security: [["bearerAuth" => []]],
        tags: ["Investments"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID de la inversión",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Detalles de la inversión",
                content: new OA\JsonContent(ref: "#/components/schemas/Investment")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Inversión no encontrada"),
        ]
    )]
    public function show() {}
}
