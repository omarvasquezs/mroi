<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Paciente;

class PacientesController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->all();
        $data['paciente_privado'] = $request->has('paciente_privado') ? 1 : 0;

        $paciente = Paciente::create($data);

        return response()->json(['message' => 'Paciente creado.', 'data' => $paciente]);
    }
}
