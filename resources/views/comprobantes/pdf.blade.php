<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Comprobante {{ $comprobante->serie }}-{{ str_pad($comprobante->correlativo, 8, '0', STR_PAD_LEFT) }}</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .details {
            margin-bottom: 20px;
        }
        .items {
            margin-bottom: 20px;
        }
        .total {
            border-top: 1px dashed #000;
            padding-top: 10px;
            text-align: right;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>CLÍNICA GYF</h2>
        <p>{{ $comprobante->tipo === 'b' ? 'BOLETA' : 'FACTURA' }} DE VENTA ELECTRÓNICA</p>
        <p>{{ $comprobante->serie }}-{{ str_pad($comprobante->correlativo, 8, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="details">
        <p>Fecha: {{ \Carbon\Carbon::parse($comprobante->created_at)->format('d/m/Y H:i') }}</p>
        <p>Paciente: {{ optional($comprobante->paciente)->nombre ?? 'N/A' }}</p>
        <p>Historia: {{ optional($comprobante->paciente)->num_historia ?? 'N/A' }}</p>
        <p>Método de pago: {{ optional($comprobante->metodoPago)->nombre ?? 'N/A' }}</p>
    </div>

    <div class="items">
        <h3>DETALLE DE SERVICIOS</h3>
        @if($comprobante->citas->isNotEmpty())
            @foreach($comprobante->citas as $cita)
                <div class="item">
                    <p>{{ $cita->tipo_cita }}</p>
                    <p>Médico: {{ $cita->medico }}</p>
                    <p>Fecha: {{ $cita->fecha }}</p>
                    <p>Monto: S/ {{ number_format($cita->pivot->monto, 2) }}</p>
                </div>
                @if(!$loop->last)
                    <hr style="border-top: 1px dashed #ccc">
                @endif
            @endforeach
        @endif
    </div>

    <div class="total">
        <p><strong>TOTAL: S/ {{ number_format($comprobante->monto_total, 2) }}</strong></p>
    </div>

    <div class="footer">
        <p>¡Gracias por su preferencia!</p>
        <p>Representación impresa de la {{ $comprobante->tipo === 'b' ? 'BOLETA' : 'FACTURA' }} de venta electrónica</p>
    </div>
</body>
</html>
