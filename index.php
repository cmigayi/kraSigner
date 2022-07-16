<?php
require_once('vendor/autoload.php');

/**
 * 1. getCustomer not getting one customer detail
 * 2. Date format issue
 * 3. Customer KRA PIN not found (Pxxxxxxx)
 * 3b. The account profile info
 * 5. Looping through API pages. Each page has 200 items 
 * 7. Schedule service (CRONJOB)  
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
use App\DataHandlers\TrackInvoiceDataHandler;
use App\Models\TrackInvoice;

$log = new ErrorLogger("ESDUnleashedApp");
$log = $log->initLog();

$config = include("Config.php");
$smtpServer = $config['smtp_server'];
$username = $config['email_username'];
$password = $config['email_password'];
$port = $config['port'];
$from = $config['from'];

$log->info("App execution started...");

$unleashedApi = new UnleashedApi($log);
//$unleashedInvoices = $unleashedApi->testGetInvoices();
$unleashedInvoices = json_decode(file_get_contents('raw_json.json'));
invoiceManager($unleashedInvoices, $log);

$log->info("App execution stopped...");


/**
 * Index Functions
 */

function invoiceManager($unleashedInvoices, $log){
    $count = 0;
    $pageSize = $unleashedInvoices->Pagination->PageSize; 
    $pageNumber = $unleashedInvoices->Pagination->PageNumber;
    $numberOfPages = $unleashedInvoices->Pagination->NumberOfPages;
    $numberOfItems = $unleashedInvoices->Pagination->NumberOfItems;

    $log->info("Endpoint details: ".$numberOfItems.", ".$pageSize.", ".$pageNumber.", ".$numberOfPages);
    
    foreach ($unleashedInvoices->Items as $invoice) {  

        $esdApi = new ESDApi($log);
        $KRAQRCodeLink = $esdApi->testPostInvoice($invoice);
        //$KRAQRCodeLink = "https://tims-test.kra.go.ke/KRA-Portal/invoiceChk.htm?actionCode=loadPage&invoiceNo=0100099570000000558";

        if(!empty($KRAQRCodeLink)){
            $qRCodeManager = new QRCodeManager();
            $qrCodePath = $qRCodeManager->genQRCode($KRAQRCodeLink); 
            $log->info("QRCODE link: $qrCodePath");
            // $qrCodePath = "https://external-content.duckduckgo.com/iu/?u=https%3A%2F%2Ftse2.mm.bing.net%2Fth%3Fid%3DOIP.DRI7RrDsCN0nAZpsjuZJIQHaHa%26pid%3DApi&f=1";
            // echo "<img src='".$qrCodePath."'/><br/>";
            // echo "KRA link: ".$KRAQRCodeLink."<br/>";

            $invoiceTemplate = new InvoiceTemplate($log);
            $htmlTemplate = $invoiceTemplate->genSignedHTMLTemplate($qrCodePath, $KRAQRCodeLink, $invoice);

            $htmlToPDFManager = new HTMLToPDFManager($log);
            $invoicePath = $htmlToPDFManager->genPDF($htmlTemplate);
            echo "<br/>".$invoicePath." created successfully!<br/><br/><br/>";            
            
            $subject = "Spring valley coffee invoice";
            $body = <<<HEREDOC
                <html>
                <head>
                <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet">
                    <style>
                    body, p {
                        font-family: 'Montserrat', sans-serif;
                    }
                    p {
                        line-height: 1.8em;
                        font-size:14px;
                    }
                    </style>
                </head>
                <body>
                    <table style=" width:100%;background:#F3F3F3;font-family: 'Montserrat', sans-serif;" cellpadding="10">
                        <tr>
                            <td align="center"><img src="{{asset('/logo.png')}}" alt="Logo" style="width:150px;"/></td>
                        </tr>
                    </table>
                    <table style="width:100%;background:#F3F3F3;" cellpadding="10">
                        <tr>
                            <td>
                                <table align="center" style="width:650px;background:#fff;font-family:'Calibri';padding: 10px 20px;">
                                    <tr>
                                        <td>
                                            <table rules="all" style="width:100%;" cellpadding="5" align="center">
                                                <tr>
                                                    <td>
                                                        <p align="justify">Hi {{$invoice->Customer->CustomerName}},</p>
                                                        <p align="justify">
                                                            Here's Invoice {{$invoice->InvoiceNumber}} for Ksh {{$invoice->Total}}
                </p>
                                                            <p align="justify">The amount outstanding of Ksh {{$invoice->total}} is due on {{$invoice->DueDate}}
                                                            </p>
                                                            <p align="justify">
                                                                View your bill online:
                </br>
                                                                <a href="$KRAQRCodeLink">$KRAQRCodeLink</a>
                                                            </p>
                                                            <p align="justify">
                                                                        From your online bill you can print a PDF, export a CSV, or create a free login and view your outstanding bills.
                                                            </p>
                                                            <p align="justify">
                                                                If you have any questions, please let us know.</p>
                                                            <p>
                                                                <br>
                                                                Thanks,
                                                                <br>
                                                                Spring Valley Coffee
                                                            </p>
                                                        </p>
                                                    </td>
                                                </tr>
                                            </table>
                                            <br>
                                        </td>
                                    </tr>
                                </table>
                                <table style=" width:100%;background:#F3F3F3;font-family:'Calibri';" cellpadding="10">
                                    <tr>
                                        <td></td>
                                    </tr>
                                    <tr>
                                        <td align="center" style="font-family:'Calibri';">
                                            <p style='text-align:center;'>
                                                <strong>E:</strong> 
                                                <a href="mailto:support@xxxx.com">support@xxxx.com</a> | 
                                                <strong>W:</strong> 
                                                <a href='https://www.springvalleycoffee.com' target='_blank'>Spring Valley Coffee</a>
                                            </p>
                                            <p style='text-align:center;'>Powered by <a href='https://souzy.tech'> © Souzy International Software Solutions.</a>All Rights Reserved</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </body>
            </html>            
            HEREDOC;
            $altbody = "";

            $emailManager = new EmailManager($log);
            $emailManager->setEmailSettings($smtpServer, $username, $password, $port);
            $emailManager->setEmailRecipients($from,'migayicecil@gmail.com','','','');
            $emailManager->setEmailAttachments($invoicePath);
            $emailManager->setEmailContent($subject, $body, $altbody);
            $emailManager->sendEmail();

            // DB
            $trackInvoice = new TrackInvoice();
            $trackInvoice->setInvoiceNumber($invoice->InvoiceNumber);
            $trackInvoice->setCustomerName($invoice->Customer->CustomerName);
            $trackInvoice->setInvoiceSigned("yes");
            $trackInvoice->setTemplateCreated("yes");
            $trackInvoice->setPdfCreated("yes");
            $trackInvoice->setEmailSent("yes");
            $trackInvoiceDataHandler =  new TrackInvoiceDataHandler($log);
            $trackInvoiceDataHandler->setData($trackInvoice);
            $trackInvoiceDataHandler->createTrackInvoice();
        }
    }
}