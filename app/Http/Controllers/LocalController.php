<?php

namespace App\Http\Controllers;

use App\Models\Local;
use Illuminate\Http\Request;

class LocalController extends Controller
{
    public function index(Request $request)
    {
        $query = Local::query();
        if ($request->has('nombre')) {
            $query->where('nombre', 'like', '%' . $request->nombre . '%');
        }
        if ($request->has('direccion')) {
            $query->where('direccion', 'like', '%' . $request->direccion . '%');
        }
        $locales = $query->orderBy('id', 'desc')->paginate(10);
        return response()->json([
            'data' => $locales->items(),
            'total' => $locales->total(),
            'per_page' => $locales->perPage(),
            'current_page' => $locales->currentPage(),
            'last_page' => $locales->lastPage(),
            'next_page_url' => $locales->nextPageUrl(),
            'prev_page_url' => $locales->previousPageUrl(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255|unique:locales,nombre',
            'direccion' => 'nullable|string|max:255',
        ]);
        $local = Local::create($request->only(['nombre', 'direccion']));
        return response()->json($local, 201);
    }

    public function show($id)
    {
        $local = Local::findOrFail($id);
        return response()->json($local);
    }

    public function update(Request $request, $id)
    {
        $local = Local::findOrFail($id);
        $request->validate([
            'nombre' => 'required|string|max:255|unique:locales,nombre,' . $id,
            'direccion' => 'nullable|string|max:255',
        ]);
        $local->update($request->only(['nombre', 'direccion']));
        return response()->json($local);
    }

    public function destroy($id)
    {
        $local = Local::findOrFail($id);
        $local->delete();
        return response()->json(['message' => 'Local eliminado']);
    }
}
