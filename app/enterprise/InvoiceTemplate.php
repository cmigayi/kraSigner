<?php

namespace App\Enterprise;

class InvoiceTemplate{

    private $log;

    public function __construct($log){
        $this->log = $log;
    }

    public function genSignedHTMLTemplate($KRAQRCodeLink, $invoice){        
        $invoiceNumber = $invoice->InvoiceNumber;
        $invoiceOrderNumber = $invoice->OrderNumber;
        $invoiceDate = $invoice->InvoiceDate;
        $invoiceDueDate = $invoice->DueDate;
        $subTotal = $invoice->SubTotal;
        $total = $invoice->Total;
        $taxTotal = $invoice->TaxTotal;
        $paymentTerm = $invoice->PaymentTerm;
        $customerName = $invoice->Customer->CustomerName;
        $customerCode = $invoice->Customer->CustomerCode;
        $customerCurrencyId = $invoice->Customer->CurrencyId;
        $customerGuid = $invoice->Customer->Guid;
        $postalAddressStreetAddress = $invoice->PostalAddress->StreetAddress;
        $postalAddressStreetAddress2 = $invoice->PostalAddress->StreetAddress2;
        $postalAddressCity = $invoice->PostalAddress->City;
        $postalAddressCountry = $invoice->PostalAddress->Country; 
        $invoiceLines =  $invoice->InvoiceLines;  
        $prod1 = $invoiceLines[0]->Product->ProductDescription; 

        echo "<br/><==== Template creation started ====><br/>";
        echo "<br/>customer: $customerName, Invoice #: $invoiceNumber, Prod: $prod1";
        echo "<br/>";

        $htmlTemplate = "";        
        if(!empty($KRAQRCodeLink)){
            $baseurl = "http://localhost:8000";
            //$img = $_SERVER["DOCUMENT_ROOT"]."/".$qrCodePath;
            // $data = base64_encode($img);
            // $imgSrc = 'data:'.$img.';base64,'.$data;
            // echo $img."<br/>";
            $htmlTemplate = "
                <div style='padding: 10px'>
                    <div><img src='http://localhost:8000/svcinvoicetop.png'/></div>
                    <div border=1 style='margin-top: 10px; width: 950px; margin-left: 30px'>
                        <table style='width: 950px; font-size: 16px;'>
                            <tr style='text-align: left;'>
                                <th style='width: 350px;text-align: left;'>$customerName</th>
                                <th style='width: 200px;text-align: left;'>Invoice Date:</th>
                                <th style='width: 400px;text-align: left;'>Spring Valley Coffee Roasters Limited</th>
                            </tr>
                            <tr style='text-align: left;'>
                                <td style='width: 350px;'>
                                    $postalAddressStreetAddress <br/><br/>
                                    $postalAddressStreetAddress2<br/><br/>  
                                    $postalAddressCity<br/><br/> 
                                    $postalAddressCountry<br/><br/>
                                    P051137152X              
                                </td>
                                <td style='width: 200px;'>
                                    $invoiceDate<br/><br/> 
                                    <b>Invoice #</b><br/><br/>
                                    $invoiceNumber <br/><br/> 
                                    <b>Customer Ref</b>             
                                </td>
                                <td style='width: 400px;'>
                                    Spring Valley Shopping Centre, Shop 5<br/><br/>  
                                    Lower Kabete Road  <br/><br/>
                                    Nairobi Kenya +254 775 111 111 <br/>
                                    operations@springvalleycoffee.com <br/>
                                    <b>PIN: P051380899P</b>            
                                </td>
                            </tr>
                        </table>
                        <table style='width: 950px; font-size: 16px; margin-top: 70px; border-collapse: collapse;'>
                            <tr style='text-align: left; border-bottom: 2px solid rgb(122, 120, 120);'>
                                <th style='width: 350px;padding:2px;text-align: left;'>Description</th>
                                <th style='width: 100px;padding:2px;text-align: left;'>Qty</th>
                                <th style='width: 150px;padding:2px;text-align: left;'>Price</th>
                                <th style='width: 150px;padding:2px;text-align: left;'>Total</th>
                                <th style='width: 150px;padding:2px;text-align: left;'>Tax Total</th>
                                <th style='width: 50px;padding:2px;text-align: left;'>Tax %</th>
                            </tr>";
                            foreach($invoiceLines as $invoiceLine){  
                                $productDesc = $invoiceLine->Product->ProductDescription;                              
                                $htmlTemplate .= "
                                <tr style='text-align: left;border-bottom: 2px solid rgb(122, 120, 120);'>
                                    <td style='width: 350px;padding:2px;text-align: left;'>$productDesc</td>
                                    <td style='width: 100px;padding:2px;text-align: left;'>$invoiceLine->OrderQuantity</td>
                                    <td style='width: 100px;padding:2px;text-align: left;'>$invoiceLine->UnitPrice</td>
                                    <td style='width: 150px;padding:2px;text-align: left;'>$invoiceLine->LineTotal</td>
                                    <td style='width: 150px;padding:2px;text-align: left;'>$invoiceLine->LineTax</td>
                                    <td style='width: 100px;padding:2px;text-align: left;'>$invoiceLine->TaxRate</td>
                                </tr>";                                
                            }
                        $htmlTemplate .= "
                        </table>
                    </div>
                </div>
               KRA signing: <a href='".$KRAQRCodeLink."'>".$KRAQRCodeLink."'</a>
            "; 
            $this->log->info("Template generation successfully.");           
        }else{
            $this->log->info("Template generation failed."); 
        }        
        return $htmlTemplate;
    }
}