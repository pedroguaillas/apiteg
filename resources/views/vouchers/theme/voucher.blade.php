<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
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
    </title>
    <style>
        * {
            box-sizing: border-box;
            padding: 0;
            margin: 0;
        }

        @page {
            margin: 5em 2.5em 3em;
            font-size: 12px;
            font-family: sans-serif;
        }

        .widthboder {
            border: 2px solid #888;
            border-radius: 10px;
        }

        .relleno {
            padding: .5em;
        }

        .m-1 {
            margin: 1em;
        }

        .m-2 {
            margin: 2em;
        }

        .mb-0 {
            margin-bottom: 0;
        }

        .table-collapse {
            border-collapse: collapse;
        }

        .table-collapse th,
        .table-collapse td {
            border: 1px solid #888;
            padding: 5px;
        }
    </style>
</head>

<body>
    <table class="table table-borderless m-2">
        <tbody>
            <tr>
                <td class="mb-0">@include('vouchers.theme.company')</td>
                <td>@include('vouchers.theme.information')</td>
            </tr>
            <tr>
                <td colspan="2">@yield('body')</td>
            </tr>
            <tr>
                <td colspan="2">@yield('footer')</td>
            </tr>
        </tbody>
    </table>
</body>

</html>