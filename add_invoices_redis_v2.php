<?php
ini_set("display_errors", 1);

ini_set("display_startup_errors", 1);

error_reporting(E_ALL);

require_once("vendor/autoload.php");

use App\Common\QRCodeManager;
use App\Esd\ESDApi;
use App\Unleashed\UnleashedApi;
use App\Common\HTMLToPDFManager;
use App\Common\EmailManager;
use App\Common\ErrorLogger; 
use App\Enterprise\InvoiceTemplate;
use App\DataHandlers\TrackInvoiceDataHandler;
use App\Models\TrackInvoice;
use App\Common\DateTimeManager;
use App\Common\MoneyManager;

$config = include("Config.php");
$redisServer = $config["redis_server"];
$redisPort = $config["redis_port"];
$redisPassword = $config["redis_password"];

try{
$redis = new Redis();
$redis->connect($redisServer, $redisPort);
$redis->auth($redisPassword);
}catch(Exception $e){
    echo $e->getMessage();
}

$log = new ErrorLogger("ESDUnleashedApp","add_redis_v2");
$log = $log->initLog();

$log->info("App execution started...");

$unleashedApi = new UnleashedApi($log);

$pageSize = 200; 
$pageNumber = 1;
$numberOfPages = 1;
$numberOfItems = 0;

while($pageNumber <= $numberOfPages){
    $today = date("Y-m-d");
    $unleashedInvoices = $unleashedApi->getInvoices("Invoices/Page/$pageNumber", "pageSize=$pageSize");
    // $unleashedInvoices = $unleashedApi->getInvoice("INV-00006827");
    //$request = "pageSize=$pageSize&startDate=$today";
    //$unleashedInvoices = $unleashedApi->getInvoices("Invoices/Page/$pageNumber", $request);
    //$unleashedInvoices = $unleashedApi->testGetInvoiceByNumber(); 
    $pageSize = $unleashedInvoices->Pagination->PageSize; 
    $pageNumber = $unleashedInvoices->Pagination->PageNumber;
    $numberOfPages = $unleashedInvoices->Pagination->NumberOfPages;
    $numberOfItems = $unleashedInvoices->Pagination->NumberOfItems;

    $log->info("Unleashed number of items: $numberOfItems");
            
    // Add to redis
    $trackInvoiceDataHandler =  new TrackInvoiceDataHandler($log);
    $isInvoiceAlreadySigned = false;

    $dataArr = array("test ronny","test kenny","test rocky","test ireen","test ricky","test morris","test gg","test nelson"); 
    foreach ($unleashedInvoices->Items as $invoice) { 
        $log->info("**************************New Invoice***********************************"); 
        // Get customer Guid
        $customerGuid = $invoice->Customer->Guid;
        $log->info("Customer Guid: $customerGuid"); 

        // Use customer Guid to get customer info
        $unleashedApi = new UnleashedApi($log);
        $svcCustomer = $unleashedApi->getCustomer("Customers/$customerGuid");
        $log->info("Buyer pin number: $svcCustomer->GSTVATNumber");

        // Get invoice number
        $invoiceNumber = $invoice->InvoiceNumber;
        $log->info("Invoice number: $invoiceNumber"); 

        $invoiceLinesJson = json_encode ((array)$invoice->InvoiceLines);
        $svcCustomerJson = json_encode ((array)$svcCustomer);
        $invoiceJson = json_encode ((array)$invoice);
        $unleashedInvoicesJson = json_encode ((array)$unleashedInvoices);

        $log->info("invoicelines json:". $invoiceLinesJson);

        // Check if invoice is already signed
        $isInvoiceAlreadySigned = $trackInvoiceDataHandler->isTrackInvoiceSigned($invoiceNumber);
        if($isInvoiceAlreadySigned == false){ 
            $data = [
                "invoice_number" => $invoiceNumber,
                'pin_of_buyer' => $svcCustomer->GSTVATNumber,
                'total' => $invoice->Total,
                'invoice_lines_json' => $invoiceLinesJson,
                'customer_json' => $svcCustomerJson,
                'invoice_json' => $invoiceJson,
                'unleashed_invoices_json' => $unleashedInvoicesJson 
            ];
                    
            $redis->rpush("invoices_register", json_encode($data));
                        
            $log->info("Redis: test details accepted successfully.");

            sleep(5);
        }
    }

    $pageNumber = $pageNumber+1; 
}

$log->info("App execution stopped...");
