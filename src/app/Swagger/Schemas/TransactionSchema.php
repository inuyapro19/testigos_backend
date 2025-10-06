<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Transaction",
    type: "object",
    required: ["id", "transaction_id", "type", "amount", "direction", "status"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "transaction_id", type: "string", example: "TXN-ABC123DEF456"),
        new OA\Property(
            property: "type",
            type: "string",
            enum: ["investment", "platform_commission", "success_commission", "lawyer_payment", "investor_return", "withdrawal", "gateway_fee", "refund"],
            example: "investment"
        ),
        new OA\Property(property: "case_id", type: "integer", nullable: true, example: 1),
        new OA\Property(property: "investment_id", type: "integer", nullable: true, example: 1),
        new OA\Property(property: "user_id", type: "integer", nullable: true, example: 2),
        new OA\Property(property: "amount", type: "number", format: "decimal", example: 1000000.00),
        new OA\Property(property: "currency", type: "string", example: "CLP"),
        new OA\Property(
            property: "direction",
            type: "string",
            enum: ["in", "out"],
            example: "in"
        ),
        new OA\Property(
            property: "status",
            type: "string",
            enum: ["pending", "processing", "completed", "failed", "cancelled"],
            example: "completed"
        ),
        new OA\Property(property: "payment_gateway", type: "string", nullable: true, example: "transbank"),
        new OA\Property(property: "gateway_transaction_id", type: "string", nullable: true, example: "TBK-789456123"),
        new OA\Property(property: "gateway_fee", type: "number", format: "decimal", example: 25000.00),
        new OA\Property(property: "gateway_response", type: "object", nullable: true),
        new OA\Property(property: "description", type: "string", example: "Inversión en caso #1"),
        new OA\Property(property: "metadata", type: "object", nullable: true),
        new OA\Property(property: "processed_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "completed_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "TransactionList",
    type: "object",
    properties: [
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/Transaction")
        ),
        new OA\Property(
            property: "meta",
            type: "object",
            properties: [
                new OA\Property(property: "current_page", type: "integer", example: 1),
                new OA\Property(property: "per_page", type: "integer", example: 20),
                new OA\Property(property: "total", type: "integer", example: 50),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: "TransactionStatistics",
    type: "object",
    properties: [
        new OA\Property(property: "total_investments", type: "number", format: "decimal", example: 50000000.00),
        new OA\Property(property: "total_platform_commissions", type: "number", format: "decimal", example: 3750000.00),
        new OA\Property(property: "total_success_commissions", type: "number", format: "decimal", example: 2000000.00),
        new OA\Property(property: "total_lawyer_payments", type: "number", format: "decimal", example: 10000000.00),
        new OA\Property(property: "total_investor_returns", type: "number", format: "decimal", example: 65000000.00),
        new OA\Property(property: "total_withdrawals", type: "number", format: "decimal", example: 30000000.00),
        new OA\Property(property: "total_gateway_fees", type: "number", format: "decimal", example: 1500000.00),
        new OA\Property(property: "net_platform_revenue", type: "number", format: "decimal", example: -50750000.00),
    ]
)]
class TransactionSchema
{
}
