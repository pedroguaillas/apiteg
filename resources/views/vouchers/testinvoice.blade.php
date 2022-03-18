<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura</title>
    <style>
        * {
            box-sizing: border-box;
            padding: 0;
            margin: 0;
        }

        body {
            font-size: .5em;
            text-align: center;
        }

        /* Este estable el margen de la hoja */
        html {
            margin: 1em;
        }
    </style>
</head>

<body>
    <div>------------------------------------------------------------</div>
    <h6>{{ $company->company }}</h6>
    <h6>{{ $company->ruc }}</h6>
    <div>{{ $company->branches[0]->address }}</div>
    <div>Factura: {{ $movement->serie }}</div>
    <div>CLAVE DE ACCESO SRI</div>
    <div>{{ $movement->autorized !== null ? date( "d/m/Y H:i:s.000", strtotime( $movement->autorized ) ) : null }}</div>
    <div>------------------------------------------------------------</div>
    <div>------------------------------------------------------------</div>
    <div>CLIENTE</div>
    <div>Nombre: {{ $movement->serie }}</div>
    <div>------------------------------------------------------------</div>
    <div>------------------------------------------------------------</div>
    <table>
        <thead>
            <tr>
                <th>DESCRIPCION</th>
                <th style="text-align: center; width: 4em;">CANT.</th>
                <th style="text-align: right; width: 5em;">P. UNIT</th>
                <th style="text-align: right; width: 5em;">T. VENTA</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movement_items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td style="text-align: center; width: 4em;">{{ number_format($item->quantity, $company->decimal) }}</td>
                <td style="text-align: right; width: 5em;">{{ number_format($item->price, $company->decimal) }}</td>
                <td style="text-align: right; width: 5em;">{{ number_format($item->quantity * $item->price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <table class="table-collapse">
        <tbody>
            <tr>
                <td style=" width:160px;" class="relleno">SUBTOTAL IVA 12%</td>
                <td style=" width:85px; padding-right: .5em; text-align: right;">{{ number_format($movement->base12, 2) }}</td>
            </tr>
            <tr>
                <td class="relleno">SUBTOTAL IVA 0%</td>
                <td style="padding-right: .5em; text-align: right;">{{ number_format($movement->base0, 2) }}</td>
            </tr>
            <tr>
                <td class="relleno">SUBTOTAL NO OBJETO IVA</td>
                <td style="padding-right: .5em; text-align: right;">{{ number_format($movement->no_iva, 2) }}</td>
            </tr>
            <tr>
                <td class="relleno">SUBTOTAL EXENTO IVA</td>
                <td style="padding-right: .5em; text-align: right;">0.00</td>
            </tr>
            <tr>
                <td class="relleno">SUBTOTAL SIN IMPUESTOS</td>
                <td style="padding-right: .5em; text-align: right;">{{ number_format($movement->base12 + $movement->base0 + $movement->no_iva, 2) }}</td>
            </tr>
            <tr>
                <td class="relleno">DESCUENTO</td>
                <td style="padding-right: .5em; text-align: right;">{{ number_format($movement->discount, 2) }}</td>
            </tr>
            <tr>
                <td class="relleno">IVA 12%</td>
                <td style="padding-right: .5em; text-align: right;">{{ number_format($movement->iva, 2) }}</td>
            </tr>
            <tr>
                <td class="relleno">PROPINA</td>
                <td style="padding-right: .5em; text-align: right;">0.00</td>
            </tr>
            <tr>
                <th class="relleno">TOTAL</th>
                <th style="padding-right: .5em; text-align: right;">{{ number_format($movement->total, 2) }}</th>
            </tr>
        </tbody>
    </table>
</body>

</html>