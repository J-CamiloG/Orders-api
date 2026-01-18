<?php

namespace App\Http\Controllers;

use App\Services\FileImportService;
use App\Services\OrderService;
use App\Repositories\OrderRepository;
use App\Http\Requests\ImportOrdersRequest;
use App\Jobs\ProcessOrderJob;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Orders", description="API Endpoints para la gestión de Ordenes")
 */
class OrderController extends Controller
{
    public function __construct(
        private FileImportService $fileImportService,
        private OrderService $orderService,
        private OrderRepository $orderRepository
    ) {}

    /**
     * @OA\Post(
     *     path="/api/orders/import",
     *     summary="Importar órdenes desde un archivo CSV o JSON",
     *     tags={"Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="file", type="string", format="binary", description="CSV or JSON file"),
     *                 @OA\Property(property="format", type="string", enum={"csv", "json"}, example="csv")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="orden importada exitosamente"),
     *     @OA\Response(response=422, description="validación de error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt,json|max:10240',
            'format' => 'required|in:csv,json'
        ]);

        try {
            $file = $request->file('file');
            $format = $request->input('format');
            
            $this->fileImportService->validateFile($file);
            $orderDTOs = $this->fileImportService->processFile($file);
            
            if (empty($orderDTOs)) {
                return response()->json([
                    'success' => false,
                    'message' => 'no están disponibles órdenes para importar'
                ], 422);
            }

            $result = $this->orderService->importOrders($orderDTOs);

            Log::info('Orders imported', [
                'total' => $result['total'],
                'success' => $result['success'],
                'failed' => $result['failed'],
                'file' => $file->getClientOriginalName()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Órdenes importadas y procesamiento iniciado',
                'data' => [
                    'total_orders' => $result['total'],
                    'imported' => $result['success'],
                    'failed' => $result['failed'],
                    'errors' => $result['errors'],
                    'status' => 'processing'
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error importing orders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error importing orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/orders/import-json",
     *     summary="Import orders directly from JSON payload (Swagger friendly)",
     *     tags={"Orders"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="orders",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="order_number", type="string", example="ORD-001"),
     *                     @OA\Property(property="customer", type="string", example="Juan Pérez"),
     *                     @OA\Property(property="product", type="string", example="Laptop Dell"),
     *                     @OA\Property(property="quantity", type="integer", example=2)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="orden importada exitosamente"),
     *     @OA\Response(response=422, description="validación de error"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function importJson(Request $request): JsonResponse
    {
        $request->validate([
            'orders' => 'required|array|min:1',
            'orders.*.order_number' => 'required|string',
            'orders.*.customer' => 'required|string',
            'orders.*.product' => 'required|string',
            'orders.*.quantity' => 'required|integer|min:1'
        ]);

        try {
            $ordersData = $request->input('orders');
            $orderDTOs = [];

            foreach ($ordersData as $orderData) {
                $orderDTOs[] = new \App\DTOs\OrderDTO(
                    orderNumber: $orderData['order_number'],
                    customer: $orderData['customer'],
                    product: $orderData['product'],
                    quantity: $orderData['quantity']
                );
            }

            $result = $this->orderService->importOrders($orderDTOs);

            Log::info('Orders imported via JSON', [
                'total' => $result['total'],
                'success' => $result['success'],
                'failed' => $result['failed']
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Ordenes importadas y procesamiento iniciado',
                'data' => [
                    'total_orders' => $result['total'],
                    'imported' => $result['success'],
                    'failed' => $result['failed'],
                    'errors' => $result['errors'],
                    'status' => 'processing'
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error importando ordenes via JSON', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error importando ordenes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/orders",
     *     summary="Enumerar todos los pedidos con paginación y filtrado.",
     *     tags={"Orders"},
     *     @OA\Parameter(name="per_page", in="query", description="Items per page", required=false, @OA\Schema(type="integer", example=15)),
     *     @OA\Parameter(name="status", in="query", description="Filter by status", required=false, @OA\Schema(type="string", enum={"pending", "processing", "completed", "failed"})),
     *     @OA\Response(response=200, description="operacion exitosa"),
     *     @OA\Response(response=500, description="Server error")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->get('per_page', 15);
            $status = $request->get('status');

            if ($status) {
                // Filter by status
                $orders = $this->orderRepository->getByStatus($status);
                $page = (int) $request->get('page', 1);
                $total = $orders->count();
                $items = $orders->forPage($page, $perPage)->values();
                
                return response()->json([
                    'success' => true,
                    'data' => $items,
                    'meta' => [
                        'current_page' => $page,
                        'per_page' => $perPage,
                        'total' => $total,
                        'last_page' => (int) ceil($total / $perPage)
                    ]
                ]);
            }

            $paginated = $this->orderRepository->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $paginated->items(),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                    'last_page' => $paginated->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error listing orders', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);

            return response()->json([
                'success' => false,
                'message' => 'Error retrieving orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{id}",
     *     summary="Obtener un pedido específico por ID",
     *     tags={"Orders"},
     *     @OA\Parameter(name="id", in="path", description="ID del pedido", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Operación exitosa"),
     *     @OA\Response(response=404, description="Pedido no encontrado"),
     *     @OA\Response(response=500, description="Error del servidor")
     * )
     */
    public function show(int $id): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderById($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $order
            ]);

        } catch (\Exception $e) {
            Log::error('Error al recuperar la orden', [
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al recuperar la orden'
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/orders/{id}/status",
     *     summary="Obtener el estado y historial de un pedido",
     *     tags={"Orders"},
     *     @OA\Parameter(name="id", in="path", description="ID del pedido", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Operación exitosa"),
     *     @OA\Response(response=404, description="Pedido no encontrado"),
     *     @OA\Response(response=500, description="Error del servidor")
     * )
     */
    public function status(int $id): JsonResponse
    {
        try {
            $statusData = $this->orderService->getOrderStatus($id);

            if (!$statusData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $statusData
            ]);

        } catch (\Exception $e) {
            Log::error('Error al recuperar el estado de la orden ', [
                'order_id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
