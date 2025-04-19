<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Comprobante de Intervención</title>
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
            text-align: center;
            padding-bottom: 5px;
            border-bottom: 1px dashed #000;
        }

        .client-info {
            margin-top: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }

        .items {
            margin-top: 10px;
        }

        .item {
            margin-bottom: 5px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 5px;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        th, td {
            border: 1px dashed #ddd;
            padding: 5px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <div class="header">
        <!-- Logo del comprobante -->
        <div style="text-align: center; margin-bottom: 10px;">
            <img src="{{ url('/images/mroi_logo_comprobantes_58.png') }}" style="max-width: 130px; height: auto; display: block; margin: 0 auto;">
        </div>
        <h2 style="margin:0;">OFTALMO CUBA E.I.R.L.</h2>
        <p>Dirección: Av. Javier prado este 1684 Dist. San Isidro</p>
        <p>RUC: 20603337442</p>
        <p>Teléfono: 912 611 980 / 944 245 378</p>
    </div>

    <div class="details">
        <p style="margin:5px 0;">{{ $comprobante->tipo === 'b' ? 'BOLETA' : 'FACTURA' }} DE VENTA ELECTRÓNICA</p>
        <p style="margin:5px 0;">
            {{ $comprobante->serie }}-{{ str_pad($comprobante->correlativo, 8, '0', STR_PAD_LEFT) }}
        </p>
        <p style="margin:5px 0;">Fecha de Emisión: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="client-info">
        <h3>DATOS DEL PACIENTE</h3>
        @if($comprobante->intervenciones->isNotEmpty() && $comprobante->intervenciones->first()->paciente)
            @php
                $paciente = App\Models\Paciente::where('num_historia', $comprobante->intervenciones->first()->num_historia)->first();
            @endphp
            <div>
                <p><strong>Número de Historia:</strong> {{ $comprobante->intervenciones->first()->num_historia }}</p>
                @if($paciente)
                    <p><strong>Paciente:</strong> {{ $paciente->nombres }} {{ $paciente->ap_paterno }} {{ $paciente->ap_materno }}</p>
                    <p><strong>Documento de Identidad:</strong> {{ $paciente->tipo_doc_identidad == 'dni' ? 'DNI' : 'CE' }} {{ $paciente->doc_identidad }}</p>
                @else
                    <p><strong>Paciente:</strong> No disponible</p>
                @endif
            </div>
        @else
            <p>Información del paciente no disponible.</p>
        @endif
    </div>

    <div class="items">
        <h3>DETALLES DE LA INTERVENCIÓN</h3>
        @if($comprobante->intervenciones->isNotEmpty())
            @foreach($comprobante->intervenciones as $intervencion)
                <div class="item">
                    <p><strong>Tipo de Intervención:</strong> {{ optional($intervencion->tipoIntervencion)->tipo_intervencion ?? 'N/A' }}</p>
                    <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($intervencion->fecha)->format('d/m/Y') }}</p>
                    <p><strong>Horario:</strong> 
                        @php
                            $horaInicio = \Carbon\Carbon::parse($intervencion->hora_inicio);
                            $horaFin = \Carbon\Carbon::parse($intervencion->hora_fin);
                            
                            // If hora_inicio and hora_fin are the same, add 30 minutes to hora_fin
                            if ($horaInicio->format('H:i') === $horaFin->format('H:i')) {
                                $horaFin->addMinutes(30);
                            }
                        @endphp
                        {{ $horaInicio->format('H:i') }} - {{ $horaFin->format('H:i') }}
                    </p>
                    <p><strong>Médico:</strong> {{ optional($intervencion->medico)->nombres }} {{ optional($intervencion->medico)->ap_paterno }}</p>
                    <p><strong>Sede de Operación:</strong> {{ optional($intervencion->sedeOperacion)->nombre ?? 'N/A' }}</p>
                    <p><strong>Monto:</strong> S/ {{ number_format($intervencion->pivot->monto, 2) }}</p>
                </div>
                @if(!$loop->last)
                    <hr style="border-top: 1px dashed #ccc; margin: 10px 0;">
                @endif
            @endforeach
        @else
            <p>No hay intervenciones registradas.</p>
        @endif
    </div>

    <div class="total">
        <p><strong>MÉTODO DE PAGO:</strong> {{ optional($comprobante->metodoPago)->nombre ?? 'N/A' }}</p>
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
            ]);
        @endphp
        <div class="qr" style="text-align: center; margin-top: 20px;">
            <img src="data:image/png;base64,{{ base64_encode(\SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')->size(150)->generate($qrContent)) }}" alt="QR Code">
        </div>
        <p>Representación impresa de la {{ $comprobante->tipo === 'b' ? 'BOLETA' : 'FACTURA' }} de venta electrónica</p>
    </div>
</body>

</html>
