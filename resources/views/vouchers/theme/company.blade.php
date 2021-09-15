<div style="margin-bottom: 0; width: 350px; padding-top: 2em;">
    <img style="display: table;" src="{{ storage_path('app/logos/' .$company->logo_dir) }}" alt="Logo" style="width: 250px; height: 125px;" />
    <table>
        <tbody class="widthboder">
            <tr>
                <th class="relleno" colspan="2">{{ $company->company }}</th>
            </tr>
            <tr>
                <td class="relleno">Dirección matriz</td>
                <td class="align-middle">{{ $company->branches[0]->address }}</td>
            </tr>
            <tr>
                <td class="relleno">Dirección sucursal</td>
                <td class="align-middle">{{ $company->branches[0]->address }}</td>
            </tr>
            <tr>
                <td class="relleno">Obligado a llevar contabilidad</td>
                <td class="align-middle">{{ $company->accounting ? 'SI' : 'NO' }}</td>
            </tr>
        </tbody>
    </table>
</div>