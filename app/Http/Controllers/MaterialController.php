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
