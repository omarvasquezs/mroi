<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use Illuminate\Http\Request;

class CitaController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'num_historia' => 'required|exists:pacientes,num_historia',
            'id_medico' => 'required|exists:medicos,id',
            'fecha' => 'required|date',
            'hora' => 'required',
            'observaciones' => 'nullable|string'
        ]);

        // Combine date and time
        $fechaHora = date('Y-m-d H:i:s', strtotime($validated['fecha'] . ' ' . $validated['hora']));

        $cita = Cita::create([
            'num_historia' => $validated['num_historia'],
            'id_medico' => $validated['id_medico'],
            'fecha' => $fechaHora,
            'observaciones' => $validated['observaciones']
        ]);

        return response()->json($cita, 201);
    }

    public function getCitasByMedicoAndFecha(Request $request)
    {
        $medicoId = $request->query('medico');
        $fecha = $request->query('fecha');

        if (!$medicoId || !$fecha) {
            return response()->json([], 400); // Bad request if parameters are missing
        }

        $citas = Cita::where('id_medico', $medicoId)
            ->whereDate('fecha', $fecha)
            ->with('paciente') // Ensure paciente information is included
            ->get();

        return response()->json($citas);
    }

    public function checkCita(Request $request)
    {
        $medicoId = $request->query('medico');
        $fecha = $request->query('fecha');
        $hora = $request->query('hora');

        if (!$medicoId || !$fecha || !$hora) {
            return response()->json(null, 400); // Bad request if parameters are missing
        }

        $cita = Cita::where('id_medico', $medicoId)
            ->whereDate('fecha', $fecha)
            ->whereTime('fecha', $hora)
            ->with('paciente') // Ensure paciente information is included
            ->first();

        return response()->json($cita);
    }
}
