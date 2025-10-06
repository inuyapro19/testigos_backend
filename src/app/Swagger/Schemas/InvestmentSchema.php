<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Investment",
    type: "object",
    required: ["id", "case_id", "investor_id", "amount", "status"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "case_id", type: "integer", example: 1),
        new OA\Property(property: "investor_id", type: "integer", example: 4),
        new OA\Property(property: "amount", type: "number", format: "decimal", example: 500000),
        new OA\Property(property: "expected_return_percentage", type: "number", format: "decimal", example: 30),
        new OA\Property(property: "expected_return_amount", type: "number", format: "decimal", example: 150000),
        new OA\Property(
            property: "status",
            type: "string",
            enum: ["pending", "confirmed", "active", "completed", "cancelled"],
            example: "confirmed"
        ),
        new OA\Property(property: "payment_data", type: "object"),
        new OA\Property(property: "confirmed_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "completed_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "actual_return", type: "number", format: "decimal", nullable: true),
        new OA\Property(property: "notes", type: "string", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "CreateInvestmentRequest",
    type: "object",
    required: ["case_id", "amount"],
    properties: [
        new OA\Property(property: "case_id", type: "integer", example: 1),
        new OA\Property(property: "amount", type: "number", example: 500000),
        new OA\Property(
            property: "payment_data",
            type: "object",
            properties: [
                new OA\Property(property: "method", type: "string", example: "webpay"),
                new OA\Property(property: "transaction_id", type: "string", example: "TXN-123456"),
            ]
        ),
    ]
)]
class InvestmentSchema
{
}
