<?php

namespace App\Unleashed;

class UnleashedApi{
    private $api;
    private $apiId;
    private $apiKey;
    private $log;

    public function __construct($log){
        $this->log = $log;

        // configuration data
        // must use your own id and key with no extra whitespace
        $this->api = "https://api.unleashedsoftware.com/";
        $this->apiId = "d423e2fe-a575-4f9e-abe0-3154376ce090";
        $this->apiKey = "YAeCbF4cCajaiDyGzeQhbSZzbT5uQRIl3ni3y0HJiw6JW3KMCNOFrMP5opFPqkz0Ssxshx33vcOs3NYFQ==";
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
        return base64_encode(hash_hmac('sha256', $request, $key, true));
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
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
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
            $this->log->info("Unleashed API get endpoint: ".$curl_result);            
            curl_close($curl);
            return $curl_result;
        }
        catch (Exception $e) {
            //error_log('Error: ' + $e);        
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
            // - POST always requires the object's id
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
            //error_log('Error: ' + $e);
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
        // POST it, return the API's response
        return $this->post($id, $key, $endpoint, "json", $dataId, json_encode($data));
    }

    // Invoices
    function getInvoices() {
        return $this->getJson($this->apiId, $this->apiKey, "Invoices", "");
    }

    // Invoices
    function getInvoice($invoiceNum) {
        return $this->getJson($this->apiId, $this->apiKey, "Invoices", "InvoiceNumber=$invoiceNum");
    }

    // 
    function getCustomer($customerGuid) {
        return $this->getJson($this->apiId, $this->apiKey, "Customers", "Guid=b732047c-650f-4501-985c-ab98986cf50c");
    }

    // 
    // function getCustomer($customerCode) {
    //     return $this->getJson($this->apiId, $this->apiKey, "Customers", "CustomerCode=$customerCode");
    // }

    // Call the GET invoices method and print the results
    function testGetInvoices() {
        echo "Starting test: testGetInvoices" . "<br />";
        echo "<br />";
        echo "<br />";

        echo "-------------------------------------------------------------------------------------<br />";
        echo "GET invoices in JSON format:" . "<br />";
        echo "<br />";
        $json = $this->getInvoices();
        //echo json_encode($json);
        // echo "<br />";
        // echo "<br />";

        // echo "GET invoices in JSON format: example of looping through the invoice list" . "<br />";
        
        // foreach ($json->Items as $invoice) {
        //     $invoiceNumber = $invoice->InvoiceNumber;
        //     $invoiceOrderNumber = $invoice->OrderNumber;
        //     $invoiceDate = $invoice->InvoiceDate;
        //     $invoiceDueDate = $invoice->DueDate;
        //     $subTotal = $invoice->SubTotal;
        //     $total = $invoice->Total;
        //     $taxTotal = $invoice->TaxTotal;
        //     $paymentTerm = $invoice->PaymentTerm;
        //     $customerName = $invoice->Customer->CustomerName;
        //     $customerCode = $invoice->Customer->CustomerCode;
        //     $customerCurrencyId = $invoice->Customer->CurrencyId;
        //     $customerGuid = $invoice->Customer->Guid;
        //     $postalAddressStreetAddress = $invoice->PostalAddress->StreetAddress;
        //     $postalAddressStreetAddress2 = $invoice->PostalAddress->StreetAddress2;
        //     $postalAddressCity = $invoice->PostalAddress->City;
        //     $postalAddressCountry = $invoice->PostalAddress->Country;
        //     echo "JSON Invoice: $invoiceNumber, $invoiceOrderNumber, $invoiceDate, $invoiceDueDate, $subTotal, $customerName, $customerCode,  <br />";
        //     echo "more details: $postalAddressStreetAddress, $postalAddressStreetAddress2, $postalAddressCity, $postalAddressCountry  <br />";
        //     echo "more details: $subTotal, $taxTotal, $total, $paymentTerm  <br /><br />";
        //     $this->log->info("Unleashed API get invoices endpoint: Invoice number - ".$number); 
        // }

        // echo "<br />";
        // echo "<br />";
        // echo "End of test: testGetInvoices" . "<br />";
        // echo "-------------------------------------------------------------------------------------<br />";
        
        return $json; 
    }

    // Call the GET invoice by number method and print the results
    function testGetInvoiceByNumber() {
        echo "Starting test: testGetInvoiceByNumber". "<br />";
        echo "-------------------------------------------------------------------------------------<br />";
        // echo "GET invoice by number in JSON format:";
        $json = $this->getInvoice("SI-00000123", "json");
        // echo json_encode($json);
        echo "<br />";
        // echo "GET invoice in JSON format" . "<br />";
        $invoice = $json->Items[0];
        $number = $invoice->InvoiceNumber;
        $subTotal = $invoice->SubTotal;

        $customerName = $invoice->Customer->CustomerName;
        $customerCode = $invoice->Customer->CustomerCode;
        echo "Invoice: <br/><br/>"; 
        echo "Inv num: $number, Subtotal: $subTotal, Custm-name: $customerName, Custm-code: $customerCode<br />";
        
        $this->log->info("Unleashed API get invoice by number endpoint: Invoice number - ".$number);     
        foreach($invoice->InvoiceLines as $invoiceLine){ 
            echo "<br/>";
            echo "*********************************************************************"; 
            echo "<br/>";       
            $productDesc = $invoiceLine->Product->ProductDescription;
            $productOrderQuantity = $invoiceLine->OrderQuantity;
            $productInvoiceQuantity = $invoiceLine->InvoiceQuantity;
            $productUnitPrice = $invoiceLine->UnitPrice;
            $productDiscountRate = $invoiceLine->DiscountRate;
            $productLineTotal = $invoiceLine->LineTotal;
            $productTaxRate = $invoiceLine->TaxRate;
            $productLineTax = $invoiceLine->LineTax;
            echo "Desc: $productDesc, Qty: $productOrderQuantity, Qty: $productInvoiceQuantity, unit Price: $productUnitPrice, Disc: $productDiscountRate<br />";
            echo "Total: $productLineTotal, Tax %: $productTaxRate<br />";
        } 
        echo "<br />";
        echo "<br />";
        echo "End of test: testGetInvoiceByNumber". "<br />";
        echo "-------------------------------------------------------------------------------------<br />";
        return $invoice;
    }

}