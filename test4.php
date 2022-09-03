<?php

$curl = curl_init();

$data = array(
    "hsDesc" => "",
    "namePLU" => "Gourmet \u2022 250g \u2022 Fine",
    "taxRate" => 16,
    "unitPrice" => 1,
    "discount" => 0,
    "hsCode" => "",
    "quantity" => 1,
    "measureUnit" => "kg",
    "vatClass" => "A"
);
$data2 = array();
array_push($data2,$data);
$data3 = array(
    "deonItemDetails" => $data2,
    "senderId" => "a4031de9-d11f-4b52-8cca-e1c7422f3c37",
    "invoiceCategory" => "tax_invoice",
    "traderSystemInvoiceNumber" => 1234,
    "relevantInvoiceNumber" => "INV-00006827",
    "pinOfBuyer" => null,
    "invoiceType" => "Original",
    "exemptionNumber" => "",
    "totalInvoiceAmount" => 1.16,
    "systemUser" => "Joe Doe"
);

$payload = json_encode($data3);

curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://197.248.30.164:5000/EsdApi/deononline/signinvoice/',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_SSL_VERIFYHOST => false,
  CURLOPT_SSL_VERIFYPEER => false,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => $payload,
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;