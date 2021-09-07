<div style="padding: 2px 2px 0px 2px" class="card border-dark">
    <table class="table table-sm">
        <tbody class="widthboder">
            <tr style="font-size: 16px;">
                <td class="relleno">R.U.C.:</td>
                <td class="align-middle">{{ $company->ruc }}</td>
            </tr>
            <tr>
                <th style="font-size: 16px; letter-spacing: 2px;" class="align-middle" colspan="2">
                    @switch($movement->voucher_type)
                    @case(1)
                    FACTURA
                    @break

                    @case(3)
                    LIQUIDACION EN COMPRA
                    @break

                    @case(4)
                    NOTA DE CRÉDITO
                    @break

                    @case(5)
                    NOTA DE DÉDITO
                    @break

                    @case(7)
                    RETENCION
                    @break

                    @default
                    OTROS
                    @endswitch
                </th>
            </tr>
            <tr>
                <td class="relleno">No.</td>
                <td class="align-middle">{{ $movement->serie }}</td>
            </tr>
            <tr>
                <td class="relleno" colspan="2">NÚMERO DE AUTORIZACIÓN</td>
            </tr>
            <tr>
                <td class="relleno" colspan="2">{{ $company->authorization }}</td>
            </tr>
            <tr>
                <td class="relleno">FECHA Y HORA DE AUTORIZACIÓN: </td>
                <td class="align-middle">{{ $movement->autorized }}</td>
            </tr>
            <tr>
                <td class="relleno">AMBIENTE:</td>
                <td class="align-middle">{{ (int)substr($movement->xml, -30, 1) === 1 ? 'PRUEBAS' : 'PRODUCCION' }}</td>
            </tr>
            <tr>
                <td class="relleno">EMISIÓN:</td>
                <td class="align-middle">NORMAL</td>
            </tr>
            <tr>
                <td class="relleno" colspan="2">CLAVE DE ACCESO</td>
            </tr>
            <tr>
                <td class="relleno" colspan="2">
                    Código de barra
                </td>
            </tr>
        </tbody>
    </table>
</div>