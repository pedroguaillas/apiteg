@extends('vouchers.theme.voucher')

@section('body')
<div style="padding-top: .5em;">
    <table style="width: 725px;" class="table table-sm">
        <tbody class="widthboder">
            <tr>
                <td class="relleno">Razón Social / Nombres Y Apellidos: {{ $movement->company }}</td>
            </tr>
            <tr>
                <td class="relleno">Identificación: {{ $movement->ruc }}</td>
            </tr>
            <tr>
                <td style="padding-bottom: 0;" class="relleno">Fecha de Emisión: {{ $movement->date }}</td>
            </tr>
            <tr>
                <td style="padding-top: 0;" class="relleno"> _________________________________________________________________________________________________________ </td>
            </tr>
            <tr>
                <td class="relleno">Comprobante que se modifica: Factura {{ $invoice->serie }}</td>
            </tr>
            <tr>
                <td class="relleno">Identificación: {{ $movement->ruc }}</td>
            </tr>
            <tr>
                <td class="relleno">Fecha de Emisión: {{ $invoice->date }}</td>
            </tr>
        </tbody>
    </table>
</div>
<div style="padding-top: .5em;">
    <table style="width: 725px; border-radius: 10px;" class="table-collapse">
        <thead>
            <tr>
                <th style="width: 5em;">Cod. Principal</th>
                <th style="width: 4em;">Cant.</th>
                <th>Descripción</th>
                <th style="width: 5em;">Precio Unitario</th>
                <th style="width: 5em;">Descuento</th>
                <th style="width: 5em;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($movement_items as $item)
            <tr>
                <td style="padding: .5em; text-align: center;">{{ $item->code }}</td>
                <td style="padding: .5em; text-align: center;">{{ $item->quantity }}</td>
                <td style="padding: .5em;">{{ $item->name }}</td>
                <td style="padding: .5em; text-align: right;">{{ number_format($item->price, 2) }}</td>
                <td style="padding: .5em; text-align: right;">{{ number_format($item->discount, 2) }}</td>
                <td style="padding: .5em; text-align: right;">{{ number_format($item->quantity * $item->price, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@section('footer')
<table>
    <tbody>
        <tr>
            <td>
                @include('vouchers.theme.additionalinformation')
            </td>
            <td>@include('vouchers.theme.total')</td>
        </tr>
    </tbody>
</table>
@endsection