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
                        @if($movement->guia)
                        <td class="align-middle">Guia de Remisión: {{ $movement->guia }}</td>
                        @endif
                    </tr>
                    <tr>
                        <td class="relleno" colspan="2">Dirección: {{ $movement->address }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </td>
</tr>
<tr>
    <td colspan="2">
        <table style="width: 100%; border-radius: 10px; margin-top: .5em;" class="table-collapse">
            <tbody>
                <tr>
                    <th style="width: 5em;">Cod. Principal</th>
                    <th style="width: 4em;">Cant.</th>
                    <th>Descripción</th>
                    <th style="width: 5em;">Precio Unitario</th>
                    <th style="width: 5em;">Descuento</th>
                    <th style="width: 5em;">Subtotal</th>
                </tr>
            </tbody>
        </table>
    </td>
</tr>
@foreach($movement_items as $item)
<tr>
    <td colspan="2">
        <table style="width: 100%;" class="table-collapse">
            <tbody>
                <tr>
                    <td style="text-align: center; width: 5em;">{{ $item->code }}</td>
                    <td style="text-align: center; width: 4em;">{{ number_format($item->quantity, $company->decimal) }}</td>
                    <td>{{ $item->name }}</td>
                    <td style="text-align: right; width: 5em;">{{ number_format($item->price, $company->decimal) }}</td>
                    <td style="text-align: right; width: 5em;">{{ number_format($item->discount, 2) }}</td>
                    <td style="text-align: right; width: 5em;">{{ number_format($item->quantity * $item->price, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </td>
</tr>
@endforeach
@endsection

@section('footer')
<table style="width: 100%;">
    <tbody>
        <tr>
            <td>
                <!-- @include('vouchers.theme.additionalinformation') -->
                @include('vouchers.theme.payment')
            </td>
            <td>@include('vouchers.theme.total')</td>
        </tr>
    </tbody>
</table>
@endsection