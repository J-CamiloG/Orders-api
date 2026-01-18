<?php

namespace App\Repositories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class OrderRepository
{
    /**
     * Get Todos las Ordenes
     */
    public function all(): Collection
    {
        return Order::with('statusHistory')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get Ordenes paginadas
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Order::with('statusHistory')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * buscar orden por ID
     */
    public function findById(int $id): ?Order
    {
        return Order::with('statusHistory')->find($id);
    }

    /**
     * buscar orden por número de orden
     */
    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return Order::with('statusHistory')
            ->where('order_number', $orderNumber)
            ->first();
    }

    /**
     * Verificar si existe una orden por número de orden
     */
    public function existsByOrderNumber(string $orderNumber): bool
    {
        return Order::where('order_number', $orderNumber)->exists();
    }

    /**
     * Create nueva orden
     */
    public function create(array $data): Order
    {
        $order = Order::create($data);
        
        $order->statusHistory()->create([
            'status' => $data['status'] ?? Order::STATUS_PENDING,
            'notes' => 'Order created',
        ]);

        return $order->fresh('statusHistory');
    }

    /**
     * Create muchas ordenes
     */
    public function createMany(array $ordersData): Collection
    {
        $orders = collect();

        foreach ($ordersData as $orderData) {
            $orders->push($this->create($orderData));
        }

        return $orders;
    }

    /**
     * Update ordenes
     */
    public function update(Order $order, array $data): Order
    {
        $order->update($data);
        return $order->fresh('statusHistory');
    }

    /**
     * Update estado de ordenes
     */
    public function updateStatus(Order $order, string $status, ?string $notes = null): Order
    {
        $order->updateStatus($status, $notes);
        return $order->fresh('statusHistory');
    }

    /**
     * Delete ordenes
     */
    public function delete(Order $order): bool
    {
        return $order->delete();
    }

    /**
     * Get ordenes por estado
     */
    public function getByStatus(string $status): Collection
    {
        return Order::with('statusHistory')
            ->where('status', $status)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get ordenes pendientes
     */
    public function getPendingOrders(): Collection
    {
        return $this->getByStatus(Order::STATUS_PENDING);
    }

    /**
     * Count ordenes por estado
     */
    public function countByStatus(string $status): int
    {
        return Order::where('status', $status)->count();
    }
}
