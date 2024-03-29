<?php

namespace App\Enterprise;

use App\Unleashed\UnleashedApi;
use App\Common\DateTimeManager;
use App\Common\MoneyManager;

class InvoiceTemplate{

    private $log;
    private $dateTimeManager;
    private $moneyManager;

    public function __construct($log){
        $this->log = $log;
        $this->dateTimeManager = new DateTimeManager($this->log);
        $this->moneyManager = new MoneyManager($this->log);
    }

    public function genSignedHTMLTemplate($qrCodePath, $KRAQRCodeLink, $invoice, $svcCustomer){ 
        $qrCodePath = 'http://'.$_SERVER['SERVER_NAME'].'/'.$qrCodePath;
        $this->log->info('QRCode full Path: '.$qrCodePath);        
        $invoiceNumber = $invoice->InvoiceNumber;
        $invoiceOrderNumber = $invoice->OrderNumber;
        $invoiceDate = $this->dateTimeManager->getDateFromUnreadableDateEpochDate($invoice->InvoiceDate);
        $invoiceDueDate = $this->dateTimeManager->getDateFromUnreadableDateEpochDate($invoice->DueDate);
        $subTotal = $this->moneyManager->formatToMoney($invoice->SubTotal);
        $total = $this->moneyManager->formatToMoney($invoice->Total);
        $taxTotal = $this->moneyManager->formatToMoney($invoice->TaxTotal);
        $paymentTerm = $invoice->PaymentTerm;
        $customerName = $invoice->Customer->CustomerName;
        $customerCode = $invoice->Customer->CustomerCode;
        $customerCurrencyId = $invoice->Customer->CurrencyId;
        $customerGuid = $invoice->Customer->Guid;
        $postalAddressStreetAddress = $invoice->PostalAddress->StreetAddress;
        $postalAddressStreetAddress2 = $invoice->PostalAddress->StreetAddress2;
        $postalAddressCity = $invoice->PostalAddress->City;
        $postalAddressCountry = $invoice->PostalAddress->Country; 
        $invoiceLines = $invoice->InvoiceLines; 

        $customerEmail = $svcCustomer->Email;
        $customerEmailCC = $svcCustomer->EmailCC;
        $customerGSTVATNumber = $svcCustomer->GSTVATNumber;

        $this->log->info('Customer info: Email-$customerEmail, CC-$customerEmailCC, VAT-$customerGSTVATNumber');     

        $htmlTemplate = '';        
        if(!empty($KRAQRCodeLink)){

            $htmlTemplate = "
                <div style='padding: 10px;font-size: 14px;'>  
                        <table style='width: 950px;'>
                            <tr style='text-align: left;'>
                                <td style='width:500px;'>
                                    <span style='margin-left: 45px; font-size: 25px; color: #232323; font-weight: bold; font-family: Arial, Helvetica, sans-serif;'>
                                        INVOICE + DELIVERY NOTE
                                    </span>
                                </td>            
                                <td style='width: 400px;'>
                                    <img style='margin-left: 150px;' width='150px' height='100px' src='https://images.squarespace-cdn.com/content/v1/5b69f9f37e3c3af551b48958/1559157173218-VJDY1ISN5QYWIOSLS5X7/Logo+-+Grey.png?format=300w'/>
                                </td>
                            </tr>
                        </table> 
                    <div style='margin-top: 5px; width: 950px; margin-left: 30px'>
                        <table style='width: 950px;'>
                            <tr style='text-align: left;'>
                                <th style='width: 350px;text-align: left;'>$customerName</th>
                                <th style='width: 200px;text-align: left;'>Invoice Date:</th>
                                <th style='width: 400px;text-align: left;'>Spring Valley Coffee Roasters Limited</th>
                            </tr>
                            <tr style='text-align: left;'>
                                <td style='width: 350px;'>
                                    $postalAddressStreetAddress <br/>
                                    $postalAddressStreetAddress2<br/>  
                                    $postalAddressCity<br/> 
                                    $postalAddressCountry<br/>
                                    $customerGSTVATNumber              
                                </td>
                                <td style='width: 200px;'>
                                    $invoiceDate<br/> 
                                    <b>Invoice #</b><br/>
                                    $invoiceNumber <br/> 
                                    <b>Customer Ref</b>             
                                </td>
                                <td style='width: 400px;'>
                                    Spring Valley Shopping Centre, Shop 5<br/>  
                                    Lower Kabete Road  <br/>
                                    Nairobi Kenya +254 775 111 111 <br/>
                                    operations@springvalleycoffee.com <br/>
                                    <b>PIN: P051380899P</b>            
                                </td>
                            </tr>
                        </table>
                        <table style='width: 950px;margin-top: 20px; border-collapse: collapse;'>
                            <tr style='text-align: left; border-bottom: 2px solid rgb(122, 120, 120);'>
                                <th style='width: 350px;padding:2px;text-align: left;'>Description</th>
                                <th style='width: 100px;padding:2px;text-align: left;'>Qty</th>
                                <th style='width: 150px;padding:2px;text-align: left;'>Price</th>
                                <th style='width: 150px;padding:2px;text-align: left;'>Total</th>
                                <th style='width: 150px;padding:2px;text-align: left;'>Tax Total</th>
                                <th style='width: 50px;padding:2px;text-align: left;'>Tax %</th>
                            </tr>';
                            foreach($invoiceLines as $invoiceLine){  
                                $productDesc = $invoiceLine->Product->ProductDescription;
                                $unitPrice = $this->moneyManager->formatToMoney($invoiceLine->UnitPrice);
                                $lineTotal = $this->moneyManager->formatToMoney($invoiceLine->LineTotal);
                                $lineTax = $this->moneyManager->formatToMoney($invoiceLine->LineTax);
                                $taxRate = $invoiceLine->TaxRate * 100;

                                $htmlTemplate .= '
                                <tr style='text-align: left;border-bottom: 2px solid rgb(122, 120, 120);'>
                                    <td style='width: 350px;padding:2px;text-align: left;'>$productDesc</td>
                                    <td style='width: 100px;padding:2px;text-align: left;'>$invoiceLine->OrderQuantity</td>
                                    <td style='width: 100px;padding:2px;text-align: left;'>$unitPrice</td>
                                    <td style='width: 150px;padding:2px;text-align: left;'>$lineTotal</td>
                                    <td style='width: 150px;padding:2px;text-align: left;'>$lineTax</td>
                                    <td style='width: 100px;padding:2px;text-align: left;'>$taxRate%</td>
                                </tr>';                                
                            }
                        $htmlTemplate .= '
                        </table>
                        <table style='width: 400px;margin-top: 10px; margin-left: 550px;border-collapse: collapse;'>
                            <tr style='text-align: left;'>
                                <td style='width: 200px;padding:2px;'><b>SUBTOTAL (KES)</b></td>
                                <td style='width: 200px;padding:2px;'>$subTotal</td>
                            </tr>
                            <tr style='text-align: left;'>
                                <td style='width: 200px;padding:2px;'><b>CHARGE SUBTOTAL (KES)</b></td>
                                <td style='width: 200px;padding:2px;'>0.00</td>
                            </tr>
                            <tr style='text-align: left;'>
                                <td style='width: 200px;padding:2px;'><b>TAX (KES)</b></td>
                                <td style='width: 200px;padding:2px;'>$taxTotal</td>
                            </tr>
                            <tr style='text-align: left;border-top: 2px solid rgb(122, 120, 120);border-top: 2px solid rgb(122, 120, 120);'>
                                <td style='width: 200px;padding:2px;'><b>TOTAL INCL. TAX (KES)</b></td>
                                <td style='width: 200px;padding:2px;font-weight: bold;'>$total</td>
                            </tr>
                        </table>
                        <table style='width: 950px;margin-top: 10px;'>
                            <tr style='text-align: left;'>
                                <td style='width:500px;'>
                                    <div>
                                        <div style='font-weight: bold;'>Due Date <span style='margin-left: 20px;'>$invoiceDueDate</span></div>
                                        <div style='margin-top: 10px;font-weight: bold;'>Payment Terms: <span style='margin-left: 20px; font-weight: normal;'>$paymentTerm</span></div>
                                        <div style='margin-top: 10px;font-weight: bold;'>Payment Details:
                                            <ul style='margin-left: 40px; list-style: none; margin: 5px;'>                    
                                                <li>Bank: Diamond Trust Bank</li>
                                                <li>Branch: Westgate (006) · 0433678002 (KES) or 0433678001 (USD)</li>
                                                <li>Cheque: Spring Valley Coffee Roasters Limited</li>
                                                <li>Lipa na Mpesa · Buy Goods & Services · 866299 </li>               
                                            </ul>                
                                        </div> 
                                        <div style='margin-top: 0px;font-weight: bold;'>
                                            <p>Delivery received by:</p>
                                            <p style='margin-top: 15px;'>Name: <span style='margin-left: 5px;'>__________________________________</span></p>   
                                            <p style='margin-top: 15px;'>Signature: <span style='margin-left: 5px;'>______________________________</span></p>  
                                            <p style='margin-top: 15px;'>Date: <span style='margin-left: 5px;'>___________________________________</span></p>          
                                        </div>
                                    </div>
                                </td>            
                                <td style='width: 350px;margin-left: 50px;'>
                                    <h5 style='margin:0px;'>KRA QR CODE</h5>
                                    <img width='250px' height='200px' src='".$qrCodePath."'/>
                                </td>
                            </tr>
                        </table>    
                    </div>
                </div>
            "; 
            $this->log->info('Template generation successfully.');           
        }else{
            $this->log->info('Template generation failed.'); 
        }        
        return [$htmlTemplate, $customerEmail, $customerEmailCC];
    }
}