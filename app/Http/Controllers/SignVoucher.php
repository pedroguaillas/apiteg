<?php

namespace App\Http\Controllers;

class SignVoucher extends Controller
{
    /* CONSTANTS */

    const SCHEMA_3_2 = "3.2";
    const SCHEMA_3_2_1 = "3.2.1";
    const SCHEMA_3_2_2 = "3.2.2";

    /* PRIVATE CONSTANTS */
    private static $SCHEMA_NS = array(
        self::SCHEMA_3_2 => "http://www.facturae.es/Facturae/2009/v3.2/Facturae",
        self::SCHEMA_3_2_1 => "http://www.facturae.es/Facturae/2014/v3.2.1/Facturae",
        self::SCHEMA_3_2_2 => "http://www.facturae.gob.es/formato/Versiones/Facturaev3_2_2.xml"
    );


    /* ATTRIBUTES */
    private $publicKey = NULL;
    private $privateKey = NULL;
    private $certX509 = NULL;

    public function __construct($schemaVersion = self::SCHEMA_3_2_1)
    {
        $this->setSchemaVersion($schemaVersion);
    }

    private function random()
    {
        return rand(100000, 999999);
    }

    public function setSchemaVersion($schemaVersion)
    {
        $this->version = $schemaVersion;
    }

    /**
     * Load a PKCS#12 Certificate Store
     *
     * @param  string  $pkcs12File  The certificate store file name
     * @param  string  $pkcs12Pass  Encryption password for unlocking the PKCS#12 file
     * @return boolean              Success
     */
    private function loadPkcs12($pkcs12File, $pkcs12Pass = "")
    {
        if (!is_file($pkcs12File))
            return false;
        if (openssl_pkcs12_read(file_get_contents($pkcs12File), $certs, $pkcs12Pass)) {
            //CERTIFICADO X509 CODIFICADO EN Base64
            $extracerts = $certs['extracerts'][0]; //Public X590

            $extracerts = str_replace('-----BEGIN CERTIFICATE-----', '', $extracerts);
            $extracerts = str_replace('-----END CERTIFICATE-----', '', $extracerts);

            $this->certX509 = str_replace("\r", "", str_replace("", "", $extracerts));
            $this->certX509 = chunk_split($this->certX509, 76, "");

            $certX509tohash = str_replace("", "", $extracerts);
            $certX509tohash = str_split($certX509tohash, 76);
            $certX509tohash = implode('', $certX509tohash);
            $certificado_b64 = str_replace('', '', $certX509tohash);
            $this->hash_certificado_der = base64_encode(hash('sha1', base64_decode($certificado_b64), true));

            $this->publicKey = openssl_x509_parse($certs['cert'], 0);

            $this->privateKey = $certs['pkey'];
        }

        return (!empty($this->publicKey) && !empty($this->privateKey));
    }

    /**
     * Sign
     *
     * @param  string  $publicPath  Path to public key PEM file or PKCS#12 certificate store
     * @param  string  $privatePath Path to private key PEM file (should be NULL in case of PKCS#12)
     * @param  string  $passphrase  Private key passphrase
     * @param  array   $policy      Facturae sign policy
     * @return boolean              Success
     */
    public function sign($archivopkcs12, $passphrase)
    {
        //numeros involucrados en los hash:

        //var Certificate_number = 1217155;//p_obtener_aleatorio(); //1562780 en el ejemplo del SRI
        $this->Certificate_number = $this->random(); //1562780 en el ejemplo del SRI

        //var Signature_number = 1021879;//p_obtener_aleatorio(); //620397 en el ejemplo del SRI
        $this->Signature_number = $this->random(); //620397 en el ejemplo del SRI

        //var SignedProperties_number = 1006287;//p_obtener_aleatorio(); //24123 en el ejemplo del SRI
        $this->SignedProperties_number = $this->random(); //24123 en el ejemplo del SRI

        //numeros fuera de los hash:

        //var SignedInfo_number = 696603;//p_obtener_aleatorio(); //814463 en el ejemplo del SRI
        $this->SignedInfo_number = $this->random(); //814463 en el ejemplo del SRI

        //var SignedPropertiesID_number = 77625;//p_obtener_aleatorio(); //157683 en el ejemplo del SRI
        $this->SignedPropertiesID_number = $this->random(); //157683 en el ejemplo del SRI

        //var Reference_ID_number = 235824;//p_obtener_aleatorio(); //363558 en el ejemplo del SRI
        $this->Reference_ID_number = $this->random(); //363558 en el ejemplo del SRI

        //var SignatureValue_number = 844709;//p_obtener_aleatorio(); //398963 en el ejemplo del SRI
        $this->SignatureValue_number = $this->random(); //398963 en el ejemplo del SRI

        //var Object_number = 621794;//p_obtener_aleatorio(); //231987 en el ejemplo del SRI
        $this->Object_number = $this->random(); //231987 en el ejemplo del SRI

        return $this->loadPkcs12($archivopkcs12, $passphrase);
    }

    /**
     * Inject signature
     *
     * @param  string Unsigned XML document
     * @return string Signed XML document
     */
    public function injectSignature($xml, $comprobante)
    {
        // X509SerialNumber
        $X509SerialNumber = $this->publicKey['serialNumber'];

        $complem = openssl_pkey_get_details(openssl_pkey_get_private($this->privateKey));
        $modulus = base64_encode($complem['rsa']['n']);

        $sha1_comprobante = str_replace('<?xml version="1.0" encoding="UTF-8"?>', '', $xml);
        $sha1_comprobante = base64_decode($sha1_comprobante);
        $sha1_comprobante = base64_encode(hash('sha1', $sha1_comprobante));

        $xmlns = 'xmlns:ds="http://www.w3.org/2000/09/xmldsig#" xmlns:etsi="http://uri.etsi.org/01903/v1.3.2#"';

        // Prepare signed properties
        $issuerName = 'CN=AC BANCO CENTRAL DEL ECUADOR,L=QUITO,OU=ENTIDAD DE CERTIFICACION DE INFORMACION-ECIBCE,O=BANCO CENTRAL DEL ECUADOR,C=EC';

        // Generate signed properties
        $SignedProperties = '';

        $SignedProperties .= '<etsi:SignedProperties Id="Signature' . $this->Signature_number . '-SignedProperties' . $this->SignedProperties_number . '">';  //SignedProperties
        $SignedProperties .= '<etsi:SignedSignatureProperties>';
        $SignedProperties .= '<etsi:SigningTime>';

        //SignedProperties += '2016-12-24T13:46:43-05:00';//moment().format('YYYY-MM-DD\THH:mm:ssZ');
        $SignedProperties .= date('Y-m-d\TH:i:s-05:00');
        $SignedProperties .= '</etsi:SigningTime>';
        $SignedProperties .= '<etsi:SigningCertificate>';
        $SignedProperties .= '<etsi:Cert>';
        $SignedProperties .= '<etsi:CertDigest>';
        $SignedProperties .= '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">';
        $SignedProperties .= '</ds:DigestMethod>';
        $SignedProperties .= '<ds:DigestValue>';
        $SignedProperties .= $this->hash_certificado_der;
        $SignedProperties .= '</ds:DigestValue>';
        $SignedProperties .= '</etsi:CertDigest>';
        $SignedProperties .= '<etsi:IssuerSerial>';
        $SignedProperties .= '<ds:X509IssuerName>';
        $SignedProperties .= $issuerName;
        $SignedProperties .= '</ds:X509IssuerName>';
        $SignedProperties .= '<ds:X509SerialNumber>';
        $SignedProperties .= $X509SerialNumber;
        $SignedProperties .= '</ds:X509SerialNumber>';
        $SignedProperties .= '</etsi:IssuerSerial>';
        $SignedProperties .= '</etsi:Cert>';
        $SignedProperties .= '</etsi:SigningCertificate>';
        $SignedProperties .= '</etsi:SignedSignatureProperties>';
        $SignedProperties .= '<etsi:SignedDataObjectProperties>';
        $SignedProperties .= '<etsi:DataObjectFormat ObjectReference="#Reference-ID-' . $this->Reference_ID_number . '">';
        $SignedProperties .= '<etsi:Description>';
        $SignedProperties .= 'contenido comprobante';
        $SignedProperties .= '</etsi:Description>';
        $SignedProperties .= '<etsi:MimeType>';
        $SignedProperties .= 'text/xml';
        $SignedProperties .= '</etsi:MimeType>';
        $SignedProperties .= '</etsi:DataObjectFormat>';
        $SignedProperties .= '</etsi:SignedDataObjectProperties>';
        $SignedProperties .= '</etsi:SignedProperties>'; //fin SignedProperties

        $SignedProperties_para_hash = str_replace('<etsi:SignedProperties', '<etsi:SignedProperties ' . $xmlns, $SignedProperties);

        $sha1_SignedProperties = base64_encode(sha1($SignedProperties_para_hash, true));

        $KeyInfo = '';
        $KeyInfo .= '<ds:KeyInfo Id="Certificate' . $this->Certificate_number . '">';
        $KeyInfo .= '<ds:X509Data>';
        $KeyInfo .= '<ds:X509Certificate>';

        //CERTIFICADO X509 CODIFICADO EN Base64 
        $KeyInfo .= $this->certX509;
        $KeyInfo .= '</ds:X509Certificate>';
        $KeyInfo .= '</ds:X509Data>';
        $KeyInfo .= '<ds:KeyValue>';
        $KeyInfo .= '<ds:RSAKeyValue>';
        $KeyInfo .= '<ds:Modulus>';

        //MODULO DEL CERTIFICADO X509
        $KeyInfo .= $modulus;

        $KeyInfo .= '</ds:Modulus>';
        $KeyInfo .= '<ds:Exponent>AQAB</ds:Exponent>';
        $KeyInfo .= '</ds:RSAKeyValue>';
        $KeyInfo .= '</ds:KeyValue>';
        $KeyInfo .= '</ds:KeyInfo>';

        $KeyInfo_para_hash = str_replace('<ds:KeyInfo', '<ds:KeyInfo ' . $xmlns, $KeyInfo);

        $sha1_certificado = base64_encode(sha1($KeyInfo_para_hash, true));

        $SignedInfo = '';
        $SignedInfo .= '<ds:SignedInfo Id="Signature-SignedInfo' . $this->SignedInfo_number . '">';
        $SignedInfo .= '<ds:CanonicalizationMethod Algorithm="http://www.w3.org/TR/2001/REC-xml-c14n-20010315">';
        $SignedInfo .= '</ds:CanonicalizationMethod>';
        $SignedInfo .= '<ds:SignatureMethod Algorithm="http://www.w3.org/2000/09/xmldsig#rsa-sha1">';
        $SignedInfo .= '</ds:SignatureMethod>';
        $SignedInfo .= '<ds:Reference Id="SignedPropertiesID' . $this->SignedPropertiesID_number . '" Type="http://uri.etsi.org/01903#SignedProperties" URI="#Signature' . $this->Signature_number . '-SignedProperties' . $this->SignedProperties_number . '">';
        $SignedInfo .= '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">';
        $SignedInfo .= '</ds:DigestMethod>';
        $SignedInfo .= '<ds:DigestValue>';

        //HASH O DIGEST DEL ELEMENTO <etsi:SignedProperties>';
        $SignedInfo .= $sha1_SignedProperties;

        $SignedInfo .= '</ds:DigestValue>';
        $SignedInfo .= '</ds:Reference>';
        $SignedInfo .= '<ds:Reference URI="#Certificate' . $this->Certificate_number . '">';
        $SignedInfo .= '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">';
        $SignedInfo .= '</ds:DigestMethod>';
        $SignedInfo .= '<ds:DigestValue>';

        //HASH O DIGEST DEL CERTIFICADO X509
        $SignedInfo .= $sha1_certificado;

        $SignedInfo .= '</ds:DigestValue>';
        $SignedInfo .= '</ds:Reference>';
        $SignedInfo .= '<ds:Reference Id="Reference-ID-' . $this->Reference_ID_number . '" URI="#comprobante">';
        $SignedInfo .= '<ds:Transforms>';
        $SignedInfo .= '<ds:Transform Algorithm="http://www.w3.org/2000/09/xmldsig#enveloped-signature">';
        $SignedInfo .= '</ds:Transform>';
        $SignedInfo .= '</ds:Transforms>';
        $SignedInfo .= '<ds:DigestMethod Algorithm="http://www.w3.org/2000/09/xmldsig#sha1">';
        $SignedInfo .= '</ds:DigestMethod>';
        $SignedInfo .= '<ds:DigestValue>';

        //HASH O DIGEST DE TODO EL ARCHIVO XML IDENTIFICADO POR EL id="comprobante" 
        $SignedInfo .= $sha1_comprobante;

        $SignedInfo .= '</ds:DigestValue>';
        $SignedInfo .= '</ds:Reference>';
        $SignedInfo .= '</ds:SignedInfo>';

        $SignedInfo_para_firma = str_replace('<ds:SignedInfo', '<ds:SignedInfo ' . $xmlns, $SignedInfo);

        $signature = "";

        openssl_sign($SignedInfo_para_firma, $signature, $this->privateKey);
        $signature = chunk_split(base64_encode($signature), 76, '');

        $xades_bes = '';

        //INICIO DE LA FIRMA DIGITAL 
        $xades_bes .= '<ds:Signature ' . $xmlns . ' Id="Signature' . $this->Signature_number . '">';
        $xades_bes .= $SignedInfo;
        $xades_bes .= '<ds:SignatureValue Id="SignatureValue' . $this->SignatureValue_number . '">';

        //VALOR DE LA FIRMA (ENCRIPTADO CON LA LLAVE PRIVADA DEL CERTIFICADO DIGITAL) 
        $xades_bes .= $signature;
        $xades_bes .= '</ds:SignatureValue>';
        $xades_bes .= '' . $KeyInfo;
        $xades_bes .= '<ds:Object Id="Signature' . $this->Signature_number . '-Object' . $this->Object_number . '">';
        $xades_bes .= '<etsi:QualifyingProperties Target="#Signature' . $this->Signature_number . '">';

        //ELEMENTO <etsi:SignedProperties>';
        $xades_bes .= $SignedProperties;
        $xades_bes .= '</etsi:QualifyingProperties>';
        $xades_bes .= '</ds:Object>';
        $xades_bes .= '</ds:Signature>';

        $xml = str_replace($comprobante, $xades_bes . $comprobante, $xml);
        return $xml;
    }
}
