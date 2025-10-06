<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Withdrawal",
    type: "object",
    required: ["id", "withdrawal_id", "user_id", "amount", "status"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "withdrawal_id", type: "string", example: "WTH-ABC123DEF456"),
        new OA\Property(property: "user_id", type: "integer", example: 2),
        new OA\Property(property: "investment_id", type: "integer", nullable: true, example: 1),
        new OA\Property(property: "amount", type: "number", format: "decimal", example: 1000000.00),
        new OA\Property(property: "fee", type: "number", format: "decimal", example: 20000.00),
        new OA\Property(property: "net_amount", type: "number", format: "decimal", example: 980000.00),
        new OA\Property(property: "currency", type: "string", example: "CLP"),
        new OA\Property(
            property: "status",
            type: "string",
            enum: ["pending", "approved", "processing", "completed", "rejected", "cancelled"],
            example: "pending"
        ),
        new OA\Property(
            property: "payment_method",
            type: "string",
            enum: ["bank_transfer", "check", "paypal", "other"],
            example: "bank_transfer"
        ),
        new OA\Property(
            property: "payment_details",
            type: "object",
            properties: [
                new OA\Property(property: "bank_name", type: "string", example: "Banco de Chile"),
                new OA\Property(property: "account_number", type: "string", example: "1234567890"),
                new OA\Property(property: "account_holder", type: "string", example: "Juan Pérez"),
            ]
        ),
        new OA\Property(property: "approved_by", type: "integer", nullable: true, example: 1),
        new OA\Property(property: "approved_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "rejection_reason", type: "string", nullable: true),
        new OA\Property(property: "transaction_id", type: "integer", nullable: true, example: 5),
        new OA\Property(property: "transfer_reference", type: "string", nullable: true, example: "REF-987654321"),
        new OA\Property(property: "processed_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "completed_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "user_notes", type: "string", nullable: true),
        new OA\Property(property: "admin_notes", type: "string", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "CreateWithdrawalRequest",
    type: "object",
    required: ["amount", "payment_method", "payment_details"],
    properties: [
        new OA\Property(property: "amount", type: "number", format: "decimal", example: 1000000.00),
        new OA\Property(
            property: "payment_method",
            type: "string",
            enum: ["bank_transfer", "check", "paypal", "other"],
            example: "bank_transfer"
        ),
        new OA\Property(
            property: "payment_details",
            type: "object",
            required: ["bank_name", "account_number", "account_holder"],
            properties: [
                new OA\Property(property: "bank_name", type: "string", example: "Banco de Chile"),
                new OA\Property(property: "account_number", type: "string", example: "1234567890"),
                new OA\Property(property: "account_holder", type: "string", example: "Juan Pérez"),
            ]
        ),
        new OA\Property(property: "investment_id", type: "integer", nullable: true, example: 1),
        new OA\Property(property: "user_notes", type: "string", example: "Retiro de ganancias del trimestre"),
    ]
)]
#[OA\Schema(
    schema: "WithdrawalList",
    type: "object",
    properties: [
        new OA\Property(
            property: "data",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/Withdrawal")
        ),
        new OA\Property(
            property: "meta",
            type: "object",
            properties: [
                new OA\Property(property: "current_page", type: "integer", example: 1),
                new OA\Property(property: "per_page", type: "integer", example: 15),
                new OA\Property(property: "total", type: "integer", example: 30),
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: "WithdrawalStatistics",
    type: "object",
    properties: [
        new OA\Property(property: "total_pending", type: "integer", example: 5),
        new OA\Property(property: "total_approved", type: "integer", example: 20),
        new OA\Property(property: "total_completed", type: "integer", example: 15),
        new OA\Property(property: "total_amount_pending", type: "number", format: "decimal", example: 5000000.00),
        new OA\Property(property: "total_amount_completed", type: "number", format: "decimal", example: 30000000.00),
        new OA\Property(property: "total_fees_collected", type: "number", format: "decimal", example: 600000.00),
    ]
)]
#[OA\Schema(
    schema: "AvailableBalance",
    type: "object",
    properties: [
        new OA\Property(property: "available_balance", type: "number", format: "decimal", example: 2500000.00),
        new OA\Property(property: "currency", type: "string", example: "CLP"),
    ]
)]
class WithdrawalSchema
{
}
