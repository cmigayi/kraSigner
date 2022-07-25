<?php
namespace App\Common;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ErrorLogger{
	private $logChannel;

	public function __construct($logChannel){
		$this->logChannel = $logChannel;
	}

	public function initLog(){
		// create a log channel
		$log = new Logger($this->logChannel);
		$today = "logs/".date("Y-m-d");
		$logPath = $today."_app.log";
		$log->pushHandler(new StreamHandler($logPath, Logger::DEBUG));

		return $log;
	}
}