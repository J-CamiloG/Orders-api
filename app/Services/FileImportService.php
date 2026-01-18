<?php

namespace App\Services;

use App\DTOs\OrderDTO;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class FileImportService
{
    /**
     * Procesar el archivo cargado y devolver el array de OrderDTO.
     */
    public function processFile(UploadedFile $file): array
    {
        $extension = $file->getClientOriginalExtension();
        
        return match($extension) {
            'csv' => $this->processCsv($file),
            'json' => $this->processJson($file),
            default => throw new InvalidArgumentException("Formato de archivo no soportado: {$extension}")
        };
    }

    /**
     * Procesando archivo CSV
     */
    private function processCsv(UploadedFile $file): array
    {
        $orders = [];
        $handle = fopen($file->getRealPath(), 'r');
        
        $header = fgetcsv($handle);
        
        $lineNumber = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $lineNumber++;
            
            try {
                $orders[] = $this->createOrderDTO([
                    'order_number' => $row[0] ?? null,
                    'customer' => $row[1] ?? null,
                    'product' => $row[2] ?? null,
                    'quantity' => $row[3] ?? null,
                ], $lineNumber);
            } catch (\Exception $e) {
                \Log::warning("error{$lineNumber}: " . $e->getMessage());
            }
        }
        
        fclose($handle);
        
        return $orders;
    }

    /**
     * Procesando archivo JSON
     */
    private function processJson(UploadedFile $file): array
    {
        $orders = [];
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('Invalid JSON format');
        }

        $ordersData = isset($data['orders']) ? $data['orders'] : [$data];
        
        foreach ($ordersData as $index => $orderData) {
            try {
                $orders[] = $this->createOrderDTO($orderData, $index + 1);
            } catch (\Exception $e) {
                \Log::warning("Error processing order at index {$index}: " . $e->getMessage());
            }
        }
        
        return $orders;
    }

    /**
     * creando OrderDTO desde array y validando los datos
     */
    private function createOrderDTO(array $data, int $lineNumber): OrderDTO
    {
        $validator = Validator::make($data, [
            'order_number' => 'required|string|max:50',
            'customer' => 'required|string|max:255',
            'product' => 'required|string|max:255',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new InvalidArgumentException(
                "validacion {$lineNumber}: " . 
                implode(', ', $validator->errors()->all())
            );
        }

        return OrderDTO::fromArray($data);
    }

    /**
     * Validación básica del archivo cargado
     */
    public function validateFile(UploadedFile $file): bool
    {
        $extension = $file->getClientOriginalExtension();
        $allowedExtensions = ['csv', 'json'];
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new InvalidArgumentException(
                "Formato de archivo no soportado: {$extension}"
            );
        }
        
        //  (max 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            throw new InvalidArgumentException('archivo demasiado grande. Tamaño máximo es 10MB');
        }
        
        return true;
    }
}
