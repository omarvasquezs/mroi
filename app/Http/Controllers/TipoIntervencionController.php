<?php

namespace App\Http\Controllers;

use App\Models\TipoIntervencion;
use Illuminate\Http\Request;

class TipoIntervencionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = TipoIntervencion::query();

            if ($request->has('tipo_intervencion') && $request->tipo_intervencion) {
                $query->where('tipo_intervencion', 'like', '%' . $request->tipo_intervencion . '%');
            }

            if ($request->has('precio') && $request->precio) {
                $query->where('precio', 'like', '%' . $request->precio . '%');
            }

            $tiposIntervenciones = $query->paginate(10);
            return response()->json($tiposIntervenciones);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching tipos_intervenciones'], 500);
        }
    }

    public function store(Request $request)
    {
        $tipoIntervencion = TipoIntervencion::create($request->all());
        return response()->json($tipoIntervencion, 201);
    }

    public function show($id)
    {
        $tipoIntervencion = TipoIntervencion::findOrFail($id);
        return response()->json($tipoIntervencion);
    }

    public function update(Request $request, $id)
    {
        $tipoIntervencion = TipoIntervencion::findOrFail($id);
        $tipoIntervencion->update($request->all());
        return response()->json($tipoIntervencion);
    }

    public function destroy($id)
    {
        TipoIntervencion::destroy($id);
        return response()->json(null, 204);
    }

    public function getAllTiposIntervenciones()
    {
        return response()->json(TipoIntervencion::all());
    }
}
