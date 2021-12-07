<?php

class DB_mySQLi
{
	// Query types
	const OTHER   = 0;
	const SELECT  = 1;
	const INSERT  = 2;
	const UPDATE  = 3;
	const DELETE  = 4;
	const REPLACE = 5;

	public $SQLi;
	public $DB_settings;
	public $LastQuery;
	public $QueryResult;



	public function __construct($DB_settings)
	{
		$this->ConnectToDB($DB_settings);
	}
	public function __destruct()
	{
	}


	public function ConnectToDB($DB_settings)
	{
		$DB_host = ((isset($DB_settings['db_host']) and !empty($DB_settings['db_host']))  ?  $DB_settings['db_host']       :  'localhost');
		$DB_port = ((isset($DB_settings['db_port']) and !empty($DB_settings['db_port']))  ?  (int)$DB_settings['db_port']  :  NULL);


		// Connect
		//--------------------------------------------------------
		$this->DB_settings = $DB_settings;
		$this->SQLi        = new MySQLi($DB_host,  $DB_settings['db_user'],  $DB_settings['db_pass'],  $DB_settings['db_name'],  $DB_port);

		if ($this->SQLi->connect_errno) {
			echo 'Не удалось подключиться к MySQL';
			exit;
		}


		// Encoding & Charset
		//--------------------------------------------------------
		$this->SQLi->Select_DB($DB_settings['db_name']);

		$this->SQLi->Set_Charset('utf8');
		$this->SQLi->Query('SET NAMES utf8');
		$this->SQLi->Query("SET CHARACTER SET 'utf8'");
	}
	public function DisconnectFromoDB()
	{
		$this->FreeMemory();
		// mysqli.default_socket = /tmp/mysql5.sock
		$this->SQLi->Close();
	}



	#=====================================================================================
	public function myQuery($query) // Сам запрос
	{
		$this->LastQuery    = Trim($query);

		$this->QueryResult =
			$this->SQLi->Query(
				$this->LastQuery
			);

		if ($this->QueryResult !== FALSE) {
			
			if (Preg_Match('@^SELECT\s?@',   $this->LastQuery)) {
				$type = self::SELECT;
			} else if (Preg_Match('@^INSERT\s?@',   $this->LastQuery)) {
				$type = self::INSERT;
			} else if (Preg_Match('@^UPDATE\s?@',   $this->LastQuery)) {
				$type = self::UPDATE;
			} else if (Preg_Match('@^DELETE\s?@',   $this->LastQuery)) {
				$type = self::DELETE;
			} else if (Preg_Match('@^REPLACE\s?@',  $this->LastQuery)) {
				$type = self::REPLACE;
			} else {
				$type = self::OTHER;
			}



			// Возврат
			// -------------------------------------------------------------------------------------------------------------------------
			if (($type == self::UPDATE) or ($type == self::DELETE) or ($type == self::REPLACE)) {
				// Returns the number of rows affected by the last query.
				return $this->SQLi->affected_rows;
			} else if ($type == self::INSERT) {
				// Returns Insert_ID and the number of rows affected by the last query.
				return array(
					$this->SQLi->insert_id,
					$this->SQLi->affected_rows,
				);
			} else {
				return $this->QueryResult;
			}
		}
		return NULL;
	}

	public function getAllResults($query, $FetchParams = MYSQLI_ASSOC)
	{
		$arr = array();

		$res = $this->myQuery($query);

		if (($res != NULL) and $res !== FALSE) {
			$QueryResult = $this->QueryResult;

			if (Method_Exists('QueryResult', 'fetch_all')) {
				$arr = $QueryResult->Fetch_All($FetchParams);

				
			} else {
				if ($FetchParams == MYSQLI_ASSOC) {

					
					while ($row = $QueryResult->Fetch_Assoc()) {
						$arr[] = $row;
					}
				} else if ($FetchParams == MYSQLI_NUM) {

					while ($row = $QueryResult->Fetch_Row()) {
						$arr[] = $row;
					}
				} else {
					while ($row = $QueryResult->Fetch_Object()) {
						$arr[] = $row;
					}
				}
			}


			if (!empty($arr)) {
				$this->FreeMemory();
			}
		}

		return $arr;
	}

	public function FreeMemory()
	{
		if (is_Object($this->QueryResult)) {
			// $this->QueryResult->Free();

			$this->QueryResult = FALSE;
		}
	}
}