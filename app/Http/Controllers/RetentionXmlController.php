<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\StaticClasses\VoucherStates;
use App\Company;
use App\Shop;

class RetentionXmlController extends Controller
{
    public function downloadXml($id)
    {
        $shop = shop::findOrFail($id);

        return response()->json([
            'xml' => base64_encode(Storage::get($shop->xml_retention))
        ]);
    }

    public function xml($id)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);

        $shop = Shop::join('providers AS p', 'p.id', 'shops.provider_id')
            ->select('p.*', 'shops.*')
            // ->select('p.identication', 'p.name', 'p.address', 'p.phone', 'p.email', 'shops.*')
            ->where('shops.id', $id)
            ->first();

        if ($shop->serie_retencion && $shop->date_retention && $shop->voucher_type < 4) {

            $this->sign($company, $shop, $this->retention($shop, $company));
        }
    }

    private function sign($company, $shop, $str_xml_voucher)
    {
        $file = substr($str_xml_voucher, strpos($str_xml_voucher, '<claveAcceso>') + 13, 49) . '.xml';
        $date = new \DateTime($shop->date_retention);

        $rootfile = 'xmls' . DIRECTORY_SEPARATOR . $company->ruc . DIRECTORY_SEPARATOR .
            $date->format('Y') . DIRECTORY_SEPARATOR .
            $date->format('m');

        $folder = $rootfile . DIRECTORY_SEPARATOR . VoucherStates::SAVED . DIRECTORY_SEPARATOR;

        Storage::put($folder . $file, $str_xml_voucher);

        if (file_exists(Storage::path($folder . $file))) {
            $shop->state_retencion = VoucherStates::SAVED;
            $shop->xml_retention = $folder . $file;
            $shop->save();
        }

        //Signner Start --------------------------
        // Si existe el certificado electronico y se ha creado Xml
        if ($company->cert_dir !== null && file_exists(Storage::path($folder . $file))) {
            // $public_path = '\';
            $public_path = '/var/www/apiteg';
            //Local --------------------------
            // $public_path = 'D:\apps\project\apiaud';

            $cert = Storage::path('cert' . DIRECTORY_SEPARATOR . $company->cert_dir);

            // Si no existe la FIRMADO entonces Crear
            if (!file_exists(Storage::path($rootfile . DIRECTORY_SEPARATOR . 'FIRMADO'))) {
                Storage::makeDirectory($rootfile . DIRECTORY_SEPARATOR . 'FIRMADO');
            }

            // $rootfile = Storage::path($rootfile);
            $newrootfile = Storage::path($rootfile);

            // $java_firma = "java -jar public\Firma\dist\Firma.jar $cert $company->pass_cert $rootfile\\CREADO\\$file $rootfile\\FIRMADO $file";
            $java_firma = "java -jar $public_path/public/Firma/dist/Firma.jar $cert $company->pass_cert $newrootfile/CREADO/$file $newrootfile/FIRMADO $file";

            $variable = system($java_firma);

            // Si se creo el archivo FIRMADO entonces guardar estado FIRMADO Y el nuevo path XML
            if (file_exists(Storage::path($rootfile . DIRECTORY_SEPARATOR . 'FIRMADO' . DIRECTORY_SEPARATOR . $file))) {
                $shop->xml_retention = $rootfile . '/FIRMADO/' . $file;
                $shop->state_retencion = 'FIRMADO';
                $shop->save();
                (new WSSriRetentionController())->sendsri($shop->id);
            }
        }
    }

    private function retention($shop, $company)
    {
        $buyer_id = $shop->identication;
        $string = '';
        $string .= '<?xml version="1.0" encoding="UTF-8"?>';
        $string .= '<comprobanteRetencion id="comprobante" version="1.0.0">';

        $string .= $this->infoTributaria($company, $shop);

        $string .= '<infoCompRetencion>';

        $date = new \DateTime($shop->date_retencion);
        $string .= '<fechaEmision>' . $date->format('d/m/Y') . '</fechaEmision>';
        $string .= '<obligadoContabilidad>' . ($company->accounting ? 'SI' : 'NO') . '</obligadoContabilidad>';
        $string .= '<tipoIdentificacionSujetoRetenido>' . (strlen($buyer_id) === 13 ? '04' : '05') . '</tipoIdentificacionSujetoRetenido>';
        $string .= '<razonSocialSujetoRetenido>' . $shop->name . '</razonSocialSujetoRetenido>';
        $string .= '<identificacionSujetoRetenido>' . $buyer_id . '</identificacionSujetoRetenido>';
        $string .= $shop->address !== null ? '<direccionProveedor>' . $shop->address . '</direccionProveedor>' : null;
        $string .= '<periodoFiscal>' . $date->format('m/Y') . '</periodoFiscal>';

        $string .= '</infoCompRetencion>';

        $string .= '<impuestos>';

        //Replace --- shop items by retencion_items
        $retention_items = $shop->shopretentionitems;

        foreach ($retention_items as $item) {

            $string .= "<impuesto>";
            $string .= "<codigo>$item->code</codigo>";
            $string .= "<codigoRetencion>$item->tax_code</codigoRetencion>";
            $string .= '<baseImponible>' . number_format($item->base, $company->decimal) . '</baseImponible>';
            $string .= "<porcentajeRetener>$item->porcentage</porcentajeRetener>";
            $string .= '<valorRetenido>' . number_format($item->value, $company->decimal) . '</valorRetenido>';
            $string .= "<codDocSustento>01</codDocSustento>"; //01 Facturas
            $string .= "<numDocSustento>" . str_replace('-', '', $shop->serie) . "</numDocSustento>";
            $string .= "<fechaEmisionDocSustento>" . $date->format('d/m/Y') . "</fechaEmisionDocSustento>";
            $string .= "</impuesto>";
        }
        $string .= "</impuestos>";

        $string .= '</comprobanteRetencion>';

        return $string;
    }

    private function infoTributaria($company, $shop)
    {
        $branch = $company->branches->first();

        $voucher_type = '07';

        $serie = str_replace('-', '', $shop->serie_retencion);

        $keyaccess = (new \DateTime($shop->date_retention))->format('dmY') . $voucher_type .
            $company->ruc . $company->enviroment_type . $serie
            . '123456781';

        $string = '';
        $string .= '<infoTributaria>';
        $string .= '<ambiente>' . $company->enviroment_type . '</ambiente>';
        $string .= '<tipoEmision>1</tipoEmision>';
        $string .= '<razonSocial>' . $company->company . '</razonSocial>';
        $string .= $branch->name !== null ? '<nombreComercial>' . $branch->name . '</nombreComercial>' : null;
        $string .= '<ruc>' . $company->ruc . '</ruc>';
        $string .= '<claveAcceso>' . $keyaccess . $this->generaDigitoModulo11($keyaccess) . '</claveAcceso>';
        $string .= '<codDoc>' . $voucher_type . '</codDoc>';
        $string .= '<estab>' . substr($serie, 0, 3) . '</estab>';
        $string .= '<ptoEmi>' . substr($serie, 3, 3) . '</ptoEmi>';
        $string .= '<secuencial>' . substr($serie, 6, 9) . '</secuencial>';
        $string .= '<dirMatriz>' . $branch->address . '</dirMatriz>';
        $string .= (int)$company->micro_business === 1 ? '<regimenMicroempresas>CONTRIBUYENTE RÉGIMEN MICROEMPRESAS</regimenMicroempresas>' : null;
        $string .= (int)$company->retention_agent === 1 ? '<agenteRetencion>1</agenteRetencion>' : null;
        $string .= '</infoTributaria>';

        return $string;
    }

    public function generaDigitoModulo11($cadena)
    {
        $cadena = trim($cadena);
        $baseMultiplicador = 7;
        $aux = new \SplFixedArray(strlen($cadena));
        $aux = $aux->toArray();
        $multiplicador = 2;
        $total = 0;
        $verificador = 0;
        for ($i = count($aux) - 1; $i >= 0; --$i) {
            $aux[$i] = substr($cadena, $i, 1);
            $aux[$i] *= $multiplicador;
            ++$multiplicador;
            if ($multiplicador > $baseMultiplicador) {
                $multiplicador = 2;
            }
            $total += $aux[$i];
        }
        if (($total == 0) || ($total == 1))
            $verificador = 0;
        else {
            $verificador = (11 - ($total % 11) == 11) ? 0 : 11 - ($total % 11);
        }
        if ($verificador == 10) {
            $verificador = 1;
        }
        return $verificador;
    }
}
