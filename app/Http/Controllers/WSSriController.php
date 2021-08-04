<?php

namespace App\Http\Controllers;

use App\StaticClasses\VoucherStates;
use Illuminate\Support\Facades\Storage;
use App\Voucher;

class WSSriController
{
    public function sendVoucher($voucher_id)
    {
        $voucher = Voucher::find($voucher_id);
        $environment = substr($voucher->xml, -30, 1);

        switch ((int) $environment) {
            case 1:
                $wsdlReceipt = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';
                break;
            case 2:
                $wsdlReceipt = 'https://cel.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';
                break;
        }

        $options = array(
            'connection_timeout' => 3,
            'cache_wsdl' => WSDL_CACHE_NONE
        );
        $soapClientReceipt = new \SoapClient($wsdlReceipt, $options);
        $paramenters = new \stdClass();
        $paramenters->xml = file_get_contents($voucher->xml);

        try {
            $resultReceipt = json_decode(json_encode($soapClientReceipt->validarComprobante($paramenters)), True);
            $this->moveXmlFile($voucher, VoucherStates::SENDED);
            switch ($resultReceipt['RespuestaRecepcionComprobante']['estado']) {
                case VoucherStates::RECEIVED:
                    $this->moveXmlFile($voucher, VoucherStates::RECEIVED);
                    $this->authorizevoucher($voucher_id);
                    break;
                case VoucherStates::RETURNED:
                    $message = $resultReceipt['RespuestaRecepcionComprobante']['comprobantes']['comprobante']['mensajes']['mensaje']['tipo'] . ' ' .
                        $resultReceipt['RespuestaRecepcionComprobante']['comprobantes']['comprobante']['mensajes']['mensaje']['identificador'] . ': ' .
                        $resultReceipt['RespuestaRecepcionComprobante']['comprobantes']['comprobante']['mensajes']['mensaje']['mensaje'];
                    if (array_key_exists('informacionAdicional', $resultReceipt['RespuestaRecepcionComprobante']['comprobantes']['comprobante']['mensajes']['mensaje'])) {
                        $message .= '. ' . $resultReceipt['RespuestaRecepcionComprobante']['comprobantes']['comprobante']['mensajes']['mensaje']['informacionAdicional'];
                    }
                    $voucher->extra_detail = $message;
                    $this->moveXmlFile($voucher, VoucherStates::RETURNED);
                    break;
            }
        } catch (\Exception $e) {
            info('#### ERROR IN VALIDARCOMPROBANTE WS #######################');
            info(' CODE: ' . $e->getCode());
            info(' FILE: ' . $e->getFile());
            info(' LINE: ' . $e->getLine());
            info(' MESSAGE: ' . $e->getMessage());
            info('#### END ERROR IN VALIDARCOMPROBANTE WS ###################');
        }
    }

    public function authorizevoucher($voucher_id)
    {
        $voucher = Voucher::find($voucher_id);
        $environment = substr($voucher->xml, -30, 1);

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
            "soap_version" => SOAP_1_1,
            // trace used for __getLastResponse return result in XML
            "trace" => 1,
            'connection_timeout' => 3,
            // exceptions used for detect error in SOAP is_soap_fault
            'exceptions' => 0
        );

        $soapClientValidation = new \SoapClient($wsdlAuthorization, $options);

        // Parameters SOAP
        $user_param = array(
            'claveAccesoComprobante' => substr(substr($voucher->xml, -53), 0, 49)
        );

        try {
            $response = $soapClientValidation->autorizacionComprobante($user_param);
            $autorizacion = $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion;

            switch ($autorizacion->estado) {
                case VoucherStates::AUTHORIZED:
                    $toPath = str_replace($voucher->state, VoucherStates::AUTHORIZED, $voucher->xml);
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
                    $toPath = str_replace($voucher->state, VoucherStates::REJECTED, $voucher->xml);
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

    private function moveXmlFile($voucher, $newState)
    {
        $to = str_replace($voucher->state, $newState, $voucher->xml);
        Storage::move($voucher->xml, $to);
        $voucher->state = $newState;
        $voucher->xml = $to;
        $voucher->save();
    }
}
