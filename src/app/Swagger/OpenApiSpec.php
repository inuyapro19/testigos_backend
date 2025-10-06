<?php

namespace App\Swagger;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Testigos API",
    description: "API para plataforma de crowdfunding legal - Conecta víctimas, abogados e inversores para financiar casos legales"
)]
#[OA\Server(
    url: "http://localhost:8080/api",
    description: "Servidor de desarrollo"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "JWT"
)]
#[OA\Tag(
    name: "Auth",
    description: "Endpoints de autenticación"
)]
#[OA\Tag(
    name: "Cases",
    description: "Gestión de casos legales"
)]
#[OA\Tag(
    name: "Investments",
    description: "Gestión de inversiones"
)]
#[OA\Tag(
    name: "Lawyers",
    description: "Gestión de abogados"
)]
#[OA\Tag(
    name: "Users",
    description: "Gestión de usuarios"
)]
#[OA\Tag(
    name: "Roles",
    description: "Gestión de roles y asignación a usuarios"
)]
#[OA\Tag(
    name: "Permissions",
    description: "Gestión de permisos para usuarios y roles"
)]
#[OA\Tag(
    name: "Transactions",
    description: "Gestión de transacciones financieras de la plataforma"
)]
#[OA\Tag(
    name: "Withdrawals",
    description: "Gestión de retiros de fondos para inversionistas y abogados"
)]
class OpenApiSpec
{
}
