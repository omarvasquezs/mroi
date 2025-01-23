<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;

class StockController extends Controller
{
    public function index()
    {
        return response()->json(Stock::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'producto' => 'required|string|max:255',
            'imagen' => 'required|string|max:255',
            'precio' => 'required|numeric'
        ]);

        $stock = Stock::create($validated);

        return response()->json($stock, 201);
    }

    public function show($id)
    {
        $stock = Stock::findOrFail($id);
        return response()->json($stock);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'producto' => 'sometimes|required|string|max:255',
            'imagen' => 'sometimes|required|string|max:255',
            'precio' => 'sometimes|required|numeric'
        ]);

        $stock = Stock::findOrFail($id);
        $stock->update($validated);

        return response()->json($stock);
    }

    public function destroy($id)
    {
        $stock = Stock::findOrFail($id);
        $stock->delete();

        return response()->json(null, 204);
    }
}
