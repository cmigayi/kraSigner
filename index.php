<?php
require_once('vendor/autoload.php');

use App\Common\QRCodeManager;
use App\Esd\ESDApi;
use App\Unleashed\UnleashedApi;
use App\Common\HTMLToPDFManager;
use App\Common\EmailManager;
use Spatie\Async\Pool;
use App\Common\ErrorLogger; 
use App\Enterprise\InvoiceTemplate;

$log = new ErrorLogger("ESDUnleashedApp");
$log = $log->initLog();

$log->info("App execution started...");

$unleashedApi = new UnleashedApi($log);
$unleashedInvoices = $unleashedApi->testGetInvoices();
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
    foreach ($unleashedInvoices->Items as $invoice) {   

        //$esdApi = new ESDApi($log);
        // $KRAQRCodeLink = $esdApi->testPostInvoice($unleashedInvoice);
        $KRAQRCodeLink = "https://tims-test.kra.go.ke/KRA-Portal/invoiceChk.htm?actionCode=loadPage&invoiceNo=0100099570000000558";

        if(!empty($KRAQRCodeLink)){
            // $qRCodeManager = new QRCodeManager();
            // $qrCodePath = $qRCodeManager->genQRCode($KRAQRCodeLink);
            // echo "<img src='".$qrCodePath."'/><br/>";
            echo "KRA link: ".$KRAQRCodeLink."<br/>";

            $invoiceTemplate = new InvoiceTemplate($log);
            $htmlTemplate = $invoiceTemplate->genSignedHTMLTemplate($KRAQRCodeLink, $invoice);

            $htmlToPDFManager = new HTMLToPDFManager($log);
            $invoicePath = $htmlToPDFManager->genPDF($htmlTemplate);
            echo "<br/>".$invoicePath." created successfully!<br/><br/><br/>"; 

            // $emailManager = new EmailManager();
            // $emailManager->setEmailSettings('smtp.gmail.com','migayicecil@gmail.com','pfplxbsufsaayjio',587);
            // $emailManager->setEmailRecipients('migayicecil@gmail.com','migayicecil@gmail.com','','','');
            // $emailManager->setEmailAttachments($invoicePath);
            // $emailManager->setEmailContent('Invoice attachment sent','Find invoice attachment','Find invoice attachment');
            // $emailManager->sendEmail();
        }
    }
}