<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\Cita;
use Illuminate\Http\Request;
use DB;

class ComprobanteController extends Controller
{
    public function store(Request $request)
    {
        try {
            $monto_total = 0;
            $citas = Cita::whereIn('id', $request->citas)
                ->with('tipoCita')  // Eager load tipoCita
                ->get();
            
            // Calculate total amount from tipos_citas prices
            foreach ($citas as $cita) {
                $monto_total += $cita->tipoCita->precio;
            }

            // Create comprobante
            $comprobante = Comprobante::create([
                'tipo' => $request->tipo,
                'serie' => $request->tipo === 'b' ? 'B001' : 'F001',
                'correlativo' => $this->getNextCorrelativo($request->tipo),
                'id_metodo_pago' => $request->id_metodo_pago, // Ensure this is passed in the request
                'paciente_id' => $request->paciente_id,
                'monto_total' => $monto_total
            ]);

            // Update citas estado and attach to comprobante
            foreach ($citas as $cita) {
                $cita->estado = 'p';
                $cita->save();
                $comprobante->citas()->attach($cita->id, ['monto' => $cita->tipoCita->precio]);
            }
            return response()->json(['comprobante' => $comprobante->load('citas')]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function getNextCorrelativo($tipo)
    {
        $lastComprobante = Comprobante::where('tipo', $tipo)
            ->where('serie', $tipo === 'b' ? 'B001' : 'F001')
            ->orderBy('correlativo', 'desc')
            ->first();
            
        return $lastComprobante ? $lastComprobante->correlativo + 1 : 1;
    }
}
