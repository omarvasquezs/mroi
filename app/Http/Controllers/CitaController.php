<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\TipoCita;
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
            'observaciones' => 'nullable|string',
            'id_tipo_cita' => 'required|exists:tipos_citas,id'
        ]);

        // Combine date and time
        $fechaHora = date('Y-m-d H:i:s', strtotime($validated['fecha'] . ' ' . $validated['hora']));

        $cita = Cita::create([
            'num_historia' => $validated['num_historia'],
            'id_medico' => $validated['id_medico'],
            'fecha' => $fechaHora,
            'observaciones' => $validated['observaciones'],
            'id_tipo_cita' => $validated['id_tipo_cita']
        ]);

        return response()->json($cita, 201);
    }

    public function getCitasByMedicoAndFecha(Request $request)
    {
        $medicoId = $request->query('medico');
        $fecha = $request->query('fecha');

        if (!$medicoId || !$fecha) {
            return response()->json([], 400);
        }

        $citas = Cita::where('id_medico', $medicoId)
            ->whereDate('fecha', $fecha)
            ->with(['paciente' => function($query) {
                $query->select('num_historia', 'nombres', 'ap_paterno', 'ap_materno');
            }])
            ->get([
                'id',
                'num_historia',
                'id_medico',
                'fecha',
                'observaciones'
            ]);

        // Transform the data to ensure we have the time in the right format
        $citas = $citas->map(function ($cita) {
            return [
                'id' => $cita->id,
                'num_historia' => $cita->num_historia,
                'fecha' => $cita->fecha,
                'hora' => date('H:i:s', strtotime($cita->fecha)),
                'observaciones' => $cita->observaciones,
                'paciente' => $cita->paciente
            ];
        });

        return response()->json($citas);
    }

    public function getTiposCitas()
    {
        return response()->json(TipoCita::all());
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
            ->with(['paciente', 'tipoCita']) // Include tipoCita relation
            ->first();

        return response()->json($cita);
    }
}
