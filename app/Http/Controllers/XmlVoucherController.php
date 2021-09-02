<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use App\MovementItem;
use App\Movement;
use App\Company;
use App\Retention;
use App\RetentionItem;
use App\StaticClasses\VoucherStates;
use App\Voucher;

class XmlVoucherController extends Controller
{
    public function downloadXml($id_voucher)
    {
        $voucher = Voucher::where('movement_id', $id_voucher)->get()->first();

        return response()->json([
            'xml' => base64_encode(Storage::get($voucher->xml))
        ]);
    }

    public function xml($id)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);

        $sale = $this->query_sale($id);

        $sale_items = MovementItem::join('products', 'movement_items.product_id', 'products.id')
            ->where('movement_id', $id)
            ->get();

        $str_xml_voucher = null;

        // Ventas
        if ($sale->type === 2) {
            switch ($sale->voucher_type) {
                case 1:
                    $str_xml_voucher = $this->invoice($sale, $company, $sale_items);
                    break;
                case 4:
                    $str_xml_voucher = $this->creditNote($sale, $company, $sale_items);
                    break;
                case 5:
                    // Nota de credito generar xml
                    $str_xml_voucher = $this->creditNote($sale, $company, $sale_items);
                    break;
            }
            // Compras
        } else {
            if ($sale->voucher_type === 1 || $sale->voucher_type === 3) {
                if ($sale->voucher_type === 3) {
                    $str_xml_voucher = $this->purchasesettlement($sale, $company, $sale_items);
                }
                // Genera xml y firma la retencion
                $this->xmlRetention($sale, $company);
            }
        }

        $this->sign($company, $sale, $str_xml_voucher);

        if ($company->cert_dir !== null) {
            // Sended Start ---------------------------------------
            (new WSSriController())->sendVoucher($id, $company->enviroment_type);
            // Sended End ---------------------------------------
        }
    }

    public function xmlRetention($sale, $company)
    {
        $str_xml_voucher = $this->retention($sale, $company);

        if ($str_xml_voucher !== null) {
            $this->sign($company, $sale, $str_xml_voucher, true);
        }
    }

    private function query_sale($id)
    {
        return Movement::join('vouchers', 'movements.id', 'vouchers.movement_id')
            ->join('contacts', 'contacts.id', 'vouchers.contact_id')
            ->select(
                'movements.date',
                'movements.sub_total',
                'movements.type',
                'vouchers.*',
                'contacts.identication_card',
                'contacts.ruc',
                'contacts.company',
                'contacts.name',
                'contacts.email',
                'contacts.accounting',
                'contacts.address'
            )
            ->where('movements.id', $id)
            ->get()->first();
    }

    private function sign($company, $sale, $str_xml_voucher, $in_taxs = false)
    {
        $file = substr($str_xml_voucher, strpos($str_xml_voucher, '<claveAcceso>') + 13, 49) . '.xml';
        $date = new \DateTime($sale->date);

        $retention = null;
        if ($in_taxs) {
            $retention = Retention::where('vaucher_id', $sale->movement_id)->get()->first();
        }

        $rootfile = 'xmls' . DIRECTORY_SEPARATOR . $company->ruc . DIRECTORY_SEPARATOR .
            $date->format('Y') . DIRECTORY_SEPARATOR .
            $date->format('m');

        $folder = $rootfile . DIRECTORY_SEPARATOR . VoucherStates::SAVED . DIRECTORY_SEPARATOR;

        Storage::put($folder . $file, $str_xml_voucher);

        if (file_exists(Storage::path($folder . $file))) {

            $datas = ['xml' => $folder . $file];

            if ($in_taxs) {
                Retention::where('vaucher_id', $sale->movement_id)
                    ->update($datas);
            } else {
                Voucher::where('movement_id', $sale->movement_id)
                    ->update($datas);
            }
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
                $rootfile = $rootfile . '/FIRMADO/' . $file;

                $datas = [
                    'state' => 'FIRMADO',
                    'xml' => $rootfile
                ];

                if ($in_taxs) {
                    Retention::where('vaucher_id', $sale->movement_id)
                        ->update($datas);
                } else {
                    Voucher::where('movement_id', $sale->movement_id)
                        ->update($datas);
                }
            }
        }
    }

    private function retention($sale, $company)
    {
        //Replace --- sale items by retencion_items
        $retention = Retention::where('vaucher_id', $sale->movement_id)->get();

        if (count($retention) > 0) {
            $retention = $retention->first();
        } else {
            return null;
        }

        $buyer_id = strlen($sale->ruc) ? $sale->ruc : $sale->identication_card;
        $string = '';
        $string .= '<?xml version="1.0" encoding="UTF-8"?>';
        $string .= '<comprobanteRetencion id="comprobante" version="1.0.0">';

        $string .= $this->infoTributaria($company, $sale, '07');

        $string .= '<infoCompRetencion>';

        $date = new \DateTime($retention->date);
        $string .= '<fechaEmision>' . $date->format('d/m/Y') . '</fechaEmision>';
        $string .= '<obligadoContabilidad>' . ($company->accounting ? 'SI' : 'NO') . '</obligadoContabilidad>';
        $string .= '<tipoIdentificacionSujetoRetenido>' . (strlen($buyer_id) === 13 ? '04' : '05') . '</tipoIdentificacionSujetoRetenido>';
        $string .= '<razonSocialSujetoRetenido>' . $sale->company . '</razonSocialSujetoRetenido>';
        $string .= '<identificacionSujetoRetenido>' . $buyer_id . '</identificacionSujetoRetenido>';
        $string .= '<direccionProveedor>' . $sale->address . '</direccionProveedor>';
        $string .= '<periodoFiscal>' . $date->format('m/Y') . '</periodoFiscal>';

        $string .= '</infoCompRetencion>';

        $string .= '<impuestos>';

        //Replace --- sale items by retencion_items
        $retention_items = RetentionItem::where('retention_id', $sale->movement_id)->get();

        foreach ($retention_items as $item) {

            $string .= "<impuesto>";
            $string .= "<codigo>$item->code</codigo>";
            $string .= "<codigoRetencion>$item->tax_code</codigoRetencion>";
            $string .= '<baseImponible>' . number_format($item->base, $company->decimal) . '</baseImponible>';
            $string .= "<porcentajeRetener>$item->porcentage</porcentajeRetener>";
            $string .= '<valorRetenido>' . number_format($item->value, $company->decimal) . '</valorRetenido>';
            $string .= "<codDocSustento>01</codDocSustento>"; //01 Facturas
            $string .= "<numDocSustento>" . str_replace('-', '', $sale->serie) . "</numDocSustento>";
            $string .= "<fechaEmisionDocSustento>" . $date->format('d/m/Y') . "</fechaEmisionDocSustento>";
            $string .= "</impuesto>";
        }
        $string .= "</impuestos>";

        $string .= '</comprobanteRetencion>';

        return $string;
    }

    private function purchasesettlement($sale, $company, $sale_items)
    {
        // $buyer_id = strlen($sale->ruc) ? $sale->ruc : $sale->identication_card;
        $buyer_id = $sale->identication_card;
        $string = '';
        $string .= '<?xml version="1.0" encoding="UTF-8"?>';
        // return $string;
        $string .= '<liquidacionCompra id="comprobante" version="1.' . ($company->decimal > 2 ? 1 : 0) . '.0">';

        $string .= $this->infoTributaria($company, $sale);

        $string .= '<infoLiquidacionCompra>';

        $date = new \DateTime($sale->date);
        $string .= '<fechaEmision>' . $date->format('d/m/Y') . '</fechaEmision>';
        $string .= '<obligadoContabilidad>' . ($company->accounting ? 'SI' : 'NO') . '</obligadoContabilidad>';
        $string .= '<tipoIdentificacionProveedor>' . (strlen($buyer_id) === 13 ? '04' : '05') . '</tipoIdentificacionProveedor>';
        $string .= '<razonSocialProveedor>' . $sale->company . '</razonSocialProveedor>';
        $string .= '<identificacionProveedor>' . $buyer_id . '</identificacionProveedor>';
        $string .= '<direccionProveedor>' . $sale->address . '</direccionProveedor>';
        $string .= '<totalSinImpuestos>' . $sale->sub_total . '</totalSinImpuestos>';
        $string .= '<totalDescuento>' . $sale->discount . '</totalDescuento>';

        // Aplied only tax to IVA, NOT aplied to IRBPNR % Imp. al Cons Esp, require add
        $string .= '<totalConImpuestos>';
        foreach ($this->grupingTaxes($sale_items) as $tax) {
            $string .= "<totalImpuesto>";
            $string .= "<codigo>2</codigo>";    // Aplied only tax to IVA
            $string .= "<codigoPorcentaje>" . $tax->percentageCode . "</codigoPorcentaje>";
            $string .= "<baseImponible>" . round($tax->base, 2) . "</baseImponible>";
            $string .= "<tarifa>" . $tax->percentage . "</tarifa>";
            $string .= "<valor>" . $tax->value . "</valor>";
            $string .= "</totalImpuesto>";
        }
        $string .= '</totalConImpuestos>';

        $string .= '<importeTotal>' . round($sale->total, 2) . '</importeTotal>';
        $string .= '<moneda>DOLAR</moneda>';

        $string .= '<pagos>';
        $string .= '<pago>';
        $string .= '<formaPago>20</formaPago>';
        $string .= '<total>' . $sale->total . '</total>';
        $string .= '<plazo>' . $sale->total . '</plazo>';
        $string .= '</pago>';
        $string .= '</pagos>';
        $string .= '</infoLiquidacionCompra>';

        $string .= '<detalles>';
        foreach ($sale_items as $detail) {
            $sub_total = $detail->quantity * $detail->price;
            $discount = round($sub_total * $detail->discount * .01, 2);
            $total = $sub_total - $discount;
            $percentage = $detail->iva === 2 ? 12 : 0;

            $string .= "<detalle>";

            $string .= "<codigoPrincipal>" . $detail->code . "</codigoPrincipal>";
            $string .= "<codigoAuxiliar>" . $detail->code . "</codigoAuxiliar>";
            $string .= "<descripcion>" . $detail->name . "</descripcion>";
            $string .= "<cantidad>" . round($detail->quantity, $company->decimal) . "</cantidad>";
            $string .= "<precioUnitario>" . round($detail->price, $company->decimal) . "</precioUnitario>";
            $string .= "<descuento>" . $detail->discount . "</descuento>";
            $string .= "<precioTotalSinImpuesto>" . round($sub_total, 2) . "</precioTotalSinImpuesto>";

            $string .= "<impuestos>";
            // foreach ($this->taxes as $tax) {
            $string .= "<impuesto>";
            $string .= "<codigo>2</codigo>";
            $string .= "<codigoPorcentaje>" . $detail->iva . "</codigoPorcentaje>";
            $string .= "<tarifa>" . ($detail->iva === 2 ? 12 : 0) . "</tarifa>";
            $string .= "<baseImponible>" . round($total, 2) . "</baseImponible>";
            $string .= "<valor>" . round($percentage * $total * .01, 2) . "</valor>";
            $string .= "</impuesto>";
            // }
            $string .= "</impuestos>";

            $string .= "</detalle>";
        }
        $string .= '</detalles>';

        // $string .= '<infoAdicional>';
        // $string .= '<campoAdicional nombre="Dirección">' . $sale->address . '</campoAdicional>';
        // $string .= '<campoAdicional nombre="Email">' . $sale->email . '</campoAdicional>';
        // $string .= '</infoAdicional>';
        $string .= '</liquidacionCompra>';

        return $string;
    }

    private function creditNote($sale, $company, $sale_items)
    {
        $buyer_id = strlen($sale->ruc) ? $sale->ruc : $sale->identication_card;
        $string = '';
        $string .= '<?xml version="1.0" encoding="UTF-8"?>';
        $string .= '<notaCredito id="comprobante" version="1.' . ($company->decimal > 2 ? 1 : 0) . '.0">';

        $string .= $this->infoTributaria($company, $sale);

        $string .= '<infoNotaCredito>';

        $date = new \DateTime($sale->date);
        $string .= '<fechaEmision>' . $date->format('d/m/Y') . '</fechaEmision>';
        $string .= '<obligadoContabilidad>' . ($company->accounting ? 'SI' : 'NO') . '</obligadoContabilidad>';
        $string .= '<tipoIdentificacionComprador>' . (strlen($buyer_id) === 13 ? '04' : '05') . '</tipoIdentificacionComprador>';
        $string .= '<razonSocialComprador>' . $sale->company . '</razonSocialComprador>';
        $string .= '<identificacionComprador>' . $buyer_id . '</identificacionComprador>';
        $string .= '<direccionComprador>' . $sale->address . '</direccionComprador>';

        // Only Credit Note Start ................................

        $invoice = Movement::select('date', 'serie')
            ->join('vouchers', 'vouchers.movement_id', 'movements.id')
            ->where('movements.id', $sale->doc_realeted)
            ->first();

        $string .= '<codDocModificado>01</codDocModificado>';
        $string .= "<numDocModificado>$invoice->serie</numDocModificado>";
        $string .= "<fechaEmisionDocSustento>$invoice->date</fechaEmisionDocSustento>";
        // Only Credit Note End ..................................

        $string .= '<totalSinImpuestos>' . $sale->sub_total . '</totalSinImpuestos>';
        // $string .= '<totalDescuento>' . $sale->discount . '</totalDescuento>';

        // Aplied only tax to IVA, NOT aplied to IRBPNR % Imp. al Cons Esp, require add
        $string .= '<totalConImpuestos>';
        foreach ($this->grupingTaxes($sale_items) as $tax) {
            $string .= "<totalImpuesto>";
            $string .= "<codigo>2</codigo>";    // Aplied only tax to IVA
            $string .= "<codigoPorcentaje>" . $tax->percentageCode . "</codigoPorcentaje>";
            $string .= "<baseImponible>" . $tax->base . "</baseImponible>";
            $string .= "<tarifa>" . $tax->percentage . "</tarifa>";
            $string .= "<valor>" . $tax->value . "</valor>";
            $string .= "</totalImpuesto>";
        }
        $string .= '</totalConImpuestos>';

        $string .= '<propina>0</propina>';
        $string .= '<importeTotal>' . round($sale->total, 2) . '</importeTotal>';
        $string .= '<moneda>DOLAR</moneda>';

        $string .= '<pagos>';
        $string .= '<pago>';
        $string .= '<formaPago>01</formaPago>';
        $string .= '<total>' . $sale->total . '</total>';
        $string .= '</pago>';
        $string .= '</pagos>';

        $string .= '</infoNotaCredito>';

        $string .= '<detalles>';
        foreach ($sale_items as $detail) {
            // $string .= $detail->__toXml();
            $sub_total = $detail->quantity * $detail->price;
            $discount = round($sub_total * $detail->discount * .01, 2);
            $total = $sub_total - $discount;
            $percentage = $detail->iva === 2 ? 12 : 0;

            $string .= "<detalle>";

            $string .= "<codigoPrincipal>" . $detail->code . "</codigoPrincipal>";
            $string .= "<codigoAuxiliar>" . $detail->code . "</codigoAuxiliar>";
            $string .= "<descripcion>" . $detail->name . "</descripcion>";
            $string .= "<cantidad>" . round($detail->quantity, $company->decimal) . "</cantidad>";
            $string .= "<precioUnitario>" . round($detail->price, $company->decimal) . "</precioUnitario>";
            $string .= "<descuento>" . $detail->discount . "</descuento>";
            $string .= "<precioTotalSinImpuesto>" . round($sub_total, 2) . "</precioTotalSinImpuesto>";

            $string .= "<impuestos>";
            // foreach ($this->taxes as $tax) {
            $string .= "<impuesto>";
            $string .= "<codigo>2</codigo>";
            $string .= "<codigoPorcentaje>" . $detail->iva . "</codigoPorcentaje>";
            $string .= "<tarifa>" . ($detail->iva === 2 ? 12 : 0) . "</tarifa>";
            $string .= "<baseImponible>" . $total . "</baseImponible>";
            $string .= "<valor>" . round($percentage * $total * .01, 2) . "</valor>";
            $string .= "</impuesto>";
            // }
            $string .= "</impuestos>";

            $string .= "</detalle>";
        }
        $string .= '</detalles>';

        // $string .= '<infoAdicional>';
        // $string .= '<campoAdicional nombre="Dirección">' . $sale->address . '</campoAdicional>';
        // $string .= '<campoAdicional nombre="Email">' . $sale->email . '</campoAdicional>';
        // $string .= '</infoAdicional>';
        $string .= '</notaCredito>';

        return $string;
    }

    private function invoice($sale, $company, $sale_items)
    {
        $buyer_id = strlen($sale->ruc) ? $sale->ruc : $sale->identication_card;
        $string = '';
        $string .= '<?xml version="1.0" encoding="UTF-8"?>';
        $string .= '<factura id="comprobante" version="1.' . ($company->decimal > 2 ? 1 : 0) . '.0">';

        $string .= $this->infoTributaria($company, $sale);

        $string .= '<infoFactura>';

        $date = new \DateTime($sale->date);
        $string .= '<fechaEmision>' . $date->format('d/m/Y') . '</fechaEmision>';
        $string .= '<obligadoContabilidad>' . ($company->accounting ? 'SI' : 'NO') . '</obligadoContabilidad>';
        $string .= '<tipoIdentificacionComprador>' . (strlen($buyer_id) === 13 ? '04' : '05') . '</tipoIdentificacionComprador>';
        $string .= '<razonSocialComprador>' . $sale->company . '</razonSocialComprador>';
        $string .= '<identificacionComprador>' . $buyer_id . '</identificacionComprador>';
        $string .= '<direccionComprador>' . $sale->address . '</direccionComprador>';
        $string .= '<totalSinImpuestos>' . $sale->sub_total . '</totalSinImpuestos>';
        $string .= '<totalDescuento>' . $sale->discount . '</totalDescuento>';

        // Aplied only tax to IVA, NOT aplied to IRBPNR % Imp. al Cons Esp, require add
        $string .= '<totalConImpuestos>';
        foreach ($this->grupingTaxes($sale_items) as $tax) {
            $string .= "<totalImpuesto>";
            $string .= "<codigo>2</codigo>";    // Aplied only tax to IVA
            $string .= "<codigoPorcentaje>" . $tax->percentageCode . "</codigoPorcentaje>";
            $string .= "<baseImponible>" . round($tax->base, 2) . "</baseImponible>";
            $string .= "<tarifa>" . $tax->percentage . "</tarifa>";
            $string .= "<valor>" . $tax->value . "</valor>";
            $string .= "</totalImpuesto>";
        }
        $string .= '</totalConImpuestos>';

        $string .= '<propina>0</propina>';
        $string .= '<importeTotal>' . round($sale->total, 2) . '</importeTotal>';
        $string .= '<moneda>DOLAR</moneda>';

        $string .= '<pagos>';
        $string .= '<pago>';
        $string .= '<formaPago>20</formaPago>';
        $string .= '<total>' . $sale->total . '</total>';
        $string .= '</pago>';
        $string .= '</pagos>';

        $string .= '</infoFactura>';

        $string .= '<detalles>';
        foreach ($sale_items as $detail) {
            $sub_total = $detail->quantity * $detail->price;
            $discount = round($sub_total * $detail->discount * .01, 2);
            $total = round($sub_total - $discount, 2);
            $percentage = $detail->iva === 2 ? 12 : 0;

            $string .= "<detalle>";

            $string .= "<codigoPrincipal>" . $detail->code . "</codigoPrincipal>";
            $string .= "<codigoAuxiliar>" . $detail->code . "</codigoAuxiliar>";
            $string .= "<descripcion>" . $detail->name . "</descripcion>";
            $string .= "<cantidad>" . round($detail->quantity, $company->decimal) . "</cantidad>";
            $string .= "<precioUnitario>" . round($detail->price, $company->decimal) . "</precioUnitario>";
            $string .= "<descuento>" . $detail->discount . "</descuento>";
            $string .= "<precioTotalSinImpuesto>" . round($sub_total, 2) . "</precioTotalSinImpuesto>";

            $string .= "<impuestos>";
            // foreach ($this->taxes as $tax) {
            $string .= "<impuesto>";
            $string .= "<codigo>2</codigo>";
            $string .= "<codigoPorcentaje>" . $detail->iva . "</codigoPorcentaje>";
            $string .= "<tarifa>" . ($detail->iva === 2 ? 12 : 0) . "</tarifa>";
            $string .= "<baseImponible>" . round($total, 2) . "</baseImponible>";
            $string .= "<valor>" . round($percentage * $total * .01, 2) . "</valor>";
            $string .= "</impuesto>";
            // }
            $string .= "</impuestos>";

            $string .= "</detalle>";
        }
        $string .= '</detalles>';

        $string .= '</factura>';

        return $string;
    }

    public function grupingTaxes($sale_items)
    {
        $taxes = array();
        foreach ($sale_items as $tax) {
            $sub_total = $tax->quantity * $tax->price;
            $discount = round($sub_total * $tax->discount * .01, 2);
            $total = $sub_total - $discount;
            $percentage = $tax->iva === 2 ? 12 : 0;

            $gruping = $this->grupingExist($taxes, $tax);
            if ($gruping !== -1) {
                $aux2 = $taxes[$gruping];
                $aux2->base += $total;
                $aux2->value += round($percentage * $total * .01, 2);
            } else {
                $aux = [
                    'percentageCode' => $tax->iva,
                    'percentage' => $percentage,
                    'base' => $total,
                    'value' => round($percentage * $total * .01, 2)
                ];
                $aux = json_encode($aux);
                $aux = json_decode($aux);
                $taxes[] = $aux;
            }
        }

        return $taxes;
    }

    private function grupingExist($taxes, $tax)
    {
        $result = -1;
        $i = 0;
        while ($i < count($taxes) && $result == -1) {
            if (
                $taxes[$i]->percentageCode === $tax->iva
                // && $taxes[$i]->percentage === $tax->percentage
            ) {
                $result = $i;
            }
            $i++;
        }
        return $result;
    }

    private function infoTributaria($company, $sale, $voucher_type = NULL)
    {
        $branch = $company->branches->first();
        $retention = null;
        $serie = $sale->serie;
        $date = $sale->date;

        if ($voucher_type === NULL) {
            $voucher_type = str_pad($sale->voucher_type, 2, '0', STR_PAD_LEFT);
        } else {
            $retention = Retention::where('vaucher_id', $sale->movement_id)->get()->first();
            $serie = $retention->serie;
            $date = $retention->date;
        }

        $serie = str_replace('-', '', $serie);

        $keyaccess = (new \DateTime($date))->format('dmY') . $voucher_type .
            $company->ruc . $company->enviroment_type . str_replace('-', '', $serie)
            . '123456781';

        $string = '';
        $string .= '<infoTributaria>';
        $string .= '<ambiente>' . $company->enviroment_type . '</ambiente>';
        $string .= '<tipoEmision>1</tipoEmision>';
        $string .= '<razonSocial>' . $company->company . '</razonSocial>';
        $string .= $branch->name !== null ? '<nombreComercial>' . $branch->name . '</nombreComercial>' : null;
        // $string .= '<nombreComercial>' . $branch->name . '</nombreComercial>';
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

    // private function generateRandomNumericCode()
    // {
    //     for ($i = 0; $i < 8; $i++) {
    //         $numericCode[$i] = rand(0, 9);
    //     }

    //     return implode($numericCode);
    // }

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
