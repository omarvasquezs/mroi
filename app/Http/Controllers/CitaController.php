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

        // Check if patient already has an appointment at the same time (with any doctor)
        $existingPatientAppointment = Cita::where('num_historia', $validated['num_historia'])
            ->whereDate('fecha', $validated['fecha'])
            ->whereTime('fecha', $validated['hora'])
            ->first();

        if ($existingPatientAppointment) {
            return response()->json([
                'message' => 'El paciente ya tiene una cita programada a esta hora con otro médico.'
            ], 422);
        }

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
            'observaciones' => 'nullable|string',
            'num_historia' => 'required|exists:pacientes,num_historia'
        ]);

        $cita = Cita::findOrFail($id);

        // Combine date and time
        $fechaHora = date('Y-m-d H:i:s', strtotime($validated['fecha'] . ' ' . $validated['hora']));

        // Check if the patient already has an appointment at this time with any doctor (excluding this appointment)
        $patientConflict = Cita::where('num_historia', $validated['num_historia'])
            ->whereDate('fecha', $validated['fecha'])
            ->whereTime('fecha', $validated['hora'])
            ->where('id', '!=', $id)
            ->first();

        if ($patientConflict) {
            return response()->json([
                'message' => 'El paciente ya tiene una cita programada a esta hora con otro médico.',
                'conflicting_doctor' => $patientConflict->medico->nombre ?? 'Médico desconocido'
            ], 422);
        }

        // Check if the doctor is available at this time (excluding this appointment)
        $doctorConflict = Cita::where('id_medico', $validated['id_medico'])
            ->whereDate('fecha', $validated['fecha'])
            ->whereTime('fecha', $validated['hora'])
            ->where('id', '!=', $id)
            ->first();

        if ($doctorConflict) {
            return response()->json(['message' => 'El médico ya tiene una cita programada a esta hora.'], 422);
        }

        $cita->update([
            'id_medico' => $validated['id_medico'],
            'fecha' => $fechaHora,
            'observaciones' => $validated['observaciones'],
            'num_historia' => $validated['num_historia']
        ]);

        // Refresh the cita with all relationships
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

    public function validateConflict(Request $request)
    {
        $validated = $request->validate([
            'num_historia' => 'required|exists:pacientes,num_historia',
            'id_medico' => 'required|exists:medicos,id',
            'fecha' => 'required|date',
            'hora' => 'required',
            'current_cita_id' => 'nullable|integer' // Optional parameter for rescheduling
        ]);

        // Combine date and time
        $fechaHora = date('Y-m-d H:i:s', strtotime($validated['fecha'] . ' ' . $validated['hora']));

        // Check if the patient already has an appointment at this time with any doctor
        $query = Cita::where('num_historia', $validated['num_historia'])
            ->whereDate('fecha', $validated['fecha'])
            ->whereTime('fecha', $validated['hora']);

        // If rescheduling, exclude the current appointment from the check
        if (isset($validated['current_cita_id'])) {
            $query->where('id', '!=', $validated['current_cita_id']);
        }

        $existingAppointment = $query->first();

        if ($existingAppointment) {
            return response()->json([
                'message' => 'El paciente ya tiene una cita programada a esta hora con otro médico.',
                'conflicting_doctor' => $existingAppointment->medico->nombre ?? 'Médico desconocido'
            ], 422);
        }

        // Check if this doctor is available at this time
        $query = Cita::where('id_medico', $validated['id_medico'])
            ->whereDate('fecha', $validated['fecha'])
            ->whereTime('fecha', $validated['hora']);

        // If rescheduling, exclude the current appointment from the check
        if (isset($validated['current_cita_id'])) {
            $query->where('id', '!=', $validated['current_cita_id']);
        }

        $doctorAppointment = $query->first();

        if ($doctorAppointment) {
            return response()->json([
                'message' => 'El médico ya tiene una cita programada a esta hora.'
            ], 422);
        }

        return response()->json(['message' => 'No conflict found.'], 200);
    }

    public function show($id)
    {
        $cita = Cita::with(['paciente', 'medico', 'tipoCita'])
            ->findOrFail($id);
        
        return response()->json($cita);
    }
}
