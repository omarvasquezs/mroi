<?php

namespace App\Http\Controllers;

use App\Models\MetodoPago;
use Illuminate\Http\Request;

class MetodoPagoController extends Controller
{
    public function index(Request $request)
    {
        $query = MetodoPago::query();

        if ($request->has('id') && $request->id) {
            $query->where('id', 'like', '%' . $request->id . '%');
        }

        if ($request->has('nombre') && $request->nombre) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }

        return $query->paginate(10);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'activo' => 'boolean'
        ]);

        $metodoPago = MetodoPago::create($request->all());
        return response()->json($metodoPago, 201);
    }

    public function show(MetodoPago $metodoPago)
    {
        return response()->json($metodoPago);
    }

    public function update(Request $request, MetodoPago $metodoPago)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'activo' => 'boolean'
        ]);

        $metodoPago->update($request->all());
        return response()->json($metodoPago);
    }

    public function destroy(MetodoPago $metodoPago)
    {
        $metodoPago->delete();
        return response()->json(null, 204);
    }
}
