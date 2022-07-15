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
		$log->pushHandler(new StreamHandler('app.log', Logger::DEBUG));

		return $log;
	}
}