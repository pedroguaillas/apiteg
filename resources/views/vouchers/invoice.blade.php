@extends('vouchers.theme.voucher')

@section('body')
<div style="padding-top: .5em;">
    <table style="width: 725px;" class="table table-sm">
        <tbody class="widthboder">
            <tr>
                <td class="relleno">Razón Social / Nombres Y Apellidos: {{ $movement->company }}</td>
                <td class="align-middle">Identificación: {{ $movement->ruc }}</td>
            </tr>
            <tr>
                <td class="relleno">Fecha de Emisión: {{ $movement->date }}</td>
                <!-- <td class="align-middle">Guía de Remisión: GUÍA DE REMISIÓN</td> -->
            </tr>
            <tr>
                <td class="relleno" colspan="2">Dirección: {{ $movement->address }}</td>
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
                <td style="padding: .5em; text-align: center;">{{ number_format($item->quantity, $company->decimal) }}</td>
                <td style="padding: .5em;">{{ $item->name }}</td>
                <td style="padding: .5em; text-align: right;">{{ number_format($item->price, $company->decimal) }}</td>
                <td style="padding: .5em; text-align: right;">{{ number_format($item->discount, $company->decimal) }}</td>
                <td style="padding: .5em; text-align: right;">{{ number_format($item->quantity * $item->price, $company->decimal) }}</td>
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
                @include('vouchers.theme.payment')
            </td>
            <td>@include('vouchers.theme.total')</td>
        </tr>
    </tbody>
</table>
@endsection