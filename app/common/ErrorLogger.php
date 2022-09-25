<?php
namespace App\Common;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ErrorLogger{
	private $logChannel;
	private $logDirName;

	public function __construct($logChannel,$logDirName){
		$this->logChannel = $logChannel;
		$this->logDirName = $logDirName;
	}

	public function initLog(){
		// create a log channel
		$log = new Logger($this->logChannel);
		$dir = "logs/";
		if(!empty($this->logDirName)){
			$dir = "logs_".$this->logDirName."/";
		}
		$today = date("Y-m-d");
		$logPath = $dir.$today."_app.log";
		$log->pushHandler(new StreamHandler($logPath, Logger::DEBUG));

		return $log;
	}
}