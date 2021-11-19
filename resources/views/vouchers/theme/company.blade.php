<div style="width: 360px; margin-top: 1.5em;">
    <img src="{{ storage_path('app/logos/' .$company->logo_dir) }}" alt="Logo" style="width: auto; height: 125px;" />
    <table>
        <tbody class="widthboder">
            <tr>
                <th class="relleno" colspan="3">{{ $company->company }}</th>
            </tr>
            <tr>
                <td class="relleno">Dirección matriz</td>
                <td class="align-middle" colspan="2">{{ $company->branches[0]->address }}</td>
            </tr>
            <tr>
                <td class="relleno">Dirección sucursal</td>
                <td class="align-middle" colspan="2">{{ $company->branches[0]->address }}</td>
            </tr>
            <tr>
                <td class="relleno" colspan="2">Obligado a llevar contabilidad</td>
                <td class="align-middle">{{ $company->accounting ? 'SI' : 'NO' }}</td>
            </tr>
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
        </tbody>
    </table>
</div>