<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use Illuminate\Http\Request;

class PacienteController extends Controller
{
    public function index(Request $request)
    {
        $query = Paciente::query();

        if ($request->has('num_historia') && $request->num_historia) {
            $query->where('num_historia', 'like', '%' . $request->num_historia . '%');
        }

        if ($request->has('nombres') && $request->nombres) {
            $query->where('nombres', 'like', '%' . $request->nombres . '%');
        }

        if ($request->has('apellidos') && $request->apellidos) {
            $query->where(function ($q) use ($request) {
                $q->where('ap_paterno', 'like', '%' . $request->apellidos . '%')
                  ->orWhere('ap_materno', 'like', '%' . $request->apellidos . '%');
            });
        }

        if ($request->has('doc_identidad') && $request->doc_identidad) {
            $query->where('doc_identidad', 'like', '%' . $request->doc_identidad . '%');
        }

        $pacientes = $query->paginate(10);
        return response()->json($pacientes);
    }

    public function store(Request $request)
    {
        $paciente = new Paciente($request->all());
        $paciente->num_historia = str_pad(Paciente::max('id') + 1, 7, '0', STR_PAD_LEFT);
        $paciente->save();
        return response()->json($paciente, 201);
    }

    public function show($id)
    {
        $paciente = Paciente::findOrFail($id);
        return response()->json($paciente);
    }

    public function update(Request $request, $id)
    {
        $paciente = Paciente::findOrFail($id);
        $paciente->update($request->all());
        return response()->json($paciente);
    }

    public function destroy($id)
    {
        Paciente::destroy($id);
        return response()->json(null, 204);
    }

    public function checkDocIdentidad(Request $request)
    {
        $exists = Paciente::where('doc_identidad', $request->doc_identidad)->exists();
        return response()->json(['exists' => $exists]);
    }
}
