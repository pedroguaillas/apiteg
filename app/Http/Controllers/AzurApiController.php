<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\StaticClasses\VoucherStates;
use Illuminate\Support\Facades\Storage;
use App\MovementItem;
use App\Company;
use App\Voucher;
use App\Contact;

class AzurApiController extends Controller
{
    public function index($id)
    {
        $voucher = Voucher::join('movements', 'vouchers.movement_id', 'movements.id')
            ->select('movements.date', 'vouchers.serie', 'vouchers.contact_id', 'vouchers.voucher_type')
            ->where('movements.id', $id)
            ->first();

        $contact = Contact::findOrFail($voucher->contact_id);

        $movement_items = MovementItem::join('products', 'products.id', 'movement_items.product_id')
            ->select(
                'products.code AS codigo_principal',
                'products.name AS descripcion',
                'products.type_product AS tipoproducto',
                'products.iva AS tipo_iva',
                'movement_items.price AS precio_unitario',
                'movement_items.quantity AS cantidad',
                'movement_items.discount AS descuento'
            )
            ->where('movement_id', $id)
            ->get();

        $comprobante = [
            'api_key' => 'API_1723_1918_5f677b97929c3',
            'codigoDoc' => str_pad($voucher->voucher_type, 2, '0', STR_PAD_LEFT),
            'emisor' => [
                'manejo_interno_secuencia' => 'NO',
                'secuencial' => substr($voucher->serie, 8, 9),
                'fecha_emision' => (new \DateTime($voucher->date))->format('Y/m/d')
            ],
            'comprador' => [
                'tipo_identificacion' => '04',
                'identificacion' => $contact->ruc,
                'razon_social' => $contact->company,
                'direccion' => $contact->address
            ],
            'items' => json_decode(json_encode($movement_items))
        ];

        return $this->send($comprobante, $id);
    }

    private function send($comprobante, $id)
    {
        switch ($comprobante['codigoDoc']) {
            case '01':
                $url = 'https://azur.com.ec/plataforma/api/v2/factura/emision';
                break;
            case '04':
                $url = 'https://azur.com.ec/plataforma/api/v2/credito/emision';
                break;
            case '05':
                $url = 'https://azur.com.ec/plataforma/api/v2/debito/emision';
                break;
        }

        $postdata = http_build_query($comprobante);

        $opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $postdata
        ));

        $context = stream_context_create($opts);

        // Response
        $result = file_get_contents($url, false, $context);

        $result = json_decode($result);

        $voucher = Voucher::where('movement_id', $id)->get()->first();

        if ($result->creado) {
            $voucher->state = VoucherStates::SENDED;
        } else {
            var_dump('ER-CREADO');
            $voucher->state = VoucherStates::RETURNED;
            $voucher->error = $result->errors->error;
        }

        $voucher->xml = $result->claveacceso;
        $voucher->save();

        return $result;

        if ($voucher->state === 'ENVIADO') {
            $this->authorizevoucher($id);
        }
    }

    public function authorizevoucher($voucher_id)
    {

        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);

        $voucher = Voucher::find($voucher_id);
        $environment = substr($voucher->xml, -26, 1);

        if ($voucher->state === VoucherStates::AUTHORIZED || $voucher->state === VoucherStates::CANCELED) {
            return;
        }

        switch ((int) $environment) {
            case 1:
                $wsdlAuthorization = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';
                break;
            case 2:
                $wsdlAuthorization = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl';
                break;
        }

        $options = array(
            'soap_version' => SOAP_1_1,
            // trace used for __getLastResponse return result in XML
            'trace' => 1,
            'connection_timeout' => 3,
            // exceptions used for detect error in SOAP is_soap_fault
            'exceptions' => 0
        );

        $soapClientValidation = new \SoapClient($wsdlAuthorization, $options);

        // Parameters SOAP
        $user_param = array('claveAccesoComprobante' => $voucher->xml);

        try {
            $response = $soapClientValidation->autorizacionComprobante($user_param);
            $autorizacion = $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion;

            switch ($autorizacion->estado) {
                case VoucherStates::AUTHORIZED:
                    $toPath = "xmls/$company->ruc/" . (new \DateTime($voucher->date))->format('Y/m') . VoucherStates::AUTHORIZED . "/$voucher->xml.xml";
                    Storage::put($toPath, $autorizacion);
                    $voucher->xml = $toPath;
                    $voucher->state = VoucherStates::AUTHORIZED;
                    $voucher->extra_detail = NULL;
                    $authorizationDate = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $autorizacion->fechaAutorizacion);
                    $voucher->autorized = $authorizationDate->format('Y-m-d H:i:s');
                    $voucher->save();
                    break;
                case VoucherStates::REJECTED:
                    $extra_detail = '';
                    foreach ($autorizacion->mensajes->mensaje as $message) {
                        $extra_detail .= 'identificador: ' . $message->identificador;
                        $extra_detail .= ', mensaje: ' . $message->mensaje;
                        $extra_detail .= ', tipo: ' . $message->tipo;
                    }
                    $toPath = "xmls/$company->ruc/" . (new \DateTime($voucher->date))->format('Y/m') . VoucherStates::REJECTED . "/$voucher->xml.xml";
                    Storage::put($toPath, $autorizacion);
                    $voucher->xml = $toPath;
                    $voucher->state = VoucherStates::REJECTED;
                    $voucher->extra_detail = $extra_detail;
                    $authorizationDate = \DateTime::createFromFormat('Y-m-d\TH:i:sP', $autorizacion->fechaAutorizacion);
                    $voucher->autorized = $authorizationDate->format('Y-m-d H:i:s');
                    $voucher->save();
                    break;
                default:
                    $voucher->state = VoucherStates::IN_PROCESS;
                    $voucher->extra_detail = NULL;
                    $voucher->save();
                    break;
            }
        } catch (\Exception $e) {
            info('#### ERROR IN AUTORIZARCOMPROBANTE WS #######################');
            info(' CODE: ' . $e->getCode());
            info(' FILE: ' . $e->getFile());
            info(' LINE: ' . $e->getLine());
            info(' MESSAGE: ' . $e->getMessage());
            info('#### END ERROR IN AUTORIZARCOMPROBANTE WS ###################');
        }
    }
}
