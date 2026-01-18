<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\OrderService;
use App\Services\ExternalApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

class ProcessOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * el número de intentos para este job.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * el nuermo de segundos para esperar antes de reintentar el job.
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * El ID del pedido a procesar.
     *
     * @var int
     */
    protected int $orderId;

    /**
     * crear una nueva instancia de job.
     */
    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    /**
     * Ejecutar el job.
     */
    public function handle(OrderService $orderService, ExternalApiService $externalApiService): void
    {
        try {
            Log::info("Processing order job started", ['order_id' => $this->orderId]);

            // Get la orden
            $order = Order::findOrFail($this->orderId);

            // Update estado en progreso
            $orderService->updateOrderStatus($order->id, 'processing', 'orden en procesamiento');

            // Send a servicio externo
            Log::info("enviando orden a servicio externo", ['order_id' => $order->id]);
            
            $response = $externalApiService->sendOrder($order);

            // Check response
            if (isset($response['success']) && $response['success']) {
                $orderService->updateOrderStatus(
                    $order->id,
                    'completed',
                    'orden procesada exitosamente por el servicio externo'
                );

                Log::info("orden procesada exitosamente", [
                    'order_id' => $order->id,
                    'external_response' => $response
                ]);
            } else {
                $errorMessage = $response['message'] ?? 'error desconocido del servicio externo';
                
                $orderService->updateOrderStatus(
                    $order->id,
                    'failed',
                    'error en el servicio externo: ' . $errorMessage
                );

                Log::error("orden procesada con error", [
                    'order_id' => $order->id,
                    'error' => $errorMessage
                ]);

                throw new Exception('servicio externo: ' . $errorMessage);
            }

        } catch (Exception $e) {
            Log::error("orden procesamiento job fallido", [
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if (isset($order)) {
                $orderService->updateOrderStatus(
                    $order->id,
                    'failed',
                    'Job processing error: ' . $e->getMessage()
                );
            }

            throw $e;
        }
    }

    /**
     * gestionar un fallo en el job.
     */
    public function failed(Exception $exception): void
    {
        Log::error("orden procesamiento job fallido permanentemente", [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage()
        ]);

        try {
            $order = Order::find($this->orderId);
            if ($order) {
                $order->update(['status' => 'failed']);
                $order->statusHistory()->create([
                    'status' => 'failed',
                    'notes' => 'Job fallido ' . $this->tries . ' attempts: ' . $exception->getMessage()
                ]);
            }
        } catch (Exception $e) {
            Log::error("fallo al actualizar el estado del pedido después del fallo del job", [
                'order_id' => $this->orderId,
                'error' => $e->getMessage()
            ]);
        }
    }
}
