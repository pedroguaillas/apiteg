@extends('vouchers.theme.voucher')

@section('body')
<tr>
    <td colspan="2">
        <div style="padding-top: .5em;">
            <table style="width: 100%;" class="table table-sm">
                <tbody class="widthboder">
                    <tr>
                        <td class="relleno">Razón Social / Nombres Y Apellidos: {{ $movement->name }}</td>
                        <td class="align-middle">Identificación: {{ $movement->identication }}</td>
                    </tr>
                    <tr>
                        <td class="relleno">Fecha de Emisión: {{ date( "d/m/Y", strtotime( $movement->date ) ) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </td>
</tr>
<tr>
    <td colspan="2">
        <table style="width: 100%; border-radius: 10px; margin-top: .5em;" class="table-collapse">
            <thead>
                <tr>
                    <th>Comprobante</th>
                    <th>Número</th>
                    <th>Fecha de Emisión</th>
                    <th>Ejercio Fiscal</th>
                    <th>Base Imponible para la Retención</th>
                    <th>IMPUESTO</th>
                    <th>Porcentaje Retención</th>
                    <th>Valor Retenido</th>
                </tr>
            </thead>
            <tbody>
                @foreach($retention_items as $item)
                <tr>
                    <td style="padding: .1em; text-align: left;">
                        {{ $movement->voucher_type_v === 1 ? 'FACTURA' : 'Liquidación en Compras' }}
                    </td>
                    <td style="padding: .1em; text-align: left;">{{ str_replace('-', '', $movement->serie) }}</td>
                    <td style="padding: .1em; text-align: left;">{{ date( "d/m/Y", strtotime( $movement->date_v ) ) }}</td>
                    <td style="padding: .1em; text-align: center;">{{ date( "m/Y", strtotime( $movement->date ) ) }}</td>
                    <td style="padding: .1em; text-align: center;">{{ number_format($item->base, 2) }}</td>
                    <td style="padding: .1em; text-align: center;">{{ $item->code === 2 ? 'IVA' : 'RENTA' }}</td>
                    <td style="padding: .1em; text-align: center;">{{ $item->porcentage }}</td>
                    <td style="padding: .1em; text-align: right;">{{ number_format($item->value, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </td>
</tr>
@endsection