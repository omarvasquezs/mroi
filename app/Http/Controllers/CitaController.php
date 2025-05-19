<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Medico;
use App\Models\TipoCita;
use App\Models\Intervencion;
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

        // Calculate hora_inicio and hora_fin
        $hora_inicio = $validated['hora'];
        $hora_fin = date('H:i:s', strtotime($hora_inicio . ' +30 minutes'));

        // Check if patient already has an appointment at the same time (with any doctor)
        $existingPatientAppointment = Cita::where('num_historia', $validated['num_historia'])
            ->where('fecha', $validated['fecha'])
            ->where('hora_inicio', $hora_inicio)
            ->first();

        if ($existingPatientAppointment) {
            return response()->json([
                'message' => 'El paciente ya tiene una cita programada a esta hora con otro médico.'
            ], 422);
        }

        $cita = Cita::create([
            'num_historia' => $validated['num_historia'],
            'id_medico' => $validated['id_medico'],
            'fecha' => $validated['fecha'],
            'hora_inicio' => $hora_inicio,
            'hora_fin' => $hora_fin,
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
            ->where('fecha', $fecha)
            ->with([
                'paciente' => function ($query) {
                    $query->select('num_historia', 'nombres', 'ap_paterno', 'ap_materno');
                },
                'tipoCita' => function ($query) {
                    $query->select('id', 'tipo_cita', 'precio');
                }
            ])
            ->get([
                'id',
                'num_historia',
                'id_medico',
                'fecha',
                'hora_inicio',
                'hora_fin',
                'observaciones',
                'id_tipo_cita'
            ]);

        // Transform the data to ensure we have the time in the right format
        $citas = $citas->map(function ($cita) {
            return [
                'id' => $cita->id,
                'num_historia' => $cita->num_historia,
                'fecha' => $cita->fecha,
                'hora' => $cita->hora_inicio,
                'hora_fin' => $cita->hora_fin,
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

        // Ensure 'hora' parameter is treated as hora_inicio (HH:mm:ss)
        $hora_inicio = $request->query('hora');

        if (!$medicoId || !$fecha || !$hora_inicio) {
            return response()->json(['error' => 'Médico, fecha y hora de inicio son requeridos.'], 400);
        }

        try {
            $cita = Cita::with(['paciente', 'tipoCita', 'medico'])
                ->where('id_medico', $medicoId)
                ->where('fecha', $fecha) // 'fecha' column is DATE type
                ->where('hora_inicio', $hora_inicio) // Query by 'hora_inicio'
                ->first();

            // For server-side debugging, you can uncomment the following line:
            // \Log::info("CheckCita request:", ['medico' => $medicoId, 'fecha' => $fecha, 'hora_inicio' => $hora_inicio, 'found_cita_id' => $cita ? $cita->id : null]);

            return response()->json(['cita' => $cita]); // Consistent response structure
        } catch (\Exception $e) {
            // For server-side debugging, you can uncomment the following line:
            // \Log::error('Error in checkCita: ' . $e->getMessage(), ['exception' => $e, 'request_params' => $request->all()]);
            return response()->json(['error' => 'Error al verificar la cita.', 'details' => $e->getMessage()], 500);
        }
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

        // Prepare date and time fields
        $fecha = $validated['fecha'];           // date (Y-m-d)
        $hora_inicio = $validated['hora'];     // time (HH:mm)
        $hora_fin = date('H:i:s', strtotime($hora_inicio . ' +30 minutes'));

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

        // Calculate new start and end times
        $hora_inicio = $validated['hora'];
        $hora_fin = date('H:i:s', strtotime($hora_inicio . ' +30 minutes'));
        // Update cita with new data
        $cita->update([
            'num_historia'  => $validated['num_historia'],
            'id_medico'     => $validated['id_medico'],
            // Keep tipo_cita unchanged on reschedule
            'fecha'         => $fecha,
            'hora_inicio'   => $hora_inicio,
            'hora_fin'      => $hora_fin,
            'observaciones' => $validated['observaciones'] ?? $cita->observaciones,
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
        $doctorCitas = Cita::where('id_medico', $medicoId)
            ->whereDate('fecha', $fecha)
            ->get(['hora_inicio']);
        // Map doctor's occupied time slots
        $doctorSlots = collect($doctorCitas->map(function ($cita) {
            return substr($cita->hora_inicio, 0, 5);
        }));
        // Optionally include patient appointments to block slots
        $patientSlots = collect();
        if ($request->has('num_historia')) {
            $numHistoria = $request->query('num_historia');
            $pacienteCitas = Cita::where('num_historia', $numHistoria)
                ->whereDate('fecha', $fecha)
                ->get(['hora_inicio']);
            $patientSlots = collect($pacienteCitas->map(function ($cita) {
                return substr($cita->hora_inicio, 0, 5);
            }));
        }
        // Combine occupied slots
        $occupiedSlots = $doctorSlots->merge($patientSlots)->unique();

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

    /**
     * Improved: Validate if a paciente/medico has a cita or intervencion at the same date/time or overlapping range.
     * POST /api/citas/validate-conflict
     * Alias for validateCitaIntervencionConflict to match route.
     */
    public function validateConflict(Request $request)
    {
        return $this->validateCitaIntervencionConflict($request);
    }
    public function validateCitaIntervencionConflict(Request $request)
    {
        $validated = $request->validate([
            'num_historia' => 'required|exists:pacientes,num_historia',
            'id_medico' => 'required|exists:medicos,id',
            'fecha' => 'required|date',
            'hora' => 'required',
            'hora_fin' => 'nullable',
            'current_id' => 'nullable|integer',
            'type' => 'nullable|string'
        ]);

        $fecha = $validated['fecha'];
        $hora = $validated['hora'];
        $hora_fin = $validated['hora_fin'] ?? null;
        $num_historia = $validated['num_historia'];
        $id_medico = $validated['id_medico'];
        $currentId = $validated['current_id'] ?? null;
        $type = $validated['type'] ?? null;

        // Calculate cita range (30 min window)
        $citaStart = $hora;
        $citaEnd = date('H:i:s', strtotime("$hora +30 minutes"));

        // Check if any intervencion overlaps with this cita (for cita creation) for the same doctor
        $intervencionConflict = Intervencion::where('id_medico', $id_medico)
            ->whereDate('fecha', $fecha);
        if ($type === 'intervencion' && $currentId) {
            $intervencionConflict->where('id', '!=', $currentId);
        }
        $intervencionConflict->where(function ($q) use ($citaStart, $citaEnd) {
            $q->where('hora_inicio', '<', $citaEnd)
                ->where('hora_fin', '>', $citaStart);
        });
        $intervencionConflict = $intervencionConflict->first();
        if ($intervencionConflict) {
            return response()->json([
                'conflict' => true,
                'type' => 'intervencion',
                'message' => 'El médico ya tiene una intervención en ese rango horario con otro paciente.'
            ], 200);
        }
        // Check if any intervencion overlaps with this cita (for cita creation) for the same paciente
        $intervencionPacienteConflict = Intervencion::where('num_historia', $num_historia)
            ->whereDate('fecha', $fecha);
        if ($type === 'intervencion' && $currentId) {
            $intervencionPacienteConflict->where('id', '!=', $currentId);
        }
        $intervencionPacienteConflict->where(function ($q) use ($citaStart, $citaEnd) {
            $q->where('hora_inicio', '<', $citaEnd)
                ->where('hora_fin', '>', $citaStart);
        });
        $intervencionPacienteConflict = $intervencionPacienteConflict->first();
        if ($intervencionPacienteConflict) {
            return response()->json([
                'conflict' => true,
                'type' => 'intervencion',
                'message' => 'El paciente ya tiene una intervención en ese rango horario con otro médico.'
            ], 200);
        }

        // For intervencion creation, check if any cita overlaps with the intervencion range for the same doctor
        $intervStart = $hora;
        $intervEnd = $hora_fin ?? date('H:i:s', strtotime("$hora +30 minutes"));
        $citaQuery = Cita::where('id_medico', $id_medico)
            ->whereDate('fecha', $fecha);
        if ($type === 'cita' && $currentId) {
            $citaQuery->where('id', '!=', $currentId);
        }
        $citaQuery->where(function ($q) use ($intervStart, $intervEnd) {
            $q->whereRaw('TIME(fecha) < ? AND ADDTIME(TIME(fecha), "00:30:00") > ?', [$intervEnd, $intervStart]);
        });
        $citaConflict = $citaQuery->first();
        if ($citaConflict) {
            return response()->json([
                'conflict' => true,
                'type' => 'cita',
                'message' => 'El médico ya tiene una cita en ese rango horario con otro paciente.'
            ], 200);
        }
        // For intervencion creation, check if any cita overlaps with the intervencion range for the same paciente
        $citaPacienteQuery = Cita::where('num_historia', $num_historia)
            ->whereDate('fecha', $fecha);
        if ($type === 'cita' && $currentId) {
            $citaPacienteQuery->where('id', '!=', $currentId);
        }
        $citaPacienteQuery->where(function ($q) use ($intervStart, $intervEnd) {
            $q->whereRaw('TIME(fecha) < ? AND ADDTIME(TIME(fecha), "00:30:00") > ?', [$intervEnd, $intervStart]);
        });
        $citaPacienteConflict = $citaPacienteQuery->first();
        if ($citaPacienteConflict) {
            return response()->json([
                'conflict' => true,
                'type' => 'cita',
                'message' => 'El paciente ya tiene una cita en ese rango horario con otro médico.'
            ], 200);
        }

        return response()->json(['conflict' => false], 200);
    }

    public function show($id)
    {
        $cita = Cita::with(['paciente', 'medico', 'tipoCita'])
            ->findOrFail($id);

        return response()->json($cita);
    }

    public function destroy($id)
    {
        $cita = Cita::findOrFail($id);

        // Only allow deletion of citas with estado = 'd' (pendiente)
        if ($cita->estado !== 'd') {
            return response()->json([
                'message' => 'Solo se pueden eliminar citas con estado pendiente.'
            ], 403);
        }

        $cita->delete();

        return response()->json([
            'message' => 'Cita eliminada exitosamente'
        ]);
    }
}
