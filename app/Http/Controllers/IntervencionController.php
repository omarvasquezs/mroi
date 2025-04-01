<?php

namespace App\Http\Controllers;

use App\Models\Intervencion;
use App\Models\Medico;
use App\Models\TipoIntervencion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntervencionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'num_historia' => 'required|exists:pacientes,num_historia',
            'id_medico' => 'required|exists:medicos,id',
            'fecha' => 'required|date',
            'hora' => 'required',
            'observaciones' => 'nullable|string',
            'id_tipo_intervencion' => 'required|exists:tipos_intervenciones,id'
        ]);

        // Combine date and time
        $fechaHora = date('Y-m-d H:i:s', strtotime($validated['fecha'] . ' ' . $validated['hora']));

        $intervencion = Intervencion::create([
            'num_historia' => $validated['num_historia'],
            'id_medico' => $validated['id_medico'],
            'fecha' => $fechaHora,
            'observaciones' => $validated['observaciones'],
            'id_tipo_intervencion' => $validated['id_tipo_intervencion']
        ]);

        return response()->json($intervencion, 201);
    }

    public function getIntervencionesByMedicoAndFecha(Request $request)
    {
        $medicoId = $request->query('medico');
        $fecha = $request->query('fecha');

        if (!$medicoId || !$fecha) {
            return response()->json([], 400);
        }

        $intervenciones = Intervencion::where('id_medico', $medicoId)
            ->whereDate('fecha', $fecha)
            ->with([
                'paciente' => function($query) {
                    $query->select('num_historia', 'nombres', 'ap_paterno', 'ap_materno');
                },
                'tipoIntervencion' => function($query) {
                    $query->select('id', 'tipo_intervencion', 'precio');
                }
            ])
            ->get([
                'id',
                'num_historia',
                'id_medico',
                'fecha',
                'observaciones',
                'id_tipo_intervencion'
            ]);

        // Transform the data to ensure we have the time in the right format
        $intervenciones = $intervenciones->map(function ($intervencion) {
            return [
                'id' => $intervencion->id,
                'num_historia' => $intervencion->num_historia,
                'fecha' => $intervencion->fecha,
                'hora' => date('H:i:s', strtotime($intervencion->fecha)),
                'observaciones' => $intervencion->observaciones,
                'paciente' => $intervencion->paciente,
                'tipoIntervencion' => $intervencion->tipoIntervencion
            ];
        });

        return response()->json($intervenciones);
    }

    public function getTiposIntervenciones()
    {
        return response()->json(TipoIntervencion::all());
    }

    public function checkIntervencion(Request $request)
    {
        $medicoId = $request->query('medico');
        $fecha = $request->query('fecha');
        $hora = $request->query('hora');

        if (!$medicoId || !$fecha || !$hora) {
            return response()->json(null, 400);
        }

        $intervencion = Intervencion::where('id_medico', $medicoId)
            ->whereDate('fecha', $fecha)
            ->whereTime('fecha', $hora)
            ->with(['paciente', 'tipoIntervencion'])
            ->first();

        return response()->json($intervencion);
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

        $intervencion = Intervencion::findOrFail($id);

        // Combine date and time
        $fechaHora = date('Y-m-d H:i:s', strtotime($validated['fecha'] . ' ' . $validated['hora']));

        $intervencion->update([
            'id_medico' => $validated['id_medico'],
            'fecha' => $fechaHora,
            'observaciones' => $validated['observaciones'],
            'num_historia' => $validated['num_historia']
        ]);

        // Refresh the intervencion with all relationships
        $intervencion->load(['paciente', 'medico', 'tipoIntervencion']);

        return response()->json($intervencion);
    }

    public function checkAvailability(Request $request)
    {
        $medicoId = $request->query('medico');
        $fecha = $request->query('fecha');

        if (!$medicoId || !$fecha) {
            return response()->json(['message' => 'Medico ID and date are required'], 400);
        }

        // Get all interventions for the selected doctor and date
        $intervenciones = Intervencion::where('id_medico', $medicoId)
            ->whereDate('fecha', $fecha)
            ->get(['id', 'fecha']);

        // Convert to a list of occupied time slots (hours)
        $occupiedSlots = $intervenciones->map(function ($intervencion) {
            return date('H:i', strtotime($intervencion->fecha));
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
            'current_intervencion_id' => 'nullable|integer'
        ]);

        // Check if this doctor is available at this time
        $query = Intervencion::where('id_medico', $validated['id_medico'])
            ->whereDate('fecha', $validated['fecha'])
            ->whereTime('fecha', $validated['hora']);

        // If rescheduling, exclude the current intervention from the check
        if (isset($validated['current_intervencion_id'])) {
            $query->where('id', '!=', $validated['current_intervencion_id']);
        }

        $doctorIntervencion = $query->first();

        if ($doctorIntervencion) {
            return response()->json([
                'message' => 'El médico ya tiene una intervención programada a esta hora.'
            ], 422);
        }

        return response()->json(['message' => 'No conflict found.'], 200);
    }

    public function show($id)
    {
        $intervencion = Intervencion::with(['paciente', 'medico', 'tipoIntervencion'])
            ->findOrFail($id);
        
        return response()->json($intervencion);
    }

    public function destroy($id)
    {
        $intervencion = Intervencion::findOrFail($id);
        
        // Only allow deletion of intervenciones with estado = 'd' (pendiente)
        if ($intervencion->estado !== 'd') {
            return response()->json([
                'message' => 'Solo se pueden eliminar intervenciones con estado pendiente.'
            ], 403);
        }
        
        $intervencion->delete();
        
        return response()->json([
            'message' => 'Intervención eliminada exitosamente'
        ]);
    }
}
