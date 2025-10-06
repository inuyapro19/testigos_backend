<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Case",
    type: "object",
    required: ["id", "title", "description", "status"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "title", type: "string", example: "Despido injustificado durante licencia médica"),
        new OA\Property(property: "description", type: "string", example: "Fui despedida de mi trabajo mientras estaba con licencia médica..."),
        new OA\Property(property: "victim_id", type: "integer", example: 2),
        new OA\Property(property: "lawyer_id", type: "integer", nullable: true, example: 3),
        new OA\Property(
            property: "status",
            type: "string",
            enum: ["draft", "pending", "published", "funding", "in_progress", "completed", "closed"],
            example: "published"
        ),
        new OA\Property(property: "category", type: "string", example: "despido_injustificado"),
        new OA\Property(property: "company", type: "string", example: "Retail Chile S.A."),
        new OA\Property(property: "funding_goal", type: "number", format: "decimal", example: 1500000),
        new OA\Property(property: "current_funding", type: "number", format: "decimal", example: 750000),
        new OA\Property(property: "success_rate", type: "number", format: "decimal", example: 85),
        new OA\Property(property: "expected_return", type: "number", format: "decimal", example: 30),
        new OA\Property(property: "deadline", type: "string", format: "date", example: "2025-06-01"),
        new OA\Property(property: "legal_analysis", type: "string"),
        new OA\Property(property: "evaluation_data", type: "object"),
        new OA\Property(property: "lawyer_evaluation_fee", type: "number", format: "decimal", example: 500000),
        new OA\Property(property: "lawyer_success_fee_percentage", type: "number", format: "decimal", example: 15),
        new OA\Property(property: "lawyer_fixed_fee", type: "number", format: "decimal", example: 2000000),
        new OA\Property(property: "lawyer_total_compensation", type: "number", format: "decimal", example: 2500000),
        new OA\Property(property: "lawyer_paid_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(
            property: "outcome",
            type: "string",
            enum: ["won", "lost", "settled", "dismissed"],
            nullable: true,
            example: "won"
        ),
        new OA\Property(property: "amount_recovered", type: "number", format: "decimal", nullable: true, example: 15000000),
        new OA\Property(property: "legal_costs", type: "number", format: "decimal", example: 2000000),
        new OA\Property(property: "outcome_description", type: "string", nullable: true),
        new OA\Property(property: "resolution_date", type: "string", format: "date", nullable: true, example: "2025-11-15"),
        new OA\Property(property: "closed_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "CreateCaseRequest",
    type: "object",
    required: ["title", "description", "category"],
    properties: [
        new OA\Property(property: "title", type: "string", example: "Despido injustificado"),
        new OA\Property(property: "description", type: "string", example: "Descripción detallada del caso..."),
        new OA\Property(property: "category", type: "string", example: "despido_injustificado"),
        new OA\Property(property: "company", type: "string", example: "Empresa ABC S.A."),
        new OA\Property(property: "funding_goal", type: "number", example: 1500000),
    ]
)]
#[OA\Schema(
    schema: "CaseList",
    type: "object",
    properties: [
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/Case")
        ),
        new OA\Property(
            property: "meta",
            type: "object",
            properties: [
                new OA\Property(property: "current_page", type: "integer", example: 1),
                new OA\Property(property: "per_page", type: "integer", example: 15),
                new OA\Property(property: "total", type: "integer", example: 100),
            ]
        ),
    ]
)]
class CaseSchema
{
}
