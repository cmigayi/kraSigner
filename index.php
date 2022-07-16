<?php
require_once('vendor/autoload.php');

/**
 * 1. getCustomer not getting one customer detail
 * 2. Date format issue
 * 3. Customer KRA PIN not found (Pxxxxxxx)
 * 3b. The account profile info
 * 4. EMail takes too long **
 * 5. Looping through API pages. Each page has 200 items 
 * 7. Schedule service (CRONJOB)  
 * 8. Track sent and unsent invoices using a CSV/excel file/mysql-db
 * 9. Pay attention of the http codes
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

$log = new ErrorLogger("ESDUnleashedApp");
$log = $log->initLog();

$log->info("App execution started...");

$unleashedApi = new UnleashedApi($log);
//$unleashedInvoices = $unleashedApi->testGetInvoices();
$unleashedInvoices = json_decode(file_get_contents('raw_json.json'));
// $pathToCsv = "invoice_report.xlsx";
// $cSVExcelManager = new CSVExcelManager($pathToCsv);
invoiceManager($unleashedInvoices, $log);

//$esdApi = new ESDApi($log);
// $KRAQRCodeLink = $esdApi->testPostInvoice($unleashedInvoice);
//$KRAQRCodeLink = "https://tims-test.kra.go.ke/KRA-Portal/invoiceChk.htm?actionCode=loadPage&invoiceNo=0100099570000000558";

// if(!empty($KRAQRCodeLink)){
//     // $qRCodeManager = new QRCodeManager();
//     // $qrCodePath = $qRCodeManager->genQRCode($KRAQRCodeLink);
//     // echo "<img src='".$qrCodePath."'/><br/>";
//     echo "KRA link: ".$KRAQRCodeLink."<br/>";

//     $invoiceTemplate = new InvoiceTemplate($log);
//     $htmlTemplate = $invoiceTemplate->genSignedHTMLTemplate($KRAQRCodeLink, "");

//     $htmlToPDFManager = new HTMLToPDFManager($log);
//     $invoicePath = $htmlToPDFManager->genPDF($htmlTemplate);
//     echo "<br/>".$invoicePath." created successfully!"; 

//     $emailManager = new EmailManager();
//     $emailManager->setEmailSettings('smtp.gmail.com','migayicecil@gmail.com','pfplxbsufsaayjio',587);
//     $emailManager->setEmailRecipients('migayicecil@gmail.com','migayicecil@gmail.com','','','');
//     $emailManager->setEmailAttachments($invoicePath);
//     $emailManager->setEmailContent('Invoice attachment sent','Find invoice attachment','Find invoice attachment');
//     $emailManager->sendEmail();
// }

$log->info("App execution stopped...");

function invoiceManager($unleashedInvoices, $log){
    $count = 0;
    
    foreach ($unleashedInvoices->Items as $invoice) {  
        $pageSize = $unleashedInvoices->Pagination->PageSize; 
        $pageNumber = $unleashedInvoices->Pagination->PageNumber;
        $numberOfPages = $unleashedInvoices->Pagination->NumberOfPages;
        $numberOfItems = $unleashedInvoices->Pagination->NumberOfItems;

        $log->info("Endpoint details: ".$numberOfItems.", ".$pageSize.", ".$pageNumber.", ".$numberOfPages);

        $esdApi = new ESDApi($log);
        $KRAQRCodeLink = $esdApi->testPostInvoice($invoice);
        // $KRAQRCodeLink = "https://tims-test.kra.go.ke/KRA-Portal/invoiceChk.htm?actionCode=loadPage&invoiceNo=0100099570000000558";

        if(!empty($KRAQRCodeLink)){
            $qRCodeManager = new QRCodeManager();
            $qrCodePath = $qRCodeManager->genQRCode($KRAQRCodeLink);
            // $qrCodePath = "https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse2.mm.bing.net%2Fth%3Fid%3DOIP.DRI7RrDsCN0nAZpsjuZJIQHaHa%26pid%3DApi&f=1";
            // echo "<img src='".$qrCodePath."'/><br/>";
            echo "KRA link: ".$KRAQRCodeLink."<br/>";

            $invoiceTemplate = new InvoiceTemplate($log);
            $htmlTemplate = $invoiceTemplate->genSignedHTMLTemplate($qrCodePath, $KRAQRCodeLink, $invoice);

            $htmlToPDFManager = new HTMLToPDFManager($log);
            $invoicePath = $htmlToPDFManager->genPDF($htmlTemplate);
            echo "<br/>".$invoicePath." created successfully!<br/><br/><br/>";  

            $templateStatus = "Yes";
            $emailStatus = "No";
            $signingStatus = "Yes";     

            $emailManager = new EmailManager();
            $emailManager->setEmailSettings('smtp.gmail.com','migayicecil@gmail.com','pfplxbsufsaayjio',587);
            $emailManager->setEmailRecipients('migayicecil@gmail.com','migayicecil@gmail.com','','','');
            $emailManager->setEmailAttachments($invoicePath);
            $emailManager->setEmailContent('Invoice attachment sent','Find invoice attachment','Find invoice attachment');
            $emailManager->sendEmail();
        }
    }
}