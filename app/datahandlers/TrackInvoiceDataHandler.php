<?php
namespace App\DataHandlers;

/**
* @author: Cecil Migayi
* @email: migayicecil@gmail.com
*
* Handle data from mysql
*/

use App\Database\MysqlDB;
use App\Models\TrackInvoice;

class TrackInvoiceDataHandler extends MysqlDB{
	private $trackInvoice;
	private $dateTime;
	private $result;
	private $log;

	public function __construct($log){

		parent::__construct($log);

		/**
		* Date and time generated for date and time record creation 
		*/		
		$this->dateTime = date('Y-m-d H:i:s');

		/**
		* Initialize logger
		*/
		$this->log = $log;

		try{
			/**
			* Connect to PDO database 
			*/
			$this->pdoConfig();
		}catch(\Exception $e){
			return $e->getMessage();
		}
	}

	public function setData(TrackInvoice $trackInvoice){
		$this->trackInvoice = $trackInvoice;
	}

	public function createTrackInvoice(){
		$this->passedData = array(
				$this->trackInvoice->getInvoiceNumber(),
				$this->trackInvoice->getCustomerName(),
				$this->trackInvoice->getInvoiceSigned(),
				$this->trackInvoice->getTemplateCreated(),
				$this->trackInvoice->getPDFCreated(),
                $this->trackInvoice->getEmailSent(),
				date('Y-m-d H:i:s',strtotime($this->dateTime))
			);

		$this->trackInvoice =  new TrackInvoice();

		try{
			$this->pdo->beginTransaction();
			$this->sql = "INSERT INTO tbl_track_invoices VALUES(null,?,?,?,?,?,?,?)";
			$this->pdoPrepareAndExecute();
			$trackInvoiceId = $this->pdo->lastInsertId();
			$this->trackInvoice = $this->getTrackInvoice($trackInvoiceId);			
			$this->pdo->commit();

		}catch(\PDOException $e){
			//logger
			$this->log->error("Error ".$e->getMessage());
		}
		return $this->trackInvoice;
	}

	public function getTrackInvoice($trackInvoiceId){
		$this->passedData = array($trackInvoiceId);
		$this->sql = "SELECT * FROM tbl_track_invoices WHERE id = ?";

		$this->trackInvoice =  new TrackInvoice();

		try{

			$this->result = $this->pdoFetchRow();

			if($this->result == null){
				$this->trackInvoice = null;
			}else{
				$this->trackInvoice->setId($this->result[0]['id']);
				$this->trackInvoice->setInvoiceNumber($this->result[0]['invoice_number']);
				$this->trackInvoice->setCustomerName($this->result[0]['customer_name']);
				$this->trackInvoice->setInvoiceSigned($this->result[0]['invoice_signed']);
				$this->trackInvoice->setTemplateCreated($this->result[0]['template_created']);
				$this->trackInvoice->setPdfCreated ($this->result[0]['pdf_created']);
				$this->trackInvoice->setEmailSent($this->result[0]['email_sent']);
				$this->trackInvoice->setDateTime($this->result[0]['date_time_created']);
			}
		}catch(\PDOException $e){
			// logger
			$this->log->error("Error ".$e->getMessage());
		}
		return $this->trackInvoice;
	}
	
	public function getTrackInvoices(){
		$this->passedData = array();

		try{
			$this->sql = "SELECT * FROM tbl_track_invoices";
			$this->result = $this->pdoFetchRows();

		}catch(\PDOException $e){
			// logger
			$this->log->error("Error ".$e->getMessage());
		}
		return $this->result;
	}
	
	public function updateTrackInvoice(){
		$trackInvoiceId = $this->trackInvoice->getTrackInvoiceId();
		$this->passedData = array(
                $this->trackInvoice->getInvoiceNumber(),
                $this->trackInvoice->getCustomerName(),
                $this->trackInvoice->getInvoiceSigned(),
                $this->trackInvoice->getTemplateCreated(),
                $this->trackInvoice->getPDFCreated(),
                $this->trackInvoice->getEmailSent(),
				$trackInvoiceId
			);

		$this->trackInvoice = new TrackInvoice();

		try{
			$this->pdo->beginTransaction();
			$this->sql = "UPDATE tbl_track_invoices SET invoice_number=?, customer_name=?, invoice_signed=?, template_created=?, pdf_created=?, email_sent=? WHERE id=?";
			$this->pdoPrepareAndExecute();
			$this->trackInvoice = $this->getTrackInvoice($trackInvoiceId);
			$this->pdo->commit();

		}catch(\PDOException $e){
			$this->pdo->rollback();
			
			//logger required!
		}
		return $this->product;	
	}
	
	public function deleteTrackInvoice($trackInvoiceId){
		$this->passedData = array($trackInvoiceId);
		try{
			$this->sql = "DELETE FROM tbl_track_invoices WHERE id=?";
			$this->result = $this->pdoPrepareAndExecute();
		}catch(\PDOException $e){
			$this->pdo->rollback();
			
			//logger required!
		}
		return $this->result;
	}
}