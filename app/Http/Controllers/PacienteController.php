<?php

namespace App\Http\Controllers;

use App\Models\Paciente;
use App\Models\Cita;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
        $request->validate([
            'nombres' => 'required|string|max:255',
            'ap_paterno' => 'required|string|max:255',
            'ap_materno' => 'required|string|max:255',
            'doc_identidad' => 'required|string|max:255|unique:pacientes',
            'telefono_personal' => 'required|string|max:255',
            // Make other fields optional
            'f_nacimiento' => 'nullable|date',
            'estado_civil' => 'nullable|string|in:S,C,V,D',
            'direccion_personal' => 'nullable|string|max:255',
            'correo_personal' => 'nullable|email|max:255',
            'ocupacion' => 'nullable|string|max:255',
            'nom_centro_laboral' => 'nullable|string|max:255',
            'telefono_trabajo' => 'nullable|string|max:255',
            'correo_trabajo' => 'nullable|email|max:255',
            'direccion_trabajo' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $paciente = new Paciente($request->all());
        $paciente->num_historia = str_pad(Paciente::max('id') + 1, 7, '0', STR_PAD_LEFT);
        $paciente->save();
        return response()->json($paciente, 201);
    }

    public function show($id)
    {
        $patient = Paciente::with('pendingAppointments')->findOrFail($id);
        return response()->json([
            'patient' => $patient,
            'pendingAppointments' => $patient->pendingAppointments
        ]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombres' => 'required|string|max:255',
            'ap_paterno' => 'required|string|max:255',
            'ap_materno' => 'required|string|max:255',
            'doc_identidad' => 'required|string|max:255|unique:pacientes,doc_identidad,' . $id,
            'telefono_personal' => 'required|string|max:255',
            // Make other fields optional
            'f_nacimiento' => 'nullable|date',
            'estado_civil' => 'nullable|string|in:S,C,V,D',
            'direccion_personal' => 'nullable|string|max:255',
            'correo_personal' => 'nullable|email|max:255',
            'ocupacion' => 'nullable|string|max:255',
            'nom_centro_laboral' => 'nullable|string|max:255',
            'telefono_trabajo' => 'nullable|string|max:255',
            'correo_trabajo' => 'nullable|email|max:255',
            'direccion_trabajo' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',
        ]);

        $paciente = Paciente::findOrFail($id);
        $paciente->update($request->all());
        return response()->json($paciente);
    }

    public function destroy($id)
    {
        $paciente = Paciente::findOrFail($id);
        
        // Check if the patient has any appointments
        $hasAppointments = Cita::where('num_historia', $paciente->num_historia)->exists();
        
        if ($hasAppointments) {
            return response()->json([
                'error' => 'No se puede eliminar el paciente',
                'message' => 'El paciente tiene citas programadas. Debe eliminar primero todas las citas asociadas.'
            ], 409); // Conflict status code
        }
        
        $paciente->delete();
        return response()->json(null, 204);
    }

    public function checkDocIdentidad(Request $request)
    {
        $exists = Paciente::where('doc_identidad', $request->doc_identidad)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function getAllPacientes()
    {
        $pacientes = Paciente::select(
            'id',
            'num_historia',
            'nombres',
            'ap_paterno',
            'ap_materno',
            'doc_identidad',
            'estado_civil',
            'f_nacimiento',
            'direccion_personal',
            'correo_personal',
            'telefono_personal',
            'estado_historia',
            'ocupacion',
            'nom_centro_laboral',
            'telefono_trabajo',
            'correo_trabajo',
            'direccion_trabajo',
            'observaciones',
            DB::raw("CONCAT(nombres, ' ', ap_paterno, ' ', ap_materno) as nombre")
        )->orderBy('nombres')->get();
        
        return response()->json($pacientes);
    }

    public function search($term)
    {
        try {
            $patient = Paciente::where('num_historia', $term)->first();
            
            if (!$patient) {
                return response()->json([
                    'error' => 'Paciente no encontrado',
                    'term' => $term
                ], 404);
            }

            $pendingAppointments = Cita::where('num_historia', $patient->num_historia)
                ->where('estado', 'd')
                ->with([
                    'medico:id,nombres,ap_paterno,ap_materno',
                    'tipoCita:id,tipo_cita,precio'  // Include precio
                ])
                ->get();

            if ($pendingAppointments->isEmpty()) {
                return response()->json([
                    'patient' => [
                        'id' => $patient->id,
                        'num_historia' => $patient->num_historia,
                        'nombre' => $patient->nombres . ' ' . $patient->ap_paterno . ' ' . $patient->ap_materno
                    ],
                    'pendingAppointments' => []
                ]);
            }

            $formattedAppointments = $pendingAppointments->map(function ($cita) {
                return [
                    'id' => $cita->id,
                    'fecha' => $cita->fecha,
                    'tipo_cita' => $cita->tipoCita ? $cita->tipoCita->tipo_cita : 'N/A',
                    'medico' => $cita->medico ? ($cita->medico->nombres . ' ' . $cita->medico->ap_paterno) : 'N/A',
                    'monto' => $cita->tipoCita ? $cita->tipoCita->precio : 0  // Use precio from tipoCita
                ];
            });

            return response()->json([
                'patient' => [
                    'id' => $patient->id,
                    'num_historia' => $patient->num_historia,
                    'nombre' => $patient->nombres . ' ' . $patient->ap_paterno . ' ' . $patient->ap_materno
                ],
                'pendingAppointments' => $formattedAppointments
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error en la búsqueda',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all patients who have appointments in the citas table with estado = 'd'
     *
     * @return \Illuminate\Http\Response
     */
    public function getPacientesWithCitas()
    {
        $pacientes = Paciente::select('pacientes.*', DB::raw("CONCAT(nombres, ' ', ap_paterno, ' ', ap_materno) as nombre"))
            ->join('citas', 'pacientes.num_historia', '=', 'citas.num_historia')
            ->where('citas.estado', '=', 'd')  // Filter for appointments with estado = 'd'
            ->distinct()
            ->orderBy('nombres')
            ->get();

        return response()->json($pacientes);
    }
}
