<?php

namespace App\Services;

use App\DTOs\OrderDTO;
use App\Models\Order;
use App\Repositories\OrderRepository;
use App\Jobs\ProcessOrderJob;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class OrderService
{
    public function __construct(
        private OrderRepository $orderRepository,
        private ExternalApiService $externalApiService
    ) {}

    /**
     * importar órdenes desde un array de OrderDTO
     */
    public function importOrders(array $orderDTOs): array
    {
        $imported = [];
        $errors = [];
        
        DB::beginTransaction();
        
        try {
            foreach ($orderDTOs as $orderDTO) {
                try {
                    if ($this->orderRepository->existsByOrderNumber($orderDTO->orderNumber)) {
                        $errors[] = "Order {$orderDTO->orderNumber} already exists";
                        continue;
                    }
                    
                    $orderData = [
                        'order_number' => $orderDTO->orderNumber,
                        'customer' => $orderDTO->customer,
                        'product' => $orderDTO->product,
                        'quantity' => $orderDTO->quantity,
                        'status' => Order::STATUS_PENDING
                    ];
                    
                    $order = $this->orderRepository->create($orderData);
                    $imported[] = $order;
                    
                    ProcessOrderJob::dispatch($order->id);
                    
                } catch (\Exception $e) {
                    $errors[] = "Error importando la orden {$orderDTO->orderNumber}: " . $e->getMessage();
                }
            }
            
            DB::commit();
            
            $this->invalidateOrdersCache();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        
        return [
            'imported' => $imported,
            'errors' => $errors,
            'total' => count($orderDTOs),
            'success' => count($imported),
            'failed' => count($errors)
        ];
    }

    /**
     * Get todos las órdenes con caching
     */
    public function getAllOrders(): Collection
    {
        return Cache::remember('orders:all', 300, function () {
            return $this->orderRepository->all();
        });
    }

    /**
     * Get orden por ID con caching
     */
    public function getOrderById(int $id): ?Order
    {
        return Cache::remember("orders:{$id}", 300, function () use ($id) {
            return $this->orderRepository->findById($id);
        });
    }

    /**
     * Get estado de orden 
     */
    public function getOrderStatus(int $id): array
    {
        $order = $this->getOrderById($id);
        
        if (!$order) {
            throw new \Exception("Orden no encontrada");
        }
        
        $statusHistory = $order->statusHistory()
            ->orderBy('created_at', 'desc')
            ->get();
        
        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'current_status' => $order->status,
            'customer' => $order->customer,
            'product' => $order->product,
            'quantity' => $order->quantity,
            'history' => $statusHistory->map(fn($h) => [
                'status' => $h->status,
                'notes' => $h->notes,
                'created_at' => $h->created_at->toIso8601String()
            ])
        ];
    }

    /**
     * Update orden estado
     */
    public function updateOrderStatus(int $id, string $status, ?string $notes = null): Order
    {
        $order = $this->orderRepository->findById($id);
        
        if (!$order) {
            throw new \Exception("Orden no encontrada");
        }
        
        $order = $this->orderRepository->updateStatus($order, $status, $notes);

        $this->invalidateOrderCache($id);
        
        return $order;
    }

    /**
     * Process orden enviándola al servicio externo
     */
    public function processOrder(int $orderId): bool
    {
        $order = $this->orderRepository->findById($orderId);
        
        if (!$order) {
            throw new \Exception("Orden no encontrada");
        }
        
        try {
            $this->updateOrderStatus($orderId, Order::STATUS_PROCESSING, 'enviando al servicio externo');
            
            $response = $this->externalApiService->sendOrder($order);
            
            if ($response['success']) {
                $this->updateOrderStatus(
                    $orderId, 
                    Order::STATUS_COMPLETED, 
                    'Procesada exitosamente por el servicio externo'
                );
                return true;
            } else {
                $this->updateOrderStatus(
                    $orderId, 
                    Order::STATUS_FAILED, 
                    'error: ' . ($response['message'] ?? 'Unknown error')
                );
                return false;
            }
            
        } catch (\Exception $e) {
            $this->updateOrderStatus(
                $orderId, 
                Order::STATUS_FAILED, 
                'Exception: ' . $e->getMessage()
            );
            throw $e;
        }
    }

    /**
     * invalidoar caché de una orden
     */
    private function invalidateOrderCache(int $id): void
    {
        Cache::forget("orders:{$id}");
        Cache::forget('orders:all');
    }

    /**
     * Invalidoar caché de todas las órdenes
     */
    private function invalidateOrdersCache(): void
    {
        Cache::forget('orders:all');
    }
}
