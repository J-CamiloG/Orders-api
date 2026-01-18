<?php

namespace App\Swagger;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="Orders API",
 *     version="1.0.0",
 *     description="API para gestionar órdenes"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Servidor local"
 * )
 */
class SwaggerAnnotations {}
