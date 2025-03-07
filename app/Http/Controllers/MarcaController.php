<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarcaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $query = Marca::with('proveedores');
        
        // Apply filters
        if ($request->filled('marca')) {
            $query->where('marca', 'like', '%' . $request->marca . '%');
        }
        
        $perPage = $request->input('per_page', 10);
        
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'marca' => 'required|string|max:255|unique:marcas',
            'proveedores' => 'nullable|array',
            'proveedores.*' => 'exists:proveedores,id'
        ]);

        try {
            DB::beginTransaction();

            $marca = Marca::create([
                'marca' => $validated['marca']
            ]);

            if (!empty($validated['proveedores'])) {
                $marca->proveedores()->attach($validated['proveedores']);
            }

            DB::commit();

            return response()->json(
                Marca::with('proveedores')->find($marca->id),
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $marca = Marca::with('proveedores')->findOrFail($id);
        return response()->json($marca);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $marca = Marca::findOrFail($id);
        
        $validated = $request->validate([
            'marca' => 'required|string|max:255|unique:marcas,marca,' . $id,
            'proveedores' => 'nullable|array',
            'proveedores.*' => 'exists:proveedores,id'
        ]);

        try {
            DB::beginTransaction();

            $marca->update([
                'marca' => $validated['marca']
            ]);

            // Sync proveedores (remove existing relationships and create new ones)
            if (isset($validated['proveedores'])) {
                $marca->proveedores()->sync($validated['proveedores']);
            } else {
                // If no proveedores were provided, detach all
                $marca->proveedores()->detach();
            }

            DB::commit();

            return response()->json(
                Marca::with('proveedores')->find($marca->id)
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $marca = Marca::findOrFail($id);
            
            // The relationships in marca_proveedor will be automatically removed
            // if you've set up the foreign key constraints with onDelete('cascade')
            $marca->delete();
            
            return response()->json(null, 204);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
