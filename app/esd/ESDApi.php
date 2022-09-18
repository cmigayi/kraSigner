<?php
namespace App\Esd;

class ESDApi{
    private $api;
    private $log;

    public function __construct($log){
        $this->log = $log;
        $config = include("Config.php");

		$this->api = $config["esd_api"];
    }

    function getCurl($endpoint, $requestUrl, $format) {        
        $curl = curl_init($this->api . $endpoint . $requestUrl);
        $this->log->info($this->api . $endpoint . $requestUrl);
        try{
            curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
            curl_setopt($curl, CURLINFO_HEADER_OUT, true);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); 
            curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/$format",
                "Accept: application/$format"));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 20);
            // these options allow us to read the error message sent by the API
            curl_setopt($curl, CURLOPT_FAILONERROR, false);
            curl_setopt($curl, CURLOPT_HTTP200ALIASES, range(400, 599));
        }catch (Exception $e) {
            $this->log->error("ESD api error: " + $e);
        }

        return $curl;
    }

    function post($endpoint, $format, $data) {
        if (!isset($data)) { return null; }
    
        try {
          $this->log->info("Post function:");
          $this->log->info($endpoint);
          // create the curl object.
          // - POST always requires the object"s id
          $curl = $this->getCurl("$endpoint", "", $format);    
          // set extra curl options required by POST
          curl_setopt($curl, CURLOPT_POST, 1);
          curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
          // POST something
          $curl_result = curl_exec($curl);   
          $info = curl_getinfo($curl);     
          $this->log->info("ESD API post response code: ".$info["http_code"]); 
          if(curl_errno($curl))  $this->log->info("ESD API post Curl error: " . curl_error($curl));    
          $this->log->info("ESD API post endpoint: ".$curl_result); 
          curl_close($curl);
          return $curl_result;
        }
        catch (Exception $e) {
            $this->log->error("ESD api error: ".$e->ErrorInfo);
        }
    }

    function postJson($endpoint, $data) {
        // POST it, return the API"s response
        $this->log->info(json_encode($data));
        return $this->post($endpoint, "json", json_encode($data));
    }

    function postInvoice($invoice) {
        return $this->postJson("signinvoice/", $invoice); 
    }

    function testPostInvoice($unleashedInvoice,$svcCustomer,$unleashedApi){
        $KRAQRCodeLink = "";
        $invoice = new \stdClass();
        $invoiceItems = array();

        foreach($unleashedInvoice->InvoiceLines as $invoiceLine){
            $this->log->info("----------------------------InvoiceLine Start----------------------------------"); 
            $invoiceItem = new \stdClass();
            $invoiceItem->hsDesc = "";
            $invoiceItem->namePLU = $invoiceLine->Product->ProductDescription;
            $this->log->info("Invoice desc: $invoiceItem->namePLU");
            $invoiceItem->taxRate = 16;
            $invoiceItem->unitPrice = $invoiceLine->UnitPrice;
            $invoiceItem->discount = $invoiceLine->DiscountRate;
            $invoiceItem->hsCode = "";
            $invoiceItem->quantity = $invoiceLine->InvoiceQuantity;
            $productGuid = $invoiceLine->Product->Guid;
            $this->log->info("Invoice product GUID: $productGuid");
            $productDetails = $unleashedApi->getProduct("Products/$productGuid");
            $this->log->info(json_encode((array)$productDetails));
            $measureUnit = $productDetails->UnitOfMeasure->Name;
            $this->log->info("UnitOfMeasure: $measureUnit");
            $invoiceItem->measureUnit = $measureUnit;
            $invoiceItem->vatClass = "A";
            array_push($invoiceItems, $invoiceItem);
            $this->log->info("----------------------------InvoiceLine End----------------------------------");
        }        
        $invoice->deonItemDetails = $invoiceItems;
        $invoice->senderId = "a4031de9-d11f-4b52-8cca-e1c7422f3c37";
        $invoice->invoiceCategory = "tax_invoice";
        $invoiceNum = $unleashedInvoice->InvoiceNumber;
        // Split the number to get the numeric part
        $invoiceNumExploadedArr = explode("-",$invoiceNum);
        $this->log->info("Split invoiceNumber, get numeric section: $invoiceNumExploadedArr[1]");
        $invoice->traderSystemInvoiceNumber = $invoiceNumExploadedArr[1];
        $invoice->relevantInvoiceNumber = $invoiceNum;
        $invoice->pinOfBuyer = $svcCustomer->GSTVATNumber;
        $invoice->invoiceType = "Original";
        $invoice->exemptionNumber = "";
        $invoice->totalInvoiceAmount = $unleashedInvoice->Total;
        $invoice->systemUser = "Joe Doe";

        $this->log->info("ESD process started...");

        try {
            $this->log->info("....Test ESD TRY CATCH.....");
            $decodedEsdInvoiceResponse = json_decode($this->postInvoice($invoice));
            
            if(!empty($decodedEsdInvoiceResponse)) {      
                $this->log->info("ESD process response: ".$decodedEsdInvoiceResponse->status);      

                if($decodedEsdInvoiceResponse->status == "SUCCESS"){
                    $this->log->info("qrcode: ".$decodedEsdInvoiceResponse->qrCode);
                    $KRAQRCodeLink = $decodedEsdInvoiceResponse->qrCode;
                }
            }else{
                $this->log->info("EsdInvoiceSigning response empty thus...failed");
            }                
        } catch (\Exception $e) {
            $this->log->error("ESD api error: ".$e->ErrorInfo);                                    
        }        
        return $KRAQRCodeLink;
    }
}