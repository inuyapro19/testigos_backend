<?php

namespace App\Swagger\Controllers;

use OpenApi\Attributes as OA;

class RoleController
{
    #[OA\Get(
        path: "/roles",
        summary: "Listar todos los roles",
        security: [["bearerAuth" => []]],
        tags: ["Roles"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de roles",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Role")
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado (solo admin)"),
        ]
    )]
    public function index() {}

    #[OA\Get(
        path: "/roles/{id}",
        summary: "Obtener detalles de un rol",
        security: [["bearerAuth" => []]],
        tags: ["Roles"],
        parameters: [
            new OA\Parameter(
                name: "id",
                in: "path",
                required: true,
                description: "ID del rol",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Detalles del rol con permisos",
                content: new OA\JsonContent(ref: "#/components/schemas/Role")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado"),
            new OA\Response(response: 404, description: "Rol no encontrado"),
        ]
    )]
    public function show() {}

    #[OA\Post(
        path: "/users/{userId}/roles",
        summary: "Asignar rol a usuario",
        security: [["bearerAuth" => []]],
        tags: ["Roles"],
        parameters: [
            new OA\Parameter(
                name: "userId",
                in: "path",
                required: true,
                description: "ID del usuario",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/AssignRoleRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Rol asignado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/UserWithRoles")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado (solo admin)"),
            new OA\Response(response: 404, description: "Usuario no encontrado"),
            new OA\Response(response: 422, description: "Error de validación"),
        ]
    )]
    public function assignRole() {}

    #[OA\Delete(
        path: "/users/{userId}/roles/{role}",
        summary: "Remover rol de usuario",
        security: [["bearerAuth" => []]],
        tags: ["Roles"],
        parameters: [
            new OA\Parameter(
                name: "userId",
                in: "path",
                required: true,
                description: "ID del usuario",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "role",
                in: "path",
                required: true,
                description: "Nombre del rol",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Rol removido exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/UserWithRoles")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado (solo admin)"),
            new OA\Response(response: 404, description: "Usuario o rol no encontrado"),
        ]
    )]
    public function removeRole() {}

    #[OA\Get(
        path: "/users/{userId}/roles",
        summary: "Obtener roles de un usuario",
        security: [["bearerAuth" => []]],
        tags: ["Roles"],
        parameters: [
            new OA\Parameter(
                name: "userId",
                in: "path",
                required: true,
                description: "ID del usuario",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Roles del usuario",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Role")
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Usuario no encontrado"),
        ]
    )]
    public function getUserRoles() {}
}
