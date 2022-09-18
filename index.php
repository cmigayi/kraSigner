<?php
ini_set("display_errors", 1);

ini_set("display_startup_errors", 1);

error_reporting(E_ALL);
require_once("vendor/autoload.php");

/**
 * 3b. The account profile info
 * 7. Schedule service (CRONJOB)  
 * 9. Pay attention of the http codes
 * 10. .env / config
 * 
 */

use App\Common\QRCodeManager;
use App\Esd\ESDApi;
use App\Unleashed\UnleashedApi;
use App\Common\HTMLToPDFManager;
use App\Common\EmailManager;
use Spatie\Async\Pool;
use App\Common\ErrorLogger; 
use App\Enterprise\InvoiceTemplate;
use App\Enterprise\CSVExcelManager;
use App\DataHandlers\TrackInvoiceDataHandler;
use App\Models\TrackInvoice;
use App\Common\DateTimeManager;
use App\Common\MoneyManager;

$log = new ErrorLogger("ESDUnleashedApp");
$log = $log->initLog();

$log->info("App execution started...");

invoiceStuff($log);

// $unleashedApi = new UnleashedApi($log);
//$unleashedApi->getInvoices("json", "Invoices/Page/1", "");
//$unleashedInvoices = $unleashedApi->testGetInvoices();
// $unleashedInvoices = json_decode(file_get_contents("raw_json.json"));
// invoiceManager($unleashedInvoices, $log);

$log->info("App execution stopped...");

/**
 * Index Functions
 */

function invoiceStuff($log){
    $unleashedApi = new UnleashedApi($log);

    $pageSize = 200; 
    $pageNumber = 1;
    $numberOfPages = 1;
    $numberOfItems = 0;

    while($pageNumber <= $numberOfPages){
        $today = date("Y-m-d");
        // $unleashedInvoices = $unleashedApi->getInvoices("Invoices/Page/$pageNumber", "pageSize=$pageSize");
        $unleashedInvoices = $unleashedApi->getInvoice("INV-00006827");
        // $request = "pageSize=$pageSize&startDate=$today";
        // $unleashedInvoices = $unleashedApi->getInvoices("Invoices/Page/$pageNumber", $request);
        //$unleashedInvoices = $unleashedApi->testGetInvoiceByNumber(); 
        $pageSize = $unleashedInvoices->Pagination->PageSize; 
        $pageNumber = $unleashedInvoices->Pagination->PageNumber;
        $numberOfPages = $unleashedInvoices->Pagination->NumberOfPages;
        $numberOfItems = $unleashedInvoices->Pagination->NumberOfItems;

        $log->info($numberOfItems);
            
        invoiceManager($unleashedInvoices, $log);

        $pageNumber = $pageNumber+1; 
    }
       
}

function booleanToMysqlHandler($boolean){
    if($boolean){
        return 1;
    }
    return 0;
} 

function emailMessageGen($svcCustomer, $invoice, $KRAQRCodeLink, $log){ 
    $dateTimeManager = new DateTimeManager($log);
    $moneyManager = new MoneyManager($log);   
    $invoiceNumber = $invoice->InvoiceNumber;
    $total = $moneyManager->formatToMoney($invoice->Total);
    $invoiceDueDate = $dateTimeManager->getDateFromUnreadableDateEpochDate($invoice->DueDate);
    $fname = $svcCustomer->ContactFirstName; 
    $hiToFname = "Hi $fname,";
    if(empty($fname)){
        $hiToFname = "Hi,";
    }
    $message = "
    <div>
    <p>$hiToFname<p>

    <p>Here&#39;s invoice $invoiceNumber for KES $total.<p>
    
    <p>The amount outstanding of KES $total is due on $invoiceDueDate.</p>
    
    <p>View your bill online: $KRAQRCodeLink<p>
    
    <p>
    From your online bill you can print a PDF, export a CSV, or create a free login and view your
    outstanding bills.
    </p>
    
    <p>If you have any questions, please let us know.</p>
    
    Thanks,<br/>
    Spring Valley Coffee Roasters Limited
    </div>
    ";
    return $message;
}

function invoiceManager($unleashedInvoices, $log){
    $config = include("Config.php");
    $smtpServer = $config["smtp_server"];
    $username = $config["email_username"];
    $password = $config["email_password"];
    $port = $config["port"];
    $from = $config["from"];    

    $invoiceSigned = false;
    $qrcodeCreated = false;
    $templateCreated = false;
    $pdfCreated = false;
    $emailSent = false;
    $customerEmail = "";
    $customerEmailStatus = false;
    $customerEmailCC = "";

    $trackInvoiceDataHandler =  new TrackInvoiceDataHandler($log);
    $isInvoiceAlreadySigned = false;

    foreach ($unleashedInvoices->Items as $invoice) {
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

        // Check if invoice is already signed
        $isInvoiceAlreadySigned = $trackInvoiceDataHandler->isTrackInvoiceSigned($invoiceNumber);
        if($isInvoiceAlreadySigned == false){
            // Sign invoice with ESD 
            $log->info("Invoice number: $invoiceNumber, signing starts..."); 
            $esdApi = new ESDApi($log);
            $KRAQRCodeLink = $esdApi->testPostInvoice($invoice, $svcCustomer, $unleashedApi);
            $log->info("KRA link: $KRAQRCodeLink"); 
            $KRAQRCodeLink = "kra link";
        }

        $qrCodePath = "";
        $invoicePDFPath = "";        

        if(!empty($KRAQRCodeLink)){
            $invoiceSigned = true;
            $log->info("Invoice is signed: $KRAQRCodeLink");            
        }else{
            $log->info("Invoice is not signed. Check if ESD API is still working.");
        }

        if($invoiceSigned){
            $qRCodeManager = new QRCodeManager();
            $qrCodePath = $qRCodeManager->genQRCode($KRAQRCodeLink); 
        }

        if(!empty($qrCodePath)){
            $qrcodeCreated = true;
            $log->info("Invoice QRCode is created: $qrCodePath");

            $invoiceTemplate = new InvoiceTemplate($log);
            $htmlTemplateArray = $invoiceTemplate->genSignedHTMLTemplate($qrCodePath, $KRAQRCodeLink, $invoice, $svcCustomer);         
            $log->info("Customer contacts: Email-$htmlTemplateArray[1], CC-$htmlTemplateArray[2]");

            if($htmlTemplateArray[0] != null){
                $htmlTemplate = $htmlTemplateArray[0];
            } 
            if($htmlTemplateArray[1] != null){
                $customerEmail = $htmlTemplateArray[1];
                $customerEmailStatus = true;
            }            
            if($htmlTemplateArray[2] != null){
                $customerEmailCC = $htmlTemplateArray[2];
            }             
            $templateCreated = true;
            $log->info("Invoice template is created.");
        }else{
            $log->info("Invoice QRCode is not created. Check QRCode creator/generator.");
        }

        if($templateCreated){
            $htmlToPDFManager = new HTMLToPDFManager($log);
            $invoicePDFPath = $htmlToPDFManager->genPDF($htmlTemplate,$invoice);  
        }else{
            $log->info("Invoice template is not created. Check template creator/generator.");
        }

        if(!empty($invoicePDFPath)){
            $pdfCreated = true;
            $log->info("Invoice PDF is created: $KRAQRCodeLink");            
        }else{
            $log->info("Invoice PDF is not signed. Check HTML to PDF creator/generator.");
        }

        if($pdfCreated && $invoicePDFPath && $customerEmailStatus){
            // $to = $customerEmail;
            $to = "migayicecil@gmail.com";
            $subject = "Spring valley coffee invoice";
            $altbody = "";
            $emailManager = new EmailManager($log);
            $emailManager->setEmailSettings($smtpServer, $username, $password, $port);
            $emailManager->setEmailRecipients($from,$to,"","","");
            $emailManager->setEmailAttachments($invoicePDFPath);
            $body = emailMessageGen($svcCustomer, $invoice, $KRAQRCodeLink, $log);                        
            $emailManager->setEmailContent($subject, $body, $altbody);            
            if($emailManager->sendEmail()){
                $emailSent = true;
                $log->info("Email is sent to $to"); 
            }else{
                $log->info("Email failed to send to $to"); 
            }
        }

        if($invoiceSigned){
            // DB
            $trackInvoice = new TrackInvoice();
            $trackInvoice->setInvoiceNumber($invoice->InvoiceNumber);
            $trackInvoice->setCustomerName($invoice->Customer->CustomerName);
            $trackInvoice->setCustomerEmail($customerEmail);
            $trackInvoice->setCustomerEmailCC($customerEmailCC);
            $trackInvoice->setInvoiceSigned(booleanToMysqlHandler($invoiceSigned));
            $trackInvoice->setQRCodeCreated(booleanToMysqlHandler($qrcodeCreated));
            $trackInvoice->setTemplateCreated(booleanToMysqlHandler($templateCreated));
            $trackInvoice->setPdfCreated(booleanToMysqlHandler($pdfCreated));
            $trackInvoice->setEmailSent(booleanToMysqlHandler($emailSent));

            $trackInvoiceDataHandler->setData($trackInvoice);
            $trackInvoice = $trackInvoiceDataHandler->createTrackInvoice();

            if(!empty($trackInvoice->getInvoiceNumber())){
                $log->info("Track invoice info saved: $invoice->InvoiceNumber)"); 
            }else{
                $log->info("Track invoice info failed to save: $invoice->InvoiceNumber)");
            }
        }              
    }
}