<?php
class db{
	/**
	 * @var PDO
	 */
	private static $_PDO;

	/**
	 * @var self
	 */
	private static $_DB;

	/**
	 * Store the number of affected rows
	 * @var int $_AffectedRows
	 */
	private $_AffectedRows;

	/**
	 * Store the last inserted ID
	 * @var int $_InsertedID
	 */
	private $_InsertedID;

	/**
	 * Store the results into array
	 * @var array $_Results
	 */
	private $_Results = [];

	protected function __construct(){
		$this->_connect();
	}

	/**
	 * Build database connection
	 */
	private function _connect(){
		$dsn = $this->_getMySQLDSN();

		if(!self::$_PDO){
			try{
				self::$_PDO = new PDO(
					$dsn,
					config::DB_USER,
					config::DB_PASSWORD,
					[
						PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
						PDO::ATTR_PERSISTENT => true,
						PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
						PDO::ATTR_EMULATE_PREPARES => false
					]
				);
			}catch (PDOException $e){
				throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
			}
		}
	}

	/**
	 * Get MySQL DSN
	 * @return string
	 */
	private function _getMySQLDSN(){
		return sprintf(
			"mysql:host=%s;port=%s;dbname=%s;charset=utf8",
			config::DB_HOST,
			config::DB_PORT,
			config::DB_NAME
		);
	}

	public function startTransaction(){
		self::$_PDO->beginTransaction();
	}

	public function endTransaction(){
		self::$_PDO->commit();
	}

	public function rollBack(){
		self::$_PDO->rollBack();
	}

	public function query($query){
		$pdo = self::$_PDO;
		$query = trim($query);
		$parameters = array_slice(func_get_args(), 1);

		try{
			$stmt = $pdo->prepare($query);
			$stmt->execute($parameters);
		}catch (PDOException $e){
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}

		if (preg_match('/^(?:select|insert|update|delete|replace|truncate|drop)/i', $query))
			$this->_AffectedRows = $stmt->rowCount();

		if (preg_match('/^insert/i', $query))
			$this->_InsertedID = $pdo->lastInsertId();

		if (preg_match('/^(?:select|show|desc)/i', $query))
			$this->_Results = $stmt->fetchAll(PDO::FETCH_OBJ);

		$stmt->closeCursor();
	}

	/**
	 * Initiate the database class
	 * @return db
	 */
	public static function init(){
		if(!self::$_DB){
			self::$_DB = new self;
		}

		return self::$_DB;
	}

	/**
	 * Get the number of affected rows after an update, delete, replace, etc
	 * @return int
	 */
	public function getAffectedRows(){
		return $this->_AffectedRows;
	}

	/**
	 * Get the last inserted ID
	 * @return int
	 */
	public function getInsertedID(){
		return $this->_InsertedID;
	}

	/**
	 * Return an array with all the rows
	 * @return array
	 */
	public function getRows(){
		return $this->_Results;
	}

	/**
	 * Return the first row as an object
	 * @return stdClass|null
	 */
	public function getFirstRow(){
		$row = null;
		if(count($this->_Results))
			$row = $this->_Results[0];

		return $row;
	}
}