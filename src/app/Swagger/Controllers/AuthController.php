<?php

namespace App\Swagger\Controllers;

use OpenApi\Attributes as OA;

class AuthController
{
    #[OA\Post(
        path: "/auth/register",
        summary: "Registrar nuevo usuario",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/RegisterRequest")
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Usuario registrado exitosamente",
                content: new OA\JsonContent(ref: "#/components/schemas/AuthResponse")
            ),
            new OA\Response(response: 422, description: "Error de validación"),
        ]
    )]
    public function register() {}

    #[OA\Post(
        path: "/auth/login",
        summary: "Iniciar sesión",
        tags: ["Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/LoginRequest")
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Inicio de sesión exitoso",
                content: new OA\JsonContent(ref: "#/components/schemas/AuthResponse")
            ),
            new OA\Response(response: 401, description: "Credenciales inválidas"),
        ]
    )]
    public function login() {}

    #[OA\Post(
        path: "/auth/logout",
        summary: "Cerrar sesión",
        security: [["bearerAuth" => []]],
        tags: ["Auth"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Sesión cerrada exitosamente",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "message", type: "string", example: "Logged out successfully")
                    ]
                )
            ),
        ]
    )]
    public function logout() {}

    #[OA\Get(
        path: "/auth/me",
        summary: "Obtener usuario autenticado",
        security: [["bearerAuth" => []]],
        tags: ["Auth"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Usuario autenticado",
                content: new OA\JsonContent(ref: "#/components/schemas/User")
            ),
            new OA\Response(response: 401, description: "No autenticado"),
        ]
    )]
    public function me() {}
}
