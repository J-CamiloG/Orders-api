<?php

namespace App\DTOs;

class OrderDTO
{
    public function __construct(
        public readonly string $orderNumber,
        public readonly string $customer,
        public readonly string $product,
        public readonly int $quantity,
        public readonly string $status = 'pending'
    ) {}

    /**
     * Crear DTO a partir de un array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            orderNumber: $data['order_number'] ?? $data['orderNumber'] ?? '',
            customer: $data['customer'] ?? '',
            product: $data['product'] ?? '',
            quantity: (int) ($data['quantity'] ?? 0),
            status: $data['status'] ?? 'pending'
        );
    }

    /**
     * Convertir DTO a array
     */
    public function toArray(): array
    {
        return [
            'order_number' => $this->orderNumber,
            'customer' => $this->customer,
            'product' => $this->product,
            'quantity' => $this->quantity,
            'status' => $this->status,
        ];
    }

    /**
     * Validar datos del DTO
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->orderNumber)) {
            $errors[] = 'numero de orden es obligatorio';
        }

        if (empty($this->customer)) {
            $errors[] = 'Cliente es obligatorio';
        }

        if (empty($this->product)) {
            $errors[] = 'Producto es obligatorio';
        }

        if ($this->quantity <= 0) {
            $errors[] = 'La cantidad debe ser mayor que 0';
        }

        return $errors;
    }

    /**
     * Comprueba si DTO es válido.
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
