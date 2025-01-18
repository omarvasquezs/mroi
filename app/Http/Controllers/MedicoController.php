<?php

namespace App\Http\Controllers;

use App\Models\Medico;
use Illuminate\Http\Request;

class MedicoController extends Controller
{
    public function index(Request $request)
    {
        $query = Medico::query();

        if ($request->has('dni') && $request->dni) {
            $query->where('dni', 'like', '%' . $request->dni . '%');
        }

        if ($request->has('nombres') && $request->nombres) {
            $query->where('nombres', 'like', '%' . $request->nombres . '%');
        }

        if ($request->has('cmp') && $request->cmp) {
            $query->where('cmp', 'like', '%' . $request->cmp . '%');
        }

        if ($request->has('ap_paterno') && $request->ap_paterno) {
            $query->where('ap_paterno', 'like', '%' . $request->ap_paterno . '%');
        }

        if ($request->has('ap_materno') && $request->ap_materno) {
            $query->where('ap_materno', 'like', '%' . $request->ap_materno . '%');
        }

        $medicos = $query->paginate(10);
        return response()->json($medicos);
    }

    public function store(Request $request)
    {
        $medico = Medico::create($request->all());
        return response()->json($medico, 201);
    }

    public function show($id)
    {
        $medico = Medico::findOrFail($id);
        return response()->json($medico);
    }

    public function update(Request $request, $id)
    {
        $medico = Medico::findOrFail($id);
        $medico->update($request->all());
        return response()->json($medico);
    }

    public function destroy($id)
    {
        Medico::destroy($id);
        return response()->json(null, 204);
    }

    public function checkDni(Request $request)
    {
        $exists = Medico::where('dni', $request->dni)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function checkCmp(Request $request)
    {
        $exists = Medico::where('cmp', $request->cmp)->exists();
        return response()->json(['exists' => $exists]);
    }
}
