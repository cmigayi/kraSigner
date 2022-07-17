<?php
namespace App\Database;

/**
*
* Handle all data from mysql db
*/

class MysqlDB{

	private $host;
	private $config_username;
	private $config_password;
	public $database;
	private $log;

	/**
	* Receives sql statements from outside the class: Setter
	*/
	public $sql;

	public $pdo;

	public $passedData;


	private $row;
	private $rows;
	private $stmt;

	public function __construct($log){
		/**
		* Initialize logger
		*/
		$this->log = $log;

		/**
		* Ensure raw data is displayed without exceptions
		*/
		Error_reporting(0);

		/**
		* Time zone should be local time
		*/
		date_default_timezone_set("Africa/Nairobi");

		/**
		* instantiation of all variables
		*/
		$this->stmt = null;
		$this->pdo = null;
		$this->row = null;
		$this->rows = null;
		$this->passedData = null;
		$this->sql = null;
	}

	/**
	* Database access configations based on PDO
	*
	* @return void
	*/
	public function pdoConfig(){
		$config = include("Config.php");

		$this->host = $config['host'];
		$this->config_username = $config['username'];
		$this->config_password = $config['password'];
		$this->database = $config['database'];
		$this->charset = "utf8mb4";

		try{
			$dsn = "mysql:host=".$this->host.";dbname=".$this->database.";charset=".$this->charset;
			$options = [
			    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
			    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
			    \PDO::ATTR_EMULATE_PREPARES   => false,
			];

			$this->pdo = new \PDO($dsn, $this->config_username, $this->config_password, $options);

		}catch(\PDOException $e){
			// throw new \PDOException("Connection failed! ".$e->getMessage());
			$this->log->error("Connection failed! ".$e->getMessage());
		}
	}

	/**
	* Handle all PDO database queries
	*
	* @return boolean state
	*/
	public function pdoQuery(){
		try{
			return $this->pdo->query($this->sql);
		}catch(\PDOException $e){
			// throw new \PDOException("Query failed! ".$e->getMessage());
			$this->log->error("Query failed! ".$e->getMessage());
		}
	}

	/**
	* Handle all PDO queries safely
	* Prepare statements is used instead of query,
	* when we need to pass data to query.
	*
	* @return boolean state
	*/
	public function pdoPrepareAndExecute(){
		$query = $this->pdo->prepare($this->sql);
		return $query->execute($this->passedData);
	}

	/**
	* Handle all PDO database fetch row
	*
	* @return array
	*/
	public function pdoFetchRow(){
		$this->stmt = $this->pdo->prepare($this->sql);
		$this->stmt->execute($this->passedData);
		$this->row = $this->stmt->fetchAll();
		$this->stmt = null;
		$this->sql = null;
		return $this->row;
	}

	/**
	* Handle all PDO database fetch rows
	*
	* @return array
	*/
	public function pdoFetchRows(){
		$this->stmt = $this->pdo->prepare($this->sql);
		$this->stmt->execute($this->passedData);
		while($this->row = $this->stmt->fetchAll()){
			$this->rows[] = $this->row;
		}
		$this->stmt = null;
		$this->sql = null;
		return $this->rows;
	}
}