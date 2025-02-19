<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Comprobante {{ $comprobante->serie }}-{{ str_pad($comprobante->correlativo, 8, '0', STR_PAD_LEFT) }}</title>
    <style>
        @page {
            margin: 1.1rem;
        }

        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
        }

        .details {
            margin-bottom: 10px;
        }

        .item {
            margin-bottom: 5px;
        }

        .total {
            border-top: 1px dashed #000;
            margin-top: 10px;
            padding-top: 5px;
            font-weight: bold;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h2 style="margin:0;">G & F oftalmólogas. SAC</h2>
        <p style="margin:5px 0;">{{ $comprobante->tipo === 'b' ? 'BOLETA' : 'FACTURA' }} DE VENTA ELECTRÓNICA</p>
        <p style="margin:5px 0;">
            {{ $comprobante->serie }}-{{ str_pad($comprobante->correlativo, 8, '0', STR_PAD_LEFT) }}</p>
    </div>

    <div class="details">
        <p>Fecha: {{ \Carbon\Carbon::parse($comprobante->created_at)->format('d/m/Y H:i') }}</p>
    </div>

    <div class="items">
        <h3>DETALLE DE SERVICIOS</h3>
        @if($comprobante->citas->isNotEmpty())
            @foreach($comprobante->citas as $cita)
                <div class="item">
                    <p>Número de Historia: {{ optional($comprobante->citas->first()->paciente)->num_historia ?? 'N/A' }}</p>
                    <p>Paciente: {{ optional($comprobante->citas->first()->paciente)->nombres ?? 'N/A' }}
                        {{ optional($comprobante->citas->first()->paciente)->ap_paterno ?? '' }}
                        {{ optional($comprobante->citas->first()->paciente)->ap_materno ?? '' }}</p>
                    <p>Tipo de Cita: {{ optional($cita->tipoCita)->tipo_cita ?? 'N/A' }}</p>
                    <p>Médico: {{ $cita->medico->nombres ?? 'N/A' }} {{ $cita->medico->ap_paterno ?? '' }}
                        {{ $cita->medico->ap_materno ?? '' }}</p>
                    <p>Fecha de la Cita: {{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y H:i') }}</p>
                    <p>Método de pago: {{ optional($comprobante->metodoPago)->nombre ?? 'N/A' }}</p>
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