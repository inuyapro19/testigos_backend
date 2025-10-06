<?php

namespace App\Swagger\Controllers;

use OpenApi\Attributes as OA;

class PermissionController
{
    #[OA\Get(
        path: "/permissions",
        summary: "Listar todos los permisos",
        security: [["bearerAuth" => []]],
        tags: ["Permissions"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Lista de permisos",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Permission")
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado (solo admin)"),
        ]
    )]
    public function index() {}

    #[OA\Post(
        path: "/users/{userId}/permissions",
        summary: "Asignar permiso a usuario",
        security: [["bearerAuth" => []]],
        tags: ["Permissions"],
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
            content: new OA\JsonContent(ref: "#/components/schemas/AssignPermissionRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Permiso asignado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/UserWithRoles")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado (solo admin)"),
            new OA\Response(response: 404, description: "Usuario no encontrado"),
            new OA\Response(response: 422, description: "Error de validación"),
        ]
    )]
    public function assignPermission() {}

    #[OA\Delete(
        path: "/users/{userId}/permissions/{permission}",
        summary: "Remover permiso de usuario",
        security: [["bearerAuth" => []]],
        tags: ["Permissions"],
        parameters: [
            new OA\Parameter(
                name: "userId",
                in: "path",
                required: true,
                description: "ID del usuario",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "permission",
                in: "path",
                required: true,
                description: "Nombre del permiso",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Permiso removido exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/UserWithRoles")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado (solo admin)"),
            new OA\Response(response: 404, description: "Usuario o permiso no encontrado"),
        ]
    )]
    public function removePermission() {}

    #[OA\Get(
        path: "/users/{userId}/permissions",
        summary: "Obtener permisos de un usuario",
        security: [["bearerAuth" => []]],
        tags: ["Permissions"],
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
                description: "Permisos del usuario (directos + heredados de roles)",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/Permission")
                )
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 404, description: "Usuario no encontrado"),
        ]
    )]
    public function getUserPermissions() {}

    #[OA\Post(
        path: "/roles/{roleId}/permissions",
        summary: "Asignar permiso a rol",
        security: [["bearerAuth" => []]],
        tags: ["Permissions"],
        parameters: [
            new OA\Parameter(
                name: "roleId",
                in: "path",
                required: true,
                description: "ID del rol",
                schema: new OA\Schema(type: "integer")
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/AssignPermissionRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Permiso asignado al rol exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Role")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado (solo admin)"),
            new OA\Response(response: 404, description: "Rol no encontrado"),
            new OA\Response(response: 422, description: "Error de validación"),
        ]
    )]
    public function assignPermissionToRole() {}

    #[OA\Delete(
        path: "/roles/{roleId}/permissions/{permission}",
        summary: "Remover permiso de rol",
        security: [["bearerAuth" => []]],
        tags: ["Permissions"],
        parameters: [
            new OA\Parameter(
                name: "roleId",
                in: "path",
                required: true,
                description: "ID del rol",
                schema: new OA\Schema(type: "integer")
            ),
            new OA\Parameter(
                name: "permission",
                in: "path",
                required: true,
                description: "Nombre del permiso",
                schema: new OA\Schema(type: "string")
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Permiso removido del rol exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/Role")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
            new OA\Response(response: 403, description: "No autorizado (solo admin)"),
            new OA\Response(response: 404, description: "Rol o permiso no encontrado"),
        ]
    )]
    public function removePermissionFromRole() {}
}
