<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductoComprobante;
use App\Models\ProductoComprobanteItem;
use App\Models\Comprobante;

class ProductoComprobanteController extends Controller
{
    public function index()
    {
        $productoComprobantes = ProductoComprobante::whereNull('comprobante_id')->get();
        return response()->json(['data' => $productoComprobantes]);
    }

    public function show($id)
    {
        $productoComprobante = ProductoComprobante::findOrFail($id);
        $items = ProductoComprobanteItem::where('producto_comprobante_id', $id)->with('stock')->get();
        return response()->json(['productoComprobante' => $productoComprobante, 'items' => $items]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombres' => 'required|string',
            'telefono' => 'required|string',
            'correo' => 'required|string|email',
            'monto_total' => 'required|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.stock_id' => 'required|integer|exists:stock,id',
            'items.*.cantidad' => 'required|integer|min:1',
            'items.*.precio' => 'required|numeric|min:0'
        ]);

        try {
            $productoComprobante = ProductoComprobante::create([
                'nombres' => $request->nombres,
                'telefono' => $request->telefono,
                'correo' => $request->correo,
                'monto_total' => $request->monto_total,
                'comprobante_id' => null
            ]);

            foreach ($request->items as $item) {
                ProductoComprobanteItem::create([
                    'producto_comprobante_id' => $productoComprobante->id,
                    'stock_id' => $item['stock_id'],
                    'cantidad' => $item['cantidad'],
                    'precio' => $item['precio']
                ]);
            }

            return response()->json(['producto_comprobante' => $productoComprobante, 'message' => 'Producto comprobante and items created successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error creating producto comprobante/items: ' . $e->getMessage()], 500);
        }
    }
}
