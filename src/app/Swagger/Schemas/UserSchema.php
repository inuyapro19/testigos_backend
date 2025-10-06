<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "User",
    type: "object",
    required: ["id", "name", "email", "role"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "María González"),
        new OA\Property(property: "email", type: "string", format: "email", example: "maria@testigos.cl"),
        new OA\Property(property: "rut", type: "string", example: "12345678-9"),
        new OA\Property(property: "birth_date", type: "string", format: "date", example: "1990-05-15"),
        new OA\Property(property: "address", type: "string", example: "Santiago, Chile"),
        new OA\Property(property: "phone", type: "string", example: "+56912345678"),
        new OA\Property(
            property: "role",
            type: "string",
            enum: ["admin", "victim", "lawyer", "investor"],
            example: "victim"
        ),
        new OA\Property(property: "avatar", type: "string", nullable: true, example: "https://example.com/avatar.jpg"),
        new OA\Property(property: "is_active", type: "boolean", example: true),
        new OA\Property(property: "last_login_at", type: "string", format: "date-time", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "LoginRequest",
    type: "object",
    required: ["email", "password"],
    properties: [
        new OA\Property(property: "email", type: "string", format: "email", example: "maria@testigos.cl"),
        new OA\Property(property: "password", type: "string", format: "password", example: "password"),
    ]
)]
#[OA\Schema(
    schema: "RegisterRequest",
    type: "object",
    required: ["name", "email", "password", "role"],
    properties: [
        new OA\Property(property: "name", type: "string", example: "María González"),
        new OA\Property(property: "email", type: "string", format: "email", example: "maria@testigos.cl"),
        new OA\Property(property: "password", type: "string", format: "password", example: "password"),
        new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "password"),
        new OA\Property(property: "rut", type: "string", example: "12345678-9"),
        new OA\Property(property: "phone", type: "string", example: "+56912345678"),
        new OA\Property(
            property: "role",
            type: "string",
            enum: ["victim", "lawyer", "investor"],
            example: "victim"
        ),
    ]
)]
#[OA\Schema(
    schema: "AuthResponse",
    type: "object",
    properties: [
        new OA\Property(property: "access_token", type: "string", example: "1|abcdef123456..."),
        new OA\Property(property: "token_type", type: "string", example: "Bearer"),
        new OA\Property(
            property: "user",
            ref: "#/components/schemas/User"
        ),
    ]
)]
class UserSchema
{
}
