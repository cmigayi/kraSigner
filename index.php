<?php
require_once('vendor/autoload.php');

/**
 * 2. Date format issue
 * 3b. The account profile info
 * 7. Schedule service (CRONJOB)  
 * 9. Pay attention of the http codes
 * 10. Email content
 * 11. Mysql customers emails null
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

$log = new ErrorLogger("ESDUnleashedApp");
$log = $log->initLog();

$log->info("App execution started...");

invoiceStuff($log);

// $unleashedApi = new UnleashedApi($log);
//$unleashedApi->getInvoices("json", "Invoices/Page/1", "");
//$unleashedInvoices = $unleashedApi->testGetInvoices();
// $unleashedInvoices = json_decode(file_get_contents('raw_json.json'));
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
        $unleashedInvoices = $unleashedApi->getInvoices("Invoices/Page/$pageNumber", "pageSize=$pageSize");

        $pageSize = $unleashedInvoices->Pagination->PageSize; 
        $pageNumber = $unleashedInvoices->Pagination->PageNumber;
        $numberOfPages = $unleashedInvoices->Pagination->NumberOfPages;
        $numberOfItems = $unleashedInvoices->Pagination->NumberOfItems;
            
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

function invoiceManager($unleashedInvoices, $log){
    $config = include("Config.php");
    $smtpServer = $config['smtp_server'];
    $username = $config['email_username'];
    $password = $config['email_password'];
    $port = $config['port'];
    $from = $config['from'];

    $invoiceSigned = false;
    $qrcodeCreated = false;
    $templateCreated = false;
    $pdfCreated = false;
    $emailSent = false;
    $customerEmail = "";
    $customerEmailCC = "";

    foreach ($unleashedInvoices->Items as $invoice) {
        $esdApi = new ESDApi($log);
        $KRAQRCodeLink = $esdApi->testPostInvoice($invoice);
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
            $htmlTemplateArray = $invoiceTemplate->genSignedHTMLTemplate($qrCodePath, $KRAQRCodeLink, $invoice);            
            $htmlTemplate = ($htmlTemplateArray[0] == null) ? "" : $htmlTemplateArray[0];
            $customerEmail = ($htmlTemplateArray[1] == null) ? "" : $htmlTemplateArray[1]; 
            $customerEmailCC = ($htmlTemplateArray[2] == null) ? "" : $htmlTemplateArray[2];
            $templateCreated = true;
            $log->info("Invoice template is created.");
        }else{
            $log->info("Invoice QRCode is not created. Check QRCode creator/generator.");
        }

        if($templateCreated){
            $htmlToPDFManager = new HTMLToPDFManager($log);
            $invoicePDFPath = $htmlToPDFManager->genPDF($htmlTemplate);  
        }else{
            $log->info("Invoice template is not created. Check template creator/generator.");
        }

        if(!empty($invoicePDFPath)){
            $pdfCreated = true;
            $log->info("Invoice PDF is created: $KRAQRCodeLink");            
        }else{
            $log->info("Invoice PDF is not signed. Check HTML to PDF creator/generator.");
        }

        if($pdfCreated && $invoicePDFPath){
            $to = 'migayicecil@gmail.com';
            $subject = "Spring valley coffee invoice";
            $altbody = "";
            $emailManager = new EmailManager($log);
            $emailManager->setEmailSettings($smtpServer, $username, $password, $port);
            $emailManager->setEmailRecipients($from,$to,'','','');
            $emailManager->setEmailAttachments($invoicePDFPath);
            $body = <<<HEREDOC
            Testing content            
            HEREDOC;            
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
            $trackInvoiceDataHandler =  new TrackInvoiceDataHandler($log);
            $trackInvoiceDataHandler->setData($trackInvoice);

            $trackInvoice = new TrackInvoice();
            $trackInvoice = $trackInvoiceDataHandler->createTrackInvoice();

            if(!empty($trackInvoice->getInvoiceNumber())){
                $log->info("Track invoice info saved: $invoice->InvoiceNumber)"); 
            }else{
                $log->info("Track invoice info failed to save: $invoice->InvoiceNumber)");
            }
        }              
    }
}