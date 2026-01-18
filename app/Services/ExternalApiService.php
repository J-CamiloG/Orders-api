<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExternalApiService
{
    private string $baseUrl;
    private int $timeout;
    private int $retryTimes;
    
    public function __construct()
    {
        $this->baseUrl = config('services.nestjs.url', env('NESTJS_API_URL', 'http://localhost:3000'));
        $this->timeout = config('services.nestjs.timeout', 30);
        $this->retryTimes = config('services.nestjs.retry_times', 3);
    }

    /**
     * Enviar orden al servicio externo
     */
    public function sendOrder(Order $order): array
    {
        try {
            Log::info("Enviando orden al servicio externo", [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'url' => $this->baseUrl . '/external/orders'
            ]);
            
            $response = Http::timeout($this->timeout)
                ->retry($this->retryTimes, 100)
                ->post($this->baseUrl . '/external/orders', [
                    'order_number' => $order->order_number,
                    'customer' => $order->customer,
                    'product' => $order->product,
                    'quantity' => $order->quantity,
                    'laravel_order_id' => $order->id,
                ]);
            
            if ($response->successful()) {
                Log::info("Orden enviada exitosamente", [
                    'order_id' => $order->id,
                    'response' => $response->json()
                ]);
                
                return [
                    'success' => true,
                    'data' => $response->json(),
                    'external_id' => $response->json()['id'] ?? null
                ];
            } else {
                Log::error("Error al enviar orden al servicio externo", [
                    'order_id' => $order->id,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                
                return [
                    'success' => false,
                    'message' => $response->body(),
                    'status_code' => $response->status()
                ];
            }
            
        } catch (\Exception $e) {
            Log::error("Exepcion enviando orden al servicio externo", [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get estado de orden desde el servicio externo
     */
    public function getOrderStatus(string $externalId): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->get($this->baseUrl . "/external/orders/{$externalId}");
            
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }
            
            return [
                'success' => false,
                'message' => 'Error en el servicio externo',
                'status_code' => $response->status()
            ];
            
        } catch (\Exception $e) {
            Log::error("Error obteniendo estado desde el servicio externo", [
                'external_id' => $externalId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Comprobación del estado del servicio externo
     */
    public function healthCheck(): bool
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl . '/health');
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}
