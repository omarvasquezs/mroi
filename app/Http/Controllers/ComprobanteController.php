<?php

namespace App\Http\Controllers;

use App\Models\Comprobante;
use App\Models\Cita;
use App\Models\ProductoComprobante;
use App\Models\Intervencion; // Add Intervencion model
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Illuminate\Support\Facades\Log;

class ComprobanteController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Get pagination parameters, default to 10 items per page
            $perPage = $request->query('per_page', 10);
            $page = $request->query('page', 1);
            
            // Get sorting parameters, default to created_at desc
            $sortBy = $request->query('sort_by', 'created_at');
            $sortDirection = $request->query('sort_direction', 'desc');

            // Load comprobantes with related models
            $comprobantesQuery = Comprobante::with([
                'citas.paciente',
                'metodoPago',
                'productoComprobante'
            ]);

            // Apply date filters
            if ($request->has('fecha_inicio') && !empty($request->fecha_inicio)) {
                $comprobantesQuery->whereDate('created_at', '>=', $request->fecha_inicio);
            }

            if ($request->has('fecha_fin') && !empty($request->fecha_fin)) {
                $comprobantesQuery->whereDate('created_at', '<=', $request->fecha_fin);
            }

            if ($request->has('fecha_hoy_dia') && $request->fecha_hoy_dia == 1) {
                $today = now()->format('Y-m-d');
                $comprobantesQuery->whereDate('created_at', $today);
            }

            if ($request->has('mes_actual') && $request->mes_actual == 1) {
                $firstDay = now()->startOfMonth()->format('Y-m-d');
                $lastDay = now()->endOfMonth()->format('Y-m-d');
                $comprobantesQuery->whereDate('created_at', '>=', $firstDay)
                                 ->whereDate('created_at', '<=', $lastDay);
            }
            
            // Apply sorting
            $comprobantesQuery->orderBy($sortBy, $sortDirection);

            // Get paginated results
            $paginatedComprobantes = $comprobantesQuery->paginate($perPage, ['*'], 'page', $page);

            // Process each comprobante to add necessary fields
            foreach ($paginatedComprobantes as $comprobante) {
                // Determine the type of service
                if ($comprobante->citas()->exists()) {
                    $comprobante->servicio = 'Cita';
                    
                    // Add patient information from cita relation
                    $cita = $comprobante->citas->first();
                    if ($cita && $cita->paciente) {
                        $paciente = $cita->paciente;
                        $comprobante->paciente_nombre = trim($paciente->nombres . ' ' . $paciente->ap_paterno . ' ' . $paciente->ap_materno);
                    }
                } elseif ($comprobante->productoComprobante) {
                    $comprobante->servicio = 'Producto';
                    
                    // Add patient name from productos_comprobante
                    if ($comprobante->productoComprobante) {
                        $comprobante->paciente_nombre = $comprobante->productoComprobante->nombres;
                    }
                } elseif ($comprobante->intervenciones()->exists()) {
                    $comprobante->servicio = 'Intervención';
                    
                    // Add patient information from intervención relation
                    $intervencion = $comprobante->intervenciones->first();
                    if ($intervencion && $intervencion->paciente) {
                        $paciente = $intervencion->paciente;
                        $comprobante->paciente_nombre = trim($paciente->nombres . ' ' . $paciente->ap_paterno . ' ' . $paciente->ap_materno);
                    }
                } else {
                    $comprobante->servicio = 'Desconocido';
                }
            }

            return response()->json($paginatedComprobantes);
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
            } elseif ($request->has('intervenciones')) {
                // Handle intervenciones
                $intervenciones = Intervencion::whereIn('id', $request->intervenciones)
                    ->with('tipoIntervencion')
                    ->get();

                foreach ($intervenciones as $intervencion) {
                    $monto_total += $intervencion->tipoIntervencion->precio;
                }
            }

            $comprobante = Comprobante::create([
                'tipo' => $request->tipo,
                'serie' => $request->tipo === 'b' ? 'B001' : 'F001',
                'correlativo' => $this->getNextCorrelativo($request->tipo),
                'id_metodo_pago' => $request->id_metodo_pago,
                'monto_total' => $monto_total
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
            } elseif ($request->has('intervenciones')) {
                // Process and update intervenciones
                foreach ($intervenciones as $intervencion) {
                    // Update estado from 'd' to 'p'
                    $intervencion->estado = 'p';
                    $intervencion->save();
                    
                    // Attach to the comprobante with the monto
                    $comprobante->intervenciones()->attach($intervencion->id, ['monto' => $intervencion->tipoIntervencion->precio]);
                }
            }

            return response()->json(['comprobante' => $comprobante->load(['citas', 'intervenciones'])]);
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

            $comprobante = $this->loadComprobante($id);

            // Generate PDF
            $pdf = $this->generatePdfDocument($comprobante, $request->query('format') === 'thermal');

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

    public function generatePdf($id)
    {
        try {
            $comprobante = $this->loadComprobante($id);

            // Generate the PDF with 58mm width (approximately 164 points)
            $pdf = $this->generatePdfDocument($comprobante);

            return $pdf->stream("Comprobante_{$comprobante->serie}_{$comprobante->correlativo}.pdf");
        } catch (\Exception $e) {
            Log::error('Error generating PDF:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    private function generatePdfDocument($comprobante, $isThermal = false)
    {
        // Determine the view based on comprobante type
        $view = 'comprobantes.pdf';
        
        if ($comprobante->servicio === 'Producto') {
            $view = 'comprobantes.productos_pdf';
        } elseif ($comprobante->servicio === 'Intervención') {
            $view = 'comprobantes.intervenciones_pdf'; // You may need to create this view
        }

        // First render to measure body height
        $pdf = PDF::loadView($view, ['comprobante' => $comprobante]);
        if ($isThermal) {
            $pdf->setPaper([0, 0, 80, 200], 'portrait');
        } else {
            $pdf->setPaper([0, 0, 164, 841.89 * 20], 'portrait');
            $pdf->setOptions([
                'margin-left' => '5mm',
                'margin-right' => '5mm',
                'margin-top' => '5mm',
                'margin-bottom' => '5mm',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true
            ]);
        }

        $GLOBALS['bodyHeight'] = 0;
        $pdf->setCallbacks([
            'myCallbacks' => [
                'event' => 'end_frame',
                'f' => function ($frame) {
                    $node = $frame->get_node();
                    if (strtolower($node->nodeName) === "body") {
                        $GLOBALS['bodyHeight'] += $frame->get_padding_box()['h'];
                    }
                }
            ]
        ]);
        $pdf->render();
        $docHeight = $GLOBALS['bodyHeight'] * 1.25 - 50;

        // Render PDF again using measured height
        unset($pdf);
        $pdf = PDF::loadView($view, ['comprobante' => $comprobante]);
        if ($isThermal) {
            $pdf->setPaper([0, 0, 80, 200], 'portrait');
        } else {
            $pdf->setPaper([0, 0, 164, $docHeight]);
            $pdf->setOptions([
                'isRemoteEnabled' => true
            ]);
        }
        return $pdf;
    }

    private function loadComprobante($id)
    {
        $comprobante = Comprobante::with([
            'citas.tipoCita',
            'metodoPago',
            'paciente',
            'productoComprobante.items.stock',
            'intervenciones.tipoIntervencion', // Add intervenciones relationship
            'intervenciones.medico'
        ])->findOrFail($id);

        // Determine the service type
        if ($comprobante->citas()->exists()) {
            $comprobante->servicio = 'Cita';
        } elseif ($comprobante->productoComprobante) {
            $comprobante->servicio = 'Producto';
        } elseif ($comprobante->intervenciones()->exists()) {
            $comprobante->servicio = 'Intervención';
        } else {
            $comprobante->servicio = 'Desconocido';
        }

        return $comprobante;
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
