<?php

namespace App\Unleashed;

class UnleashedApi{
    private $api;
    private $apiId;
    private $apiKey;
    private $log;

    public function __construct($log){
        $this->log = $log;
        $config = include("Config.php");

		$this->api = $config["unleashed_api"];
        $this->apiId = $config["unleashed_api_id"];
        $this->apiKey = $config["unleashed_api_key"];
    } 

    // Get the request signature:
    // Based on your API id and the request portion of the url
    // - $request is only any part of the url after the "?"
    // - use $request = "" if there is no request portion
    // - for GET $request will only be the filters eg ?customerName=Bob
    // - for POST $request will usually be an empty string
    // - $request never includes the "?"
    // Using the wrong value for $request will result in an 403 forbidden response from the API
    function getSignature($request, $key) {
        $this->log->info("Unleashed API signature generated");
        return base64_encode(hash_hmac("sha256", $request, $key, true));
    }

    // Create the curl object and set the required options
    // - $api will always be https://api.unleashedsoftware.com/
    // - $endpoint must be correctly specified
    // - $requestUrl does include the "?" if any
    // Using the wrong values for $endpoint or $requestUrl will result in a failed API call
    function getCurl($id, $key, $signature, $endpoint, $requestUrl, $format) {

        $curl = curl_init($this->api . $endpoint . $requestUrl);
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/$format",
            "Accept: application/$format", "api-auth-id: $id", "api-auth-signature: $signature"));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        // these options allow us to read the error message sent by the API
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_HTTP200ALIASES, range(400, 599));
        
        $this->log->info("Unleashed API curl object created and required options set");

        return $curl;
    }

    // GET something from the API
    // - $request is only any part of the url after the "?"
    // - use $request = "" if there is no request portion
    // - for GET $request will only be the filters eg ?customerName=Bob
    // - $request never includes the "?"
    // Format agnostic method.  Pass in the required $format of "json" or "xml"
    function get($id, $key, $endpoint, $request, $format) {
        $requestUrl = "";
        if (!empty($request)) $requestUrl = "?$request";

        try {
            // calculate API signature
            $signature = $this->getSignature($request, $key);
            // create the curl object
            $curl = $this->getCurl($id, $key, $signature, $endpoint, $requestUrl, $format);
            // GET something
            $curl_result = curl_exec($curl);
            $this->log->info("Unleashed API get endpoint: success");            
            curl_close($curl);
            return $curl_result;
        }
        catch (Exception $e) {
            //error_log("Error: " + $e);        
            $this->log->error("Unleashed API get error: ".$e->ErrorInfo);
        }
    }

    // POST something to the API
    // - $request is only any part of the url after the "?"
    // - use $request = "" if there is no request portion
    // - for POST $request will usually be an empty string
    // - $request never includes the "?"
    // Format agnostic method.  Pass in the required $format of "json" or "xml"
    function post($id, $key, $endpoint, $format, $dataId, $data) {
        if (!isset($dataId, $data)) { return null; }

        try {
            // calculate API signature
            $signature = $this->getSignature("", $key);
            // create the curl object.
            // - POST always requires the object"s id
            $curl = $this->getCurl($id, $key, $signature, "$endpoint/$dataId", "", $format);
            // set extra curl options required by POST
            curl_setopt($curl, CURLOPT_POST, 1);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            // POST something
            $curl_result = curl_exec($curl);            
            $this->log->info("Unleashed API post endpoint: ".$curl_result); 
            curl_close($curl);
            return $curl_result;
        }
        catch (Exception $e) {
            //error_log("Error: " + $e);
            $this->log->error("Unleashed API post error: ".$e->ErrorInfo);
        }
    }

    // GET in JSON format
    // - gets the data from the API and converts it to an stdClass object
    function getJson($id, $key, $endpoint, $request) {
        // GET it, decode it, return it
        return json_decode($this->get($id, $key, $endpoint, $request, "json"));
    }

    // POST in JSON format
    // - the object to POST must be a valid stdClass object. Not array, not associative.
    // - converts the object to string and POSTs it to the API
    function postJson($id, $key, $endpoint, $dataId, $data) {
        // POST it, return the API"s response
        return $this->post($id, $key, $endpoint, "json", $dataId, json_encode($data));
    }

    // Invoices
    function getInvoices($endpoint, $request) {
        return $this->getJson($this->apiId, $this->apiKey, $endpoint, $request);
    }

    // Invoice
    function getInvoice($invoiceNum) {
        return $this->getJson($this->apiId, $this->apiKey, "Invoices", "InvoiceNumber=$invoiceNum");
    }

    // Product
    function getProduct($endpoint) {
        return $this->getJson($this->apiId, $this->apiKey, $endpoint, "");
    }

    // 
    function getCustomer($endpoint) {
        return $this->getJson($this->apiId, $this->apiKey, $endpoint, "");
    }

    // Call the GET invoice by number method and print the results
    function testGetInvoiceByNumber() {
        $json = $this->getInvoice("SO-00006827", "json");
        $invoice = $json->Items[0];
        $number = $invoice->InvoiceNumber;
        $subTotal = $invoice->SubTotal;

        $customerName = $invoice->Customer->CustomerName;
        $customerCode = $invoice->Customer->CustomerCode;

        $this->log->info("Unleashed API get invoice by number endpoint: Invoice number - ".$number); 

        foreach($invoice->InvoiceLines as $invoiceLine){      
            $productDesc = $invoiceLine->Product->ProductDescription;
            $productOrderQuantity = $invoiceLine->OrderQuantity;
            $productInvoiceQuantity = $invoiceLine->InvoiceQuantity;
            $productUnitPrice = $invoiceLine->UnitPrice;
            $productDiscountRate = $invoiceLine->DiscountRate;
            $productLineTotal = $invoiceLine->LineTotal;
            $productTaxRate = $invoiceLine->TaxRate;
            $productLineTax = $invoiceLine->LineTax;
        } 
        return $invoice;
    }

}