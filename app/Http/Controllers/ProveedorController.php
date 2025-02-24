<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProveedorController extends Controller
{
    public function index(Request $request)
    {
        $query = Proveedor::query();

        if ($request->has('razon_social')) {
            $query->where('razon_social', 'like', '%' . $request->razon_social . '%');
        }

        if ($request->has('ruc')) {
            $query->where('ruc', 'like', '%' . $request->ruc . '%');
        }

        if ($request->has('nombre_representante')) {
            $query->where('nombre_representante', 'like', '%' . $request->nombre_representante . '%');
        }

        $proveedores = $query->paginate(10);

        return response()->json($proveedores);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'razon_social' => 'required|string|max:255',
                'ruc' => 'required|digits:11|unique:proveedores',
                'direccion' => 'required|string',
                'telefono' => 'required|string',
                'correo_electronico' => 'required|string|email|max:255|unique:proveedores',
                'pagina_web' => 'nullable|string|max:255',
                'nombre_representante' => 'required|string|max:255',
            ]);

            $proveedor = Proveedor::create($request->all());
            return response()->json($proveedor, 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error creating proveedor: ' . $e->getMessage());
            return response()->json([
                'error' => 'Error creating proveedor',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $proveedor = Proveedor::findOrFail($id);

        return response()->json($proveedor);
    }

    public function update(Request $request, $id)
    {
        $proveedor = Proveedor::findOrFail($id);

        $request->validate([
            'razon_social' => 'required|string|max:255',
            'ruc' => 'required|digits:11|unique:proveedores,ruc,' . $proveedor->id,
            'direccion' => 'required|string',
            'telefono' => 'required|string',
            'correo_electronico' => 'required|string|email|max:255|unique:proveedores,correo_electronico,' . $proveedor->id,
            'pagina_web' => 'nullable|string|max:255',
            'nombre_representante' => 'required|string|max:255',
        ]);

        try {
            $proveedor->update($request->all());
            return response()->json($proveedor);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating proveedor: ' . $e->getMessage());
            return response()->json(['error' => 'Error updating proveedor'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $proveedor = Proveedor::findOrFail($id);
            $proveedor->delete();
            return response()->json(null, 204);
        } catch (\Exception $e) {
            Log::error('Error deleting proveedor: ' . $e->getMessage());
            return response()->json(['error' => 'Error deleting proveedor'], 500);
        }
    }

    public function checkRuc(Request $request)
    {
        $exists = Proveedor::where('ruc', $request->ruc)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function checkCorreo(Request $request)
    {
        $exists = Proveedor::where('correo_electronico', $request->correo_electronico)->exists();
        return response()->json(['exists' => $exists]);
    }
}
