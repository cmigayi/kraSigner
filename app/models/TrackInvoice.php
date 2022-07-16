<?php
namespace App\Models;

/**
* @Author: Cecil Migayi
* @Email: migayicecil@gmail.com
*
* This class assist in handling TrackInvoice data 
*/

class TrackInvoice{

	private $id;
	private $invoiceNumber;
	private $customerName;
	private $invoiceSigned;
	private $templateCreated;
	private $pdfCreated;
	private $emailSent;
	private $dateTime;

	public function setId($id){
		$this->id = $id;
	}

	public function getId(){
		return $this->id;
	}

	public function setInvoiceNumber($invoiceNumber){
		$this->invoiceNumber = $invoiceNumber;
	}

	public function getInvoiceNumber(){
		return $this->invoiceNumber;
	}

	public function setCustomerName($customerName){
		$this->customerName = $customerName;
	}

	public function getCustomerName(){
		return $this->customerName;
	}

	public function setInvoiceSigned($invoiceSigned){
		$this->invoiceSigned = $invoiceSigned;
	}

	public function getInvoiceSigned(){
		return $this->invoiceSigned;
	}

	public function setTemplateCreated($templateCreated){
		$this->templateCreated = $templateCreated;
	}

	public function getTemplateCreated(){
		return $this->templateCreated;
	}

    public function setPdfCreated($pdfCreated){
		$this->pdfCreated = $pdfCreated;
	}

	public function getPdfCreated(){
		return $this->pdfCreated;
	}

	public function setEmailSent($emailSent){
		$this->emailSent = $emailSent;
	}

	public function getEmailSent(){
		return $this->emailSent;
	}

	public function setDateTime($dateTime){
		$this->dateTime = $dateTime;
	}

	public function getDateTime(){
		return $this->dateTime;
	}
}