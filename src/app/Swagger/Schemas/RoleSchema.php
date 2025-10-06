<?php

namespace App\Swagger\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Role",
    type: "object",
    required: ["id", "name"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "lawyer"),
        new OA\Property(property: "guard_name", type: "string", example: "web"),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
        new OA\Property(
            property: "permissions",
            type: "array",
            items: new OA\Items(ref: "#/components/schemas/Permission")
        ),
    ]
)]
#[OA\Schema(
    schema: "Permission",
    type: "object",
    required: ["id", "name"],
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "create_case"),
        new OA\Property(property: "guard_name", type: "string", example: "web"),
        new OA\Property(property: "created_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time"),
    ]
)]
#[OA\Schema(
    schema: "AssignRoleRequest",
    type: "object",
    required: ["role"],
    properties: [
        new OA\Property(
            property: "role",
            type: "string",
            enum: ["admin", "victim", "lawyer", "investor"],
            example: "lawyer"
        ),
    ]
)]
#[OA\Schema(
    schema: "AssignPermissionRequest",
    type: "object",
    required: ["permission"],
    properties: [
        new OA\Property(property: "permission", type: "string", example: "create_case"),
    ]
)]
#[OA\Schema(
    schema: "UserWithRoles",
    allOf: [
        new OA\Schema(ref: "#/components/schemas/User"),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: "roles",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Role")
                ),
                new OA\Property(
                    property: "permissions",
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Permission")
                ),
            ]
        )
    ]
)]
class RoleSchema
{
}
