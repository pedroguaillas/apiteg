<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\StaticClasses\VoucherStates;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use App\MovementItem;
use App\Company;
use App\Voucher;
use App\Contact;

class DatilApiController extends Controller
{
    public function index($id)
    {
        $auth = Auth::user();
        $level = $auth->companyusers->first();
        $company = Company::find($level->level_id);
        $branch = $company->branches->first();

        $voucher = Voucher::join('movements', 'vouchers.movement_id', 'movements.id')
            ->select('movements.date', 'movements.sub_total', 'vouchers.serie', 'vouchers.contact_id', 'vouchers.voucher_type', 'vouchers.total')
            ->where('movements.id', $id)
            ->first();

        $contact = Contact::findOrFail($voucher->contact_id);

        $movement_items = MovementItem::join('products', 'products.id', 'movement_items.product_id')
            ->select(
                'movement_items.quantity',
                'products.code',
                'movement_items.price',
                'movement_items.discount',
                'products.name',
                'products.iva'
            )
            ->where('movement_id', $id)
            ->get();

        $items = [];
        foreach ($movement_items as $item) {
            array_push($items, [
                'cantidad' => $item->quantity,
                'codigo_principal' => $item->code,
                'precio_unitario' => $item->price,
                'descuento' => $item->discount,
                'descripcion' => $item->name,
                'impuestos' => [
                    [
                        'base_imponible' => $item->quantity * $item->price,
                        'valor' => $item->quantity * $item->price * ($item->iva === 2 ? 12 : 0),
                        'tarifa' =>  '' . ($item->iva === 2 ? 12 : 0),
                        'codigo' => '2',
                        'codigo_porcentaje' => '' . $item->iva,
                    ]
                ]
            ]);
        }

        $info_adicional = [];

        array_push($info_adicional, ['nombre' => 'Email', 'valor' => 'pruebas@sri.gob.ec']);

        if ($company->micro_business === 1) {
            array_push($info_adicional, ['nombre' => 'Régimen:', 'valor' => 'Régimen Contribuyente Microempresas']);
        }
        if ($company->retention_agent === 1) {
            array_push($info_adicional, ['nombre' => 'Angente de Retención:', 'valor' => 'Resolución Nro. 1']);
        }

        $impuestos = [];
        foreach ((new XmlVoucherController())->grupingTaxes($movement_items) as $tax) {
            array_push($impuestos, ['base_imponible' => $tax->base, 'valor' => $tax->value, 'codigo' => '2', 'codigo_porcentaje' => '' . $tax->percentageCode]);
        }

        $comprobante = [
            'ambiente' => $company->enviroment_type,
            'tipo_emision' => 1,
            'secuencial' => (int)substr($voucher->serie, 8, 9),
            'fecha_emision' => (new \DateTime($voucher->date))->format(\DateTime::ISO8601),
            'emisor' => [
                'ruc' => $company->ruc,
                'obligado_contabilidad' => $company->accounting === 1,
                'contribuyente_especial' => $company->special,
                'nombre_comercial' => $branch->name,
                'razon_social' => $company->company,
                'direccion' => $branch->address,
                'establecimiento' => [
                    'punto_emision' => substr($voucher->serie, 4, 3),
                    'codigo' => substr($voucher->serie, 4, 3),
                    'direccion' => $branch->address
                ]
            ],
            'moneda' => 'USD',
            'info_adicional' => $info_adicional,
            'totales' => [
                'total_sin_impuestos' => $voucher->sub_total,
                'impuestos' => $impuestos,
                'importe_total' => $voucher->total,
                'propina' => 0,
                'descuento' => 0
            ],
            'comprador' => [
                'email' => $contact->email,
                'identificacion' => $contact->ruc !== null ? $contact->ruc : $contact->identification_card,
                'tipo_identificacion' => $contact->ruc !== null ? '04' : '05',
                'razon_social' => $contact->company,
                'direccion' => $contact->address,
                'telefono' => $contact->phone
            ],
            'items' => $items,
            'pagos' => [
                [
                    'medio' => 'otros',
                    'total' => $voucher->total
                ]
            ]
        ];

        // return $this->send($comprobante, $id);
        return $this->send2($comprobante);
    }

    private function send2($comprobante)
    {
        $client = new Client([
            'base_uri' => 'https://link.datil.co'
        ]);

        $response = $client->post('invoices/issue', [
            'debug' => TRUE,
            'body' => json_encode($comprobante),
            'headers' => [
                'Content-Type' => 'application/json',
                'X-Key' => '5efcf1ad278641ebb90846eb7e9f65ac',
                'X-Password' => '0602479388'
            ]
        ]);

        return $response;
    }

    private function send($comprobante, $id)
    {
        // $url = preg_replace("/ /", "%20", $url);
        $url = 'https://link.datil.co/invoices/issue';

        $postdata = http_build_query($comprobante);

        $opts = array('http' =>
        array(
            'method'  => "POST",
            // 'header'  => array(
            //     "Content-type: application/json",
            //     "X-Key: ac8f9ff21d2d458794b121bdc01d2508",
            //     "X-Password: 0602479388"
            // ),
            'header'  => 'Content-Type: application/json; X-Key=ac8f9ff21d2d458794b121bdc01d2508; X-Password=0602479388',
            'content' => $postdata
            // 'content' => $comprobante
        ));

        $context = stream_context_create($opts);

        // Response
        // $result = file_get_contents($url, false, $context);
        $result = fopen($url, 'r', false, $context);

        $result = json_decode($result);

        return $result;

        // $voucher = Voucher::where('movement_id', $id)->get()->first();

        // if ($result->creado) {
        //     $voucher->state = VoucherStates::SENDED;
        // } else {
        //     var_dump('ER-CREADO');
        //     $voucher->state = VoucherStates::RETURNED;
        //     $voucher->error = $result->errors->error;
        // }

        // $voucher->xml = $result->claveacceso;
        // $voucher->save();

        // return $result;

        // if ($voucher->state === 'ENVIADO') {
        //     $this->authorizevoucher($id);
        // }
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
