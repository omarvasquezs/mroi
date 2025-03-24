<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Medico;
use App\Models\TipoCita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->with([
                'paciente' => function($query) {
                    $query->select('num_historia', 'nombres', 'ap_paterno', 'ap_materno');
                },
                'tipoCita' => function($query) {
                    $query->select('id', 'tipo_cita', 'precio');
                }
            ])
            ->get([
                'id',
                'num_historia',
                'id_medico',
                'fecha',
                'observaciones',
                'id_tipo_cita'
            ]);

        // Transform the data to ensure we have the time in the right format
        $citas = $citas->map(function ($cita) {
            return [
                'id' => $cita->id,
                'num_historia' => $cita->num_historia,
                'fecha' => $cita->fecha,
                'hora' => date('H:i:s', strtotime($cita->fecha)),
                'observaciones' => $cita->observaciones,
                'paciente' => $cita->paciente,
                'tipoCita' => $cita->tipoCita
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

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_medico' => 'required|exists:medicos,id',
            'fecha' => 'required|date',
            'hora' => 'required',
            'observaciones' => 'nullable|string'
        ]);

        $cita = Cita::findOrFail($id);

        // Combine date and time
        $fechaHora = date('Y-m-d H:i:s', strtotime($validated['fecha'] . ' ' . $validated['hora']));

        // Check if the new time slot is available
        $existingCita = Cita::where('id_medico', $validated['id_medico'])
            ->whereDate('fecha', $validated['fecha'])
            ->whereTime('fecha', $validated['hora'])
            ->where('id', '!=', $id)
            ->first();

        if ($existingCita) {
            return response()->json(['message' => 'Este horario ya está ocupado'], 422);
        }

        $cita->update([
            'id_medico' => $validated['id_medico'],
            'fecha' => $fechaHora,
            'observaciones' => $validated['observaciones']
        ]);

        $cita->load(['paciente', 'medico', 'tipoCita']);

        return response()->json($cita);
    }

    public function checkAvailability(Request $request)
    {
        $medicoId = $request->query('medico');
        $fecha = $request->query('fecha');

        if (!$medicoId || !$fecha) {
            return response()->json(['message' => 'Medico ID and date are required'], 400);
        }

        // Get all appointments for the selected doctor and date
        $citas = Cita::where('id_medico', $medicoId)
            ->whereDate('fecha', $fecha)
            ->get(['id', 'fecha']);

        // Convert to a list of occupied time slots (hours)
        $occupiedSlots = $citas->map(function ($cita) {
            return date('H:i', strtotime($cita->fecha));
        });

        // Get working hours (8:00 to 19:00 with 30 minute intervals)
        $workingHours = [];
        $startHour = 8;
        $endHour = 19;

        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $formattedHour = str_pad($hour, 2, '0', STR_PAD_LEFT);
            $workingHours[] = [
                'time' => "$formattedHour:00",
                'available' => !$occupiedSlots->contains("$formattedHour:00")
            ];
            $workingHours[] = [
                'time' => "$formattedHour:30",
                'available' => !$occupiedSlots->contains("$formattedHour:30")
            ];
        }

        return response()->json([
            'availableSlots' => $workingHours,
            'doctor' => Medico::find($medicoId)
        ]);
    }
}
