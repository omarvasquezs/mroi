<?php

namespace App\Http\Controllers;

use App\Models\TipoCita;
use Illuminate\Http\Request;

class TipoCitaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = TipoCita::query();

            if ($request->has('tipo_cita') && $request->tipo_cita) {
                $query->where('tipo_cita', 'like', '%' . $request->tipo_cita . '%');
            }

            if ($request->has('precio') && $request->precio) {
                $query->where('precio', 'like', '%' . $request->precio . '%');
            }

            $tiposCitas = $query->paginate(10);
            return response()->json($tiposCitas);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error fetching tipos_citas'], 500);
        }
    }

    public function store(Request $request)
    {
        $tipoCita = TipoCita::create($request->all());
        return response()->json($tipoCita, 201);
    }

    public function show($id)
    {
        $tipoCita = TipoCita::findOrFail($id);
        return response()->json($tipoCita);
    }

    public function update(Request $request, $id)
    {
        $tipoCita = TipoCita::findOrFail($id);
        $tipoCita->update($request->all());
        return response()->json($tipoCita);
    }

    public function destroy($id)
    {
        TipoCita::destroy($id);
        return response()->json(null, 204);
    }
}
