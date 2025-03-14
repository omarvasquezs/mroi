<?php

namespace App\Http\Controllers;

use App\Models\Material;
use Illuminate\Http\Request;

class MaterialController extends Controller
{
    public function index(Request $request)
    {
        $query = Material::query();

        if ($request->has('material') && $request->material) {
            $query->where('material', 'like', '%' . $request->material . '%');
        }

        // If 'all' parameter is true, return all records without pagination
        if ($request->has('all') && $request->all == true) {
            $materiales = $query->orderBy('material', 'asc')->get();
            return response()->json([
                'data' => $materiales
            ]);
        }
        
        $perPage = $request->input('per_page', 10);
        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $request->validate([
            'material' => 'required|string|max:255',
        ]);

        return Material::create($request->all());
    }

    public function show(Material $material)
    {
        return $material;
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'material' => 'required|string|max:255',
        ]);

        $material = Material::findOrFail($id);

        $material->update($request->all());

        return response()->json($material);
    }

    public function destroy($id)
    {
        Material::destroy($id);
        return response()->json(null, 204);
    }
}
