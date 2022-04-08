<div style="width: 365px;">
    <div class="parent-img">
        <img src="{{ storage_path('app/logos/' .$company->logo_dir) }}" alt="Logo" style="width: auto; height: 125px;" />
    </div>
    <table style="margin-top: .5em; width: 375px;">
        <tbody class="widthboder">
            <tr>
                <td class="relleno" style="text-align: center;" colspan="3">{{ $company->company }}</td>
            </tr>
            @if($company->branches[0]->name !== null)
            <tr>
                <th class="relleno" colspan="3">{{ $company->branches[0]->name }}</th>
            </tr>
            @endif
            <tr>
                <td class="relleno">Dirección matriz</td>
                <td class="align-middle" colspan="2">{{ $company->branches[0]->address }}</td>
            </tr>
            <tr>
                <td class="relleno" colspan="2">Obligado a llevar contabilidad</td>
                <td class="align-middle">{{ $company->accounting ? 'SI' : 'NO' }}</td>
            </tr>
            @if($movement->voucher_type!==4)

            @if($company->micro_business)
            <tr>
                <td style="text-align: left;" class="relleno" colspan="3">CONTRIBUYENTE RÉGIMEN MICROEMPRESAS</td>
            </tr>
            @endif
            @if($company->retention_agent)
            <tr>
                <td class="relleno" colspan="2">Agente de Retención Resolución No.</td>
                <td class="align-middle">{{ $company->retention_agent }}</td>
            </tr>
            @endif
            @if($company->rimpe)
            <tr>
                <td style="text-align: left;" class="relleno" colspan="3">CONTRIBUYENTE RÉGIMEN RIMPE</td>
            </tr>
            @endif

            @endif
        </tbody>
    </table>
</div>