<?php

// 1) Email templating after reviewing API 
// - what online link is
// 2) Ask for ESD api endpoints
// 3) Frequency of triggers daily, weekly
// 4) required fields for the ESD Signer and whether there are credentials that we need to extract to .env.
// 5) Manual process ends when we deploy application

namespace App\Esd;

class ESDApi{
    private $api;
    private $log;

    public function __construct($log){
        $this->log = $log;
        $this->api = "http://172.16.24.44:5000/EsdApi/deononline/";
    }

    function getCurl($endpoint, $requestUrl, $format) {
       // global $api;

        $curl = curl_init($this->api . $endpoint . $requestUrl);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/$format",
            "Accept: application/$format"));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        // these options allow us to read the error message sent by the API
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_HTTP200ALIASES, range(400, 599));

        return $curl;
    }

    function post($endpoint, $format, $data) {
        if (!isset($data)) { return null; }
    
        try {
          // create the curl object.
          // - POST always requires the object's id
          $curl = $this->getCurl("$endpoint", "", $format);
          // set extra curl options required by POST
          curl_setopt($curl, CURLOPT_POST, 1);
          curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
          // POST something
          $curl_result = curl_exec($curl);
          error_log($curl_result);
          curl_close($curl);
          return $curl_result;
        }
        catch (Exception $e) {
          error_log('Error: ' + $e);
        }
    }

    function postJson($endpoint, $data) {
        // POST it, return the API's response
        return $this->post($endpoint, "json", json_encode($data));
    }

    function postInvoice($invoice) {
        return $this->postJson("signinvoice", $invoice);   
    }

    function testPostInvoice($unleashedInvoice){
        $KRAQRCodeLink = "";
        $invoice = new \stdClass();
        $invoiceItems = array();

        foreach($unleashedInvoice->InvoiceLines as $invoiceLine){ 
            $invoiceItem = new \stdClass();
            $invoiceItem->hsDesc = "";
            $invoiceItem->namePLU = $invoiceLine->Product->ProductDescription;
            $invoiceItem->taxRate = 16;
            $invoiceItem->unitPrice = $invoiceLine->UnitPrice;
            $invoiceItem->discount = $invoiceLine->DiscountRate;
            $invoiceItem->hsCode = "";
            $invoiceItem->quantity = $invoiceLine->InvoiceQuantity;
            $invoiceItem->measureUnit = "kg";
            $invoiceItem->vatClass = "A";
            array_push($invoiceItems, $invoiceItem);
        }        

        $invoice->deonItemDetails = $invoiceItems;
        $invoice->senderId = "a4031de9-d11f-4b52-8cca-e1c7422f3c37";
        $invoice->invoiceCategory = "tax_invoice";
        $invoice->traderSystemInvoiceNumber = 12345;
        $invoice->relevantInvoiceNumber = $unleashedInvoice->InvoiceNumber;
        $invoice->pinOfBuyer = "";
        $invoice->discount = 0;
        $invoice->invoiceType = "Original";
        $invoice->exemptionNumber = "";
        $invoice->totalInvoiceAmount = "1000";
        $invoice->systemUser = "Joe Doe";

        echo "<br/><br/>";
        echo "Starting ESD process <br />";
        echo "-------------------------------------------------------------------------------------<br />";
        //echo json_encode($invoice)."<br/><br/>"; 

        try {
            echo $this->postInvoice($invoice)."<br />";
            $decodedEsdInvoiceResponse = json_decode($this->postInvoice($invoice));
            if(!empty($decodedEsdInvoiceResponse)) {            
                echo "Status: ".$decodedEsdInvoiceResponse->status."<br />";

                if($decodedEsdInvoiceResponse->status == "SUCCESS"){
                    echo "qrcode: ".$decodedEsdInvoiceResponse->qrCode."<br />";
                    $log->info("qrcode: ".$decodedEsdInvoiceResponse->qrCode);
                    $KRAQRCodeLink = $decodedEsdInvoiceResponse->qrCode;
                }
            }                
        } catch (\Exception $e) {
            $this->log->error("ESD error: ".$e->ErrorInfo);                                    
        }        
        return $KRAQRCodeLink;
    }
}