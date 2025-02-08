<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StockController extends Controller
{
    public function index(Request $request)
    {
        $query = Stock::query();
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
            'tipo_producto' => 'required|in:l,m,c,u'
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
                'tipo_producto' => $validated['tipo_producto']
            ]);

            return response()->json($stock, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $stock = Stock::findOrFail($id);
        return response()->json($stock);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'producto' => 'required|string|max:255',
            'precio' => 'required|numeric|min:0',
            'tipo_producto' => 'required|in:l,m,c,u',
            'imagen' => 'nullable|file|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        try {
            $stock = Stock::findOrFail($id);
            $updateData = [
                'producto' => $validated['producto'],
                'precio' => $validated['precio'],
                'tipo_producto' => $validated['tipo_producto']
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
}
