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
        $query = Stock::with(['proveedor', 'material']);
        $perPage = 10; // Default for CatalogoLentes

        // Text search for producto
        if ($request->filled('producto')) {
            $query->where('producto', 'like', '%' . $request->producto . '%');
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

        // Filter by proveedor
        if ($request->filled('proveedor')) {
            $query->whereHas('proveedor', function ($q) use ($request) {
                $q->where('razon_social', 'like', '%' . $request->proveedor . '%');
            });
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
            'producto' => 'required|string|max:255',
            'imagen' => 'required|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'precio' => 'required|numeric|min:0',
            'tipo_producto' => 'required|in:l,m,c,u',
            'id_proveedor' => 'required|exists:proveedores,id',
            'codigo' => 'nullable|string|max:255',
            'genero' => 'nullable|in:H,M,N,U',
            'id_material' => 'nullable|exists:materiales,id',
            'fecha_compra' => 'nullable|date'
        ]);

        try {
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
                'producto' => $validated['producto'],
                'imagen' => $validated['imagen'],
                'precio' => $validated['precio'],
                'tipo_producto' => $validated['tipo_producto'],
                'id_proveedor' => $validated['id_proveedor'],
                'codigo' => $validated['codigo'] ?? null,
                'genero' => $validated['genero'] ?? null,
                'id_material' => $validated['id_material'] ?? null,
                'fecha_compra' => $validated['fecha_compra'] ?? null
            ]);

            return response()->json($stock, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $stock = Stock::with(['proveedor', 'material'])->findOrFail($id);
        return response()->json($stock);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'producto' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'tipo_producto' => 'required|in:l,m,c,u',
            'id_proveedor' => 'required|exists:proveedores,id',
            'imagen' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'codigo' => 'nullable|string|max:255',
            'genero' => 'nullable|in:H,M,N,U',
            'id_material' => 'nullable|exists:materiales,id',
            'fecha_compra' => 'nullable|date'
        ]);

        try {
            $stock = Stock::findOrFail($id);
            $updateData = [
                'producto' => $validated['producto'],
                'precio' => $validated['precio'],
                'tipo_producto' => $validated['tipo_producto'],
                'id_proveedor' => $validated['id_proveedor'],
                'codigo' => $validated['codigo'] ?? $stock->codigo,
                'genero' => $validated['genero'] ?? $stock->genero,
                'id_material' => $validated['id_material'] ?? $stock->id_material,
                'fecha_compra' => $validated['fecha_compra'] ?? $stock->fecha_compra
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
                    'producto' => $stock->producto,
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
