<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\Cita;
use App\Models\ProductoComprobante;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;

class ComprobanteController extends Controller
{
    public function index()
    {
        try {
            $comprobantes = Comprobante::with(['citas', 'metodoPago'])->get();

            foreach ($comprobantes as $comprobante) {
                if ($comprobante->citas()->exists()) {
                    $comprobante->servicio = 'Cita';
                } elseif (ProductoComprobante::where('comprobante_id', $comprobante->id)->exists()) {
                    $comprobante->servicio = 'Producto';
                } else {
                    $comprobante->servicio = 'Desconocido';
                }
            }

            return response()->json($comprobantes);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $monto_total = 0;

            if ($request->has('citas')) {
                $citas = Cita::whereIn('id', $request->citas)
                    ->with('tipoCita')
                    ->get();

                foreach ($citas as $cita) {
                    $monto_total += $cita->tipoCita->precio;
                }
            } elseif ($request->has('productos_comprobante_id')) {
                $productoComprobante = ProductoComprobante::findOrFail($request->productos_comprobante_id);
                $monto_total = $productoComprobante->monto_total;
            }

            $comprobante = Comprobante::create([
                'tipo' => $request->tipo,
                'serie' => $request->tipo === 'b' ? 'B001' : 'F001',
                'correlativo' => $this->getNextCorrelativo($request->tipo),
                'id_metodo_pago' => $request->id_metodo_pago,
                'paciente_id' => $request->paciente_id ?? null,
                'monto_total' => $monto_total,
                'pagado' => true
            ]);

            if ($request->has('citas')) {
                foreach ($citas as $cita) {
                    $cita->estado = 'p';
                    $cita->save();
                    $comprobante->citas()->attach($cita->id, ['monto' => $cita->tipoCita->precio]);
                }
            } elseif ($request->has('productos_comprobante_id')) {
                $productoComprobante = ProductoComprobante::findOrFail($request->productos_comprobante_id);
                $productoComprobante->comprobante_id = $comprobante->id;
                $productoComprobante->save();
            }

            return response()->json(['comprobante' => $comprobante->load('citas')]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function generate(Request $request, $id)
    {
        try {
            Log::info('Starting comprobante generation for ID: ' . $id);

            // Verify that the DomPDF package is installed
            if (!class_exists(PDF::class)) {
                throw new \Exception('DomPDF package not installed. Run: composer require barryvdh/laravel-dompdf');
            }

            // Load all necessary relationships
            $comprobante = Comprobante::with([
                'citas.tipoCita', 
                'metodoPago', 
                'paciente',
                'productoComprobante.items.stock'
            ])->findOrFail($id);
            
            Log::info('Comprobante loaded:', ['comprobante' => $comprobante->toArray()]);

            // Add service type
            if ($comprobante->citas()->exists()) {
                $comprobante->servicio = 'Cita';
            } elseif ($comprobante->productoComprobante) {
                $comprobante->servicio = 'Producto';
            } else {
                $comprobante->servicio = 'Desconocido';
            }

            // Check view existence
            if (!View::exists('comprobantes.pdf')) {
                throw new \Exception('PDF view template not found: comprobantes.pdf');
            }

            // Generate PDF
            $pdf = PDF::loadView('comprobantes.pdf', ['comprobante' => $comprobante]);
            
            if ($request->query('format') === 'thermal') {
                $pdf->setPaper([0, 0, 80, 200], 'portrait');
            }

            return response()->json([
                'comprobante' => $comprobante,
                'pdf' => base64_encode($pdf->output())
            ]);

        } catch (\Exception $e) {
            Log::error('Comprobante generation failed:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => $e->getMessage(),
                'details' => [
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
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
