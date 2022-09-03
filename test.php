<?php
// $date = "/Date(1658147439398)/";
// $date1 = explode("(",$date);
// $date2 = explode(")",$date1[1]);
// //echo substr($date, 7,10);
// //$epoch = substr($date[0], 7,10);
// //$epoch2 = 1655958857230;
// echo date("Y-m-d H:i:s", substr($date2[0], 0, -3));
// //echo substr($epoch, 0, 10);
// setlocale(LC_MONETARY,"en_US");
// echo number_format(300, 2);
// phpinfo();

function getCurl($endpoint, $requestUrl, $format) {  
    $api = "https://197.248.30.164:5000/EsdApi/deononline/";      
    $curl = curl_init($api . $endpoint . $requestUrl);
    echo "<br/>".$api . $endpoint . $requestUrl;
    try{
        curl_setopt($curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-Type: application/$format",
            "Accept: application/$format"));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); 
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 20);
        // these options allow us to read the error message sent by the API
        curl_setopt($curl, CURLOPT_FAILONERROR, false);
        curl_setopt($curl, CURLOPT_HTTP200ALIASES, range(400, 599));
    }catch (Exception $e) {
        echo 'ESD api error: ' + $e;
    }

    return $curl;
}

function post($endpoint, $format, $data) {
    if (!isset($data)) { return null; }

    try {
      echo "Post function:";
      echo $endpoint;
      // create the curl object.
      // - POST always requires the object's id
      $curl = getCurl("$endpoint", "", $format);    
      // set extra curl options required by POST
      curl_setopt($curl, CURLOPT_POST, 1);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
      // POST something
      $curl_result = curl_exec($curl);            
      //echo "ESD API post endpoint: ".$curl_result; 
      curl_close($curl);
      return $curl_result;
    }
    catch (Exception $e) {
        echo 'ESD api error: '.$e->ErrorInfo;
    }
}

function postJson($endpoint, $data) {
    // POST it, return the API's response
    //echo json_encode($data);
    return post($endpoint, "json", json_encode($data));
}

function postInvoice($invoice) {
    return postJson("signinvoice/", $invoice); 
}

$invoice = new \stdClass();
        $invoiceItems = array();

        $invoiceItem = new \stdClass();
            $invoiceItem->hsDesc = "";
            $invoiceItem->namePLU = "Gourmet \u2022 250g \u2022 Fine";
            $invoiceItem->taxRate = 16;
            $invoiceItem->unitPrice = 1;
            $invoiceItem->discount = 0;
            $invoiceItem->hsCode = "";
            $invoiceItem->quantity = 1;
            $invoiceItem->measureUnit = "kg";
            $invoiceItem->vatClass = "A";
            array_push($invoiceItems, $invoiceItem);        
        $invoice->deonItemDetails = $invoiceItems;
        $invoice->senderId = "a4031de9-d11f-4b52-8cca-e1c7422f3c37";
        $invoice->invoiceCategory = "tax_invoice";
        $invoice->traderSystemInvoiceNumber = 1234;
        $invoice->relevantInvoiceNumber = "INV-00006827";
        $invoice->pinOfBuyer = null;
        $invoice->invoiceType = "Original";
        $invoice->exemptionNumber = "";
        $invoice->totalInvoiceAmount = 1.16;
        $invoice->systemUser = "Joe Doe";
   echo json_decode(postInvoice($invoice));     
?>