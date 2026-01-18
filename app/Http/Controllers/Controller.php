<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="Orders API - Laravel Backend",
 *     version="1.0.0",
 *     description="API para gestión de órdenes con procesamiento asíncrono y comunicación con servicio externo NestJS",
 *     @OA\Contact(
 *         email="support@ordersapi.com"
 *     )
 * )
 * @OA\Server(
 *     url="http://localhost:8000",
 *     description="Servidor de Desarrollo Local"
 * )
 * @OA\Server(
 *     url="https://api.ordersapi.com",
 *     description="Servidor de Producción"
 * )
 */
abstract class Controller
{
    //
}
