<?php
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

$log = new ErrorLogger("ESDUnleashedApp","retrieve_redis");
$log = $log->initLog();

$config = include("Config.php");
$smtpServer = $config["smtp_server"];
$username = $config["email_username"];
$password = $config["email_password"];
$redisServer = $config["redis_server"];
$redisPort = $config["redis_port"];
$redisPassword = $config["redis_password"];
$port = $config["port"];
$from = $config["from"];    

$invoiceSigned = false;
$qrcodeCreated = false;
$templateCreated = false;
$pdfCreated = false;
$emailSent = false;
$isInvoiceAlreadySigned = false;
$customerEmailStatus = false;
$customerEmail = "";
$customerEmailCC = "";

$trackInvoiceDataHandler =  new TrackInvoiceDataHandler($log);

$log->info("App execution started...");

try {
    $redis = new Redis();
    $redis->connect($redisServer, $redisPort);
    $redis->auth($redisPassword);

    $data = $redis->lpop("invoices_register");
    $data  = json_decode($data, true); 

    if($data == null){
        $log->info("Redis queue is empty. No signing can be done.");  
    }else{
        $log->info("-------------------------------New Invoice--------------------------");              
    
        $invoiceNumber = $data['invoice_number'];
        $svcCustomer = json_decode($data['customer_json']);
        $invoice = json_decode($data['invoice_json']);
        $unleashedApi = json_decode($data['unleashed_invoices_json']);

        // Sign invoice with ESD 
        $log->info("Invoice number: $invoiceNumber, signing starts..."); 
        $esdApi = new ESDApi($log);
        //$KRAQRCodeLink = $esdApi->testPostInvoice($invoice, $svcCustomer, $unleashedApi);
        $log->info("KRA link: $KRAQRCodeLink"); 
        $KRAQRCodeLink = "kra link";

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
            $subject = "Spring Valley Coffee Invoice";
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

} catch (Exception $e) {
    $log->error($e->getMessage());
}

$log->info("App execution stopped...");