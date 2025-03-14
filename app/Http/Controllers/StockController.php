<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::with(['marca', 'material']); // Changed from proveedor to marca
        $perPage = 10; // Default for CatalogoLentes

        // Text search for descripcion (previously producto)
        if ($request->filled('descripcion')) {
            $query->where('descripcion', 'like', '%' . $request->descripcion . '%');
        }
        
        // Price filters for CatalogoLentes
        if ($request->filled('precio_min')) {
            $query->where('precio', '>=', floatval($request->precio_min));
        }
        
        if ($request->filled('precio_max')) {
            $query->where('precio', '<=', floatval($request->precio_max));
        }

        // Single precio filter for ControlStockCrud
        if ($request->filled('precio') && !$request->filled('precio_min') && !$request->filled('precio_max')) {
            $query->where('precio', 'like', $request->precio . '%');
            $perPage = 10; // Set to 10 for ControlStockCrud
        }

        // Filter by tipo_producto
        if ($request->filled('tipo_producto')) {
            $tipoProductos = explode(',', $request->tipo_producto);
            $query->whereIn('tipo_producto', $tipoProductos);
        }

        // Filter by marca instead of proveedor
        if ($request->filled('marca')) {
            $query->whereHas('marca', function ($q) use ($request) {
                $q->where('marca', 'like', '%' . $request->marca . '%');
            });
        }

        // Filter by codigo
        if ($request->filled('codigo')) {
            $query->where('codigo', 'like', '%' . $request->codigo . '%');
        }

        $result = $query->orderBy('created_at', 'desc')->paginate($perPage);
        return response()->json([
            'current_page' => $result->currentPage(),
            'data' => $result->items(),
            'first_page_url' => $result->url(1),
            'from' => $result->firstItem(),
            'last_page' => $result->lastPage(),
            'last_page_url' => $result->url($result->lastPage()),
            'next_page_url' => $result->nextPageUrl(),
            'per_page' => $result->perPage(),
            'prev_page_url' => $result->previousPageUrl(),
            'to' => $result->lastItem(),
            'total' => $result->total(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'descripcion' => 'nullable|string|max:255',
            'imagen' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'precio' => 'required|numeric|min:0',
            'tipo_producto' => 'required|in:l,m,c,u',
            'id_marca' => 'required|exists:marcas,id', // Changed from id_proveedor to id_marca
            'codigo' => 'nullable|string|max:255',
            'genero' => 'nullable|in:H,M,N,U',
            'id_material' => 'nullable|exists:materiales,id',
            'fecha_compra' => 'nullable|date',
            'num_stock' => 'required|integer|min:0'
        ]);

        try {
            $validated['imagen'] = null; // Default to null

            if ($request->hasFile('imagen')) {
                $file = $request->file('imagen');
                $filename = time() . '_' . $file->getClientOriginalName();
                
                // Ensure directory exists
                $path = public_path('images/stock');
                if (!file_exists($path)) {
                    mkdir($path, 0777, true);
                }
                
                $file->move($path, $filename);
                $validated['imagen'] = $filename;
            }

            $stock = Stock::create([
                'descripcion' => $validated['descripcion'] ?? null,
                'imagen' => $validated['imagen'],
                'precio' => $validated['precio'],
                'tipo_producto' => $validated['tipo_producto'],
                'id_marca' => $validated['id_marca'], // Changed from id_proveedor to id_marca
                'codigo' => $validated['codigo'] ?? null,
                'genero' => $validated['genero'] ?? null,
                'id_material' => $validated['id_material'] ?? null,
                'fecha_compra' => $validated['fecha_compra'] ?? null,
                'num_stock' => $validated['num_stock']
            ]);

            return response()->json($stock, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $stock = Stock::with(['marca', 'material'])->findOrFail($id); // Changed from proveedor to marca
        return response()->json($stock);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'descripcion' => 'nullable|string|max:255',
            'precio' => 'required|numeric|min:0',
            'tipo_producto' => 'required|in:l,m,c,u',
            'id_marca' => 'required|exists:marcas,id', // Changed from id_proveedor to id_marca
            'imagen' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'codigo' => 'nullable|string|max:255',
            'genero' => 'nullable|in:H,M,N,U',
            'id_material' => 'nullable|exists:materiales,id',
            'fecha_compra' => 'nullable|date',
            'num_stock' => 'required|integer|min:0'
        ]);

        try {
            $stock = Stock::findOrFail($id);
            $updateData = [
                'descripcion' => $validated['descripcion'] ?? $stock->descripcion,
                'precio' => $validated['precio'],
                'tipo_producto' => $validated['tipo_producto'],
                'id_marca' => $validated['id_marca'], // Changed from id_proveedor to id_marca
                'codigo' => $validated['codigo'] ?? $stock->codigo,
                'genero' => $validated['genero'] ?? $stock->genero,
                'id_material' => $validated['id_material'] ?? $stock->id_material,
                'fecha_compra' => $validated['fecha_compra'] ?? $stock->fecha_compra,
                'num_stock' => $validated['num_stock']
            ];
            
            if ($request->hasFile('imagen')) {
                // Delete old image
                if ($stock->imagen) {
                    $oldPath = public_path('images/stock/' . $stock->imagen);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }

                // Store new image
                $file = $request->file('imagen');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('images/stock'), $filename);
                $updateData['imagen'] = $filename;
            }

            $stock->update($updateData);
            return response()->json($stock);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al actualizar el producto: ' . $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        $stock = Stock::findOrFail($id);
        
        // Delete image file
        if ($stock->imagen) {
            $path = public_path('images/stock/' . $stock->imagen);
            if (file_exists($path)) {
                unlink($path);
            }
        }

        $stock->delete();
        return response()->json(null, 204);
    }

    public function updateStock(Request $request)
    {
        try {
            $updates = $request->input('updates');
            Log::info('Stock update request received:', ['updates' => $updates]);
            
            DB::beginTransaction();
            
            foreach ($updates as $update) {
                $stock = Stock::findOrFail($update['id']);
                $stock->num_stock = $update['num_stock'];
                $stock->save();
                
                Log::info('Stock updated:', [
                    'id' => $stock->id,
                    'descripcion' => $stock->descripcion, // Updated from producto to descripcion
                    'old_stock' => $stock->getOriginal('num_stock'),
                    'new_stock' => $stock->num_stock
                ]);
            }
            
            DB::commit();
            return response()->json(['message' => 'Stock actualizado correctamente']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error actualizando stock: ' . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar el stock: ' . $e->getMessage()], 500);
        }
    }
}
