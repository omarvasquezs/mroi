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
            'hora_inicio' => 'required',
            'hora_fin' => 'required',
            'observaciones' => 'nullable|string',
            'id_tipo_intervencion' => 'required|exists:tipos_intervenciones,id',
            'estado' => 'nullable|string',
            'clinica_inicial_id' => 'nullable|exists:locales,id',
            'medico_que_indica_id' => 'nullable|exists:medicos,id',
            'sede_operacion_id' => 'nullable|exists:locales,id'
        ]);
        
        // Set default estado if not provided
        if (!isset($validated['estado'])) {
            $validated['estado'] = 'd'; // Default estado value (pendiente)
        }

        $intervencion = Intervencion::create([
            'num_historia' => $validated['num_historia'],
            'id_medico' => $validated['id_medico'],
            'fecha' => $validated['fecha'],
            'hora_inicio' => $validated['hora_inicio'],
            'hora_fin' => $validated['hora_fin'],
            'observaciones' => $validated['observaciones'],
            'id_tipo_intervencion' => $validated['id_tipo_intervencion'],
            'estado' => $validated['estado'],
            'clinica_inicial_id' => $validated['clinica_inicial_id'] ?? null,
            'medico_que_indica_id' => $validated['medico_que_indica_id'] ?? null,
            'sede_operacion_id' => $validated['sede_operacion_id'] ?? null
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

        // Include clinic, referring doctor and operation site in the JSON
        $intervenciones = Intervencion::where('id_medico', $medicoId)
            ->whereDate('fecha', $fecha)
            ->with([
                'paciente:id,num_historia,nombres,ap_paterno,ap_materno',
                'tipoIntervencion:id,tipo_intervencion,precio',
                'clinicaInicial:id,nombre',
                'medicoQueIndica:id,nombres,ap_paterno,ap_materno',
                'sedeOperacion:id,nombre'
            ])
            ->get([
                'id',
                'num_historia',
                'id_medico',
                'fecha',
                'hora_inicio',
                'hora_fin',
                'observaciones',
                'id_tipo_intervencion',
                'clinica_inicial_id',
                'medico_que_indica_id',
                'sede_operacion_id',
                'estado'
            ]);

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
            ->where(function ($query) use ($hora) {
                // Find interventions where the given time falls within the range
                $query->whereTime('hora_inicio', '<=', $hora)
                      ->whereTime('hora_fin', '>', $hora);
            })
            ->with(['paciente', 'tipoIntervencion'])
            ->first();

        return response()->json($intervencion);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'id_medico' => 'required|exists:medicos,id',
            'fecha' => 'required|date',
            'hora_inicio' => 'required',
            'hora_fin' => 'required',
            'observaciones' => 'nullable|string',
            'num_historia' => 'required|exists:pacientes,num_historia',
            'id_tipo_intervencion' => 'required|exists:tipos_intervenciones,id'
        ]);

        $intervencion = Intervencion::findOrFail($id);

        $intervencion->update([
            'id_medico' => $validated['id_medico'],
            'fecha' => $validated['fecha'],
            'hora_inicio' => $validated['hora_inicio'],
            'hora_fin' => $validated['hora_fin'],
            'observaciones' => $validated['observaciones'],
            'num_historia' => $validated['num_historia'],
            'id_tipo_intervencion' => $validated['id_tipo_intervencion']
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
            ->get(['id', 'hora_inicio', 'hora_fin']);

        // Get working hours (8:00 to 19:00 with 15 minute intervals)
        $workingHours = [];
        $startHour = 8;
        $endHour = 19;

        // Generate all possible time slots
        for ($hour = $startHour; $hour < $endHour; $hour++) {
            $formattedHour = str_pad($hour, 2, '0', STR_PAD_LEFT);
            
            // Generate slots at 00, 15, 30, and 45 minutes past the hour
            foreach ([0, 15, 30, 45] as $minute) {
                $formattedMinute = str_pad($minute, 2, '0', STR_PAD_LEFT);
                $timeSlot = "$formattedHour:$formattedMinute";
                
                // Check if this time slot is available
                $isAvailable = true;
                foreach ($intervenciones as $intervencion) {
                    $startTime = substr($intervencion->hora_inicio, 0, 5); // Format HH:MM
                    $endTime = substr($intervencion->hora_fin, 0, 5); // Format HH:MM
                    
                    if ($timeSlot >= $startTime && $timeSlot < $endTime) {
                        $isAvailable = false;
                        break;
                    }
                }
                
                $workingHours[] = [
                    'time' => $timeSlot,
                    'available' => $isAvailable
                ];
            }
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
            'hora_inicio' => 'required',
            'hora_fin' => 'required',
            'current_intervencion_id' => 'nullable|integer'
        ]);

        // Check if this doctor has any conflicting interventions at this time range
        $query = Intervencion::where('id_medico', $validated['id_medico'])
            ->whereDate('fecha', $validated['fecha'])
            ->where(function ($query) use ($validated) {
                // Check for time range overlap
                $query->where(function ($q) use ($validated) {
                    // New intervention starts during an existing one
                    $q->where('hora_inicio', '<=', $validated['hora_inicio'])
                      ->where('hora_fin', '>', $validated['hora_inicio']);
                })->orWhere(function ($q) use ($validated) {
                    // New intervention ends during an existing one
                    $q->where('hora_inicio', '<', $validated['hora_fin'])
                      ->where('hora_fin', '>=', $validated['hora_fin']);
                })->orWhere(function ($q) use ($validated) {
                    // New intervention completely contains an existing one
                    $q->where('hora_inicio', '>=', $validated['hora_inicio'])
                      ->where('hora_fin', '<=', $validated['hora_fin']);
                });
            });

        // If rescheduling, exclude the current intervention from the check
        if (isset($validated['current_intervencion_id'])) {
            $query->where('id', '!=', $validated['current_intervencion_id']);
        }

        $conflictIntervencion = $query->first();

        if ($conflictIntervencion) {
            return response()->json([
                'message' => 'El médico ya tiene una intervención programada en este rango de tiempo.'
            ], 422);
        }

        return response()->json(['message' => 'No conflict found.'], 200);
    }

    public function show($id)
    {
        $intervencion = Intervencion::with(['paciente', 'medico', 'tipoIntervencion', 'clinicaInicial', 'medicoQueIndica', 'sedeOperacion'])
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
