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
            font-size: 8px;
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

        .qr {
            margin: 20px;
        }
    </style>
</head>

<body>
    <div class="header">
        <!-- Logo del comprobante -->
        <div style="text-align: center; margin-bottom: 10px;">
            <img src="{{ url('/images/gyf_logo_comprobantes_58.png') }}" style="max-width: 160px; height: auto; display: block; margin: 0 auto;">
        </div>
        <h2 style="margin:0;">G & F oftalmólogas. S.A.C.</h2>
        <p>Dirección: Av. El Sol esq. Jr. Unión. Villa el Salvador</p>
        <p>RUC: 20613814265</p>
        <p>Teléfono: 940 213 168</p>
    </div>

    <div class="details" style="text-align: center; padding-bottom: 5px;border-bottom: 1px dashed #000;">
        <p style="margin:5px 0;">{{ $comprobante->tipo === 'b' ? 'BOLETA' : 'FACTURA' }} DE VENTA ELECTRÓNICA</p>
        <p style="margin:5px 0;">
            {{ $comprobante->serie }}-{{ str_pad($comprobante->correlativo, 8, '0', STR_PAD_LEFT) }}
        </p>
    </div>

    <div class="items">
        <h3>DETALLE DE SERVICIO</h3>
        @if($comprobante->citas->isNotEmpty())
            @foreach($comprobante->citas as $cita)
                <div class="item">
                    <p><strong>Fecha de Emision:</strong> {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
                    <p><strong>Número de Historia:</strong> {{ optional($comprobante->citas->first()->paciente)->num_historia ?? 'N/A' }}</p>
                    <p><strong>Paciente:</strong> {{ optional($comprobante->citas->first()->paciente)->nombres ?? 'N/A' }}
                        {{ optional($comprobante->citas->first()->paciente)->ap_paterno ?? '' }}
                        {{ optional($comprobante->citas->first()->paciente)->ap_materno ?? '' }}</p>
                    <p><strong>Tipo de Cita:</strong> {{ optional($cita->tipoCita)->tipo_cita ?? 'N/A' }}</p>
                    <p><strong>Médico:</strong> {{ $cita->medico->nombres ?? 'N/A' }} {{ $cita->medico->ap_paterno ?? '' }}
                        {{ $cita->medico->ap_materno ?? '' }}</p>
                    <p><strong>Fecha de la Cita:</strong> {{ \Carbon\Carbon::parse($cita->fecha)->format('d/m/Y H:i') }}</p>
                    <p><strong>Método de pago:</strong> {{ optional($comprobante->metodoPago)->nombre ?? 'N/A' }}</p>
                    <p><strong>Monto:</strong> S/ {{ number_format($cita->pivot->monto, 2) }}</p>
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
        @php
            $qrContent = json_encode([
                'serie'         => $comprobante->serie,
                'correlativo'   => $comprobante->correlativo,
                'monto_total'   => $comprobante->monto_total,
                'fecha_emision' => \Carbon\Carbon::now()->format('d/m/Y H:i'),
                // add additional fields as needed
            ]);
        @endphp
        <div class="qr" style="text-align: center; margin-top: 20px;">
            <img src="data:image/png;base64,{{ base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(150)->generate($qrContent)) }}" alt="QR Code">
        </div>
        <p>Representación impresa de la {{ $comprobante->tipo === 'b' ? 'BOLETA' : 'FACTURA' }} de venta electrónica</p>
    </div>
</body>

</html>
