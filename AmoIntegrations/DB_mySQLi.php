<?php
#===============================================================================================================



#===============================================================================================================
# http://www.mysql.ru/docs/man/DATETIME.html
# http://habrahabr.ru/post/69983/
#===============================================================================================================



#===============================================================================================================
class DB_mySQLi
{
	// Query types
	const OTHER   = 0;
	const SELECT  = 1;
	const INSERT  = 2;
	const UPDATE  = 3;
	const DELETE  = 4;
	const REPLACE = 5;

	static $DEBUG_MODE_FLAG = FALSE;

	public $SQLi;
	public $DB_settings;

	public $LastQuery;
	public $QueryResult;




	#=====================================================================================
	/**
	 *  @param array $DB_settings ['db_host','db_port','db_pass','db_user','db_name']
	 */
	public function __construct($DB_settings)
	{
		$this->ConnectToDB($DB_settings);
	}
	public function __destruct()
	{
	}



	#=====================================================================================
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

			if (self::$DEBUG_MODE_FLAG != FALSE) {
				echo ': (' . $this->SQLi->connect_errno . ') ' . $this->SQLi->connect_error;
			}

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
		$this->LastQuery = Trim($query);
		// echo $this->LastQuery.'<br/>';


		// Выполняем запрос
		// -------------------------------------------------------------------------------------------------------------------------
		$this->QueryResult = $this->SQLi->Query($this->LastQuery);

		if ($this->QueryResult !== FALSE) {
			// Определяем тип
			// -------------------------------------------------------------------------------------------------------------------------
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
				// SELECT, OTHER
				return $this->QueryResult;
			}
		}

		return NULL;
	}

	public function ExecuteAndReturnQuery($query, $delimeter) // Для echo...
	{
		$this->myQuery($query);

		$this->FreeMemory();

		return $query . $delimeter;
	}

	public function getAllResults($query, $FetchParams = MYSQLI_ASSOC) // Для forEach ($all as $item) {}
	{
		$arr = array();

		$res = $this->myQuery($query);

		if (($res != NULL) and $res !== FALSE) {
			$QueryResult = $this->QueryResult;

			if (Method_Exists('QueryResult', 'fetch_all')) {
				$arr = $QueryResult->Fetch_All($FetchParams);

				// http://stackoverflow.com/questions/11664536/fatal-error-call-to-undefined-method-mysqli-resultfetch-all
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

	public function getResultNumRows($query) // Просто кол-во строк в результате
	{
		$i = 0;

		if ($this->myQuery($query) !== FALSE) {
			$i = SizeOf($this->QueryResult->Fetch_All(MYSQLI_NUM));

			$this->FreeMemory();
		}

		return (int)$i;
	}


	public function insert(string $table_name, array $data)
	{
		if (!empty($data)) {
			$cols = '';
			$vals = '';
			foreach ($data as $k => $v) {
				if ($v) {
					$cols .= " `$k`, ";
					$vals .= " '$v', ";
				}
			}
			if($cols && $vals){
				$cols = trim($cols, ', ');
				$vals = trim($vals, ', ');
				$query = "INSERT INTO $table_name ( $cols ) VALUES ( $vals )";
				$result = $this->myQuery($query);
				return $result;
			}
			throw new \ErrorException('incorrect values', 0, E_WARNING);
		}
	}
	public function update(string $table_name, array $data, array $where)
	{
		if (!empty($data)) {
			$update='';
			foreach ($data as $k => $v) {
				if ($v) {
					$update .= " `$k`='$v', ";
				}
			}
			if(empty($where)){
				throw new Exception("Empty Where", 0, E_WARNING);
			}
			$where_sql ='';
			foreach ($where as $condition_name=>$value) {
				$value = $this->SafeString($value);
				$condition_name = $this->SafeString($condition_name);
				if($value && $condition_name){
					$where_sql .=  " AND `$condition_name` = '$value'";
				}
			}
			if($update){
				$update = trim($update, ', ');
				$query = "UPDATE  $table_name SET $update WHERE $table_name.id>0 $where_sql";
				$result = $this->myQuery($query);
				return $result;
			}
			throw new \ErrorException('incorrect values', 0, E_WARNING);
		}
	}


	#=====================================================================================
	public function FreeMemory()
	{
		if (is_Object($this->QueryResult)) {
			// $this->QueryResult->Free();

			$this->QueryResult = FALSE;
		}
	}



	#=====================================================================================
	function __Create_Update_Query($table, &$item_arr, $allow_keys, $all_to_cp1251 = FALSE)
	{
		// $item_id   = $item_arr['id'];
		// $tmp_query = ('id='.$item_id);

		// forEach ($allow_keys as $key => $type)
		// {
		// $value      = 0;
		// $safe_value = '';

		// if (isSet($_POST[$key]))
		// {
		// $value = $_POST[$key];
		// }
		// else
		// {
		// if ($type <> 'boolean')
		// {
		// continue;
		// $value = 0;
		// }
		// }


		// if      ($type == 'int')		{$value = Safe_Int($value);        $safe_value = $value;}
		// else if ($type == 'boolean')	{$value = Safe_Boolean($value);    $safe_value = $value;}
		// else if ($type == 'str')
		// {
		// if ($all_to_cp1251 == TRUE) {$value = All_to_cp1251($value);   $safe_value = '\''.Safe($value).'\'';}
		// else                        {$value = Trim($value);            $safe_value = '\''.Safe($value).'\'';}
		// }

		// $tmp_query .= (', '.$key.'='.$safe_value);

		// $item_arr[$key] = $value;
		// }
		// unSet($type);
		// unSet($value);
		// unSet($safe_value);

		// (((isSet($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] == 'on')) ? 's' : '')
		// $query = ('UPDATE  '.$table.'  SET '.$tmp_query.'  WHERE id='.$item_id);

		// return $query;
	}

	function getTableUpdateTime($tableName)
	{
		$UpdateTime  = 0;
		$QueryResult = $this->myQuery('SHOW TABLE STATUS FROM  ' . $this->DB_settings['db_name'] . "  LIKE '" . $tableName . "'");

		if (!empty($QueryResult)) {
			$UpdateTime = StrToTime($QueryResult->Fetch_Object()->Update_time);
		}

		return $UpdateTime;
	}



	#=====================================================================================
	public function SafeString($data, $limit = 0, $noTags = TRUE)
	{
		$search  = array("\\\\",  "\\0",  "\\n",  "\\r",  "\Z",    "\'",  '\"',  '"',       "'");
		$replace = array("\\",    "\0",   "\n",   "\r",   "\x1a",  "'",   '"',   '&quot;',  '&#039;');

		$search  = array("\\\\",  "\\0",  "\\n",  "\\r",  "\Z",    "\'",  '\"');
		$replace = array("\\",    "\0",   "\n",   "\r",   "\x1a",  "'",   '"');

		$data    = Str_Replace($search, $replace, Trim($data));


		if ($limit > 0) {
			$data = SubStr($data, 0, $limit);
		}

		if ($noTags != FALSE) {
			$data = Strip_Tags($data);
		}


		// $data = AddCSlashes($this->SQLi->Real_Escape_String($data), '%_');
		$data = $this->SQLi->Real_Escape_String($data);

		return $data;
		// $data = StripSlashes(StripCSlashes($data));
		// $data = Str_Replace('\\',  '',  $data);
	}
	public function SafeFloatInt($data)
	{
		$dotPos   = StrrPos($data, '.');
		$commaPos = StrrPos($data, ',');


		$sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : ((($commaPos > $dotPos) && $commaPos) ? $commaPos : FALSE);

		if (!$sep) {
			return floatVal(Preg_Replace("/[^0-9]/", '', $data));
		}


		return floatVal((float)Preg_Replace("/[^0-9]/", '', SubStr($data, 0, $sep)) . '.' . Preg_Replace("/[^0-9]/", '', SubStr($data, $sep + 1, StrLen($data))));
	}
	public function SafeInt($data)
	{
		if ($data > 2147483646) {
			return $this->SafeIntBig($data);
		} else {
			return abs(intVal($data));
		}
	}
	public function SafeIntBig($data)
	{
		$data = Trim($data);

		if (cType_Digit($data) != TRUE) {
			//$newData = sPrintF('%.0f', $data);
			$newData = Preg_Replace("/[^0-9](.*)$/", '', $data);

			if (cType_Digit($newData) == TRUE) {
				$data = $newData;
			} else {
				$data = 0;
			}
		}

		return abs($data);
	}
	public function SafeEmail($data = '')
	{
		/*
		 http://www.w3schools.com/php/php_ref_filter.asp
		 
		 if(!filter_var("someone@example....com", FILTER_VALIDATE_EMAIL))
		 {
		 echo("E-mail is not valid");
		 }
		else
		 {
		 echo("E-mail is valid");
		 }
		 
		 if (preg_match("/[^(\w)|(\@)|(\.)|(\-)]/",$usermail)) {
			echo "invalid mail";
			exit;
	}
		 
		 */

		if (!empty($data) and (StrLen($data) < 255)) {
			$data = $this->SafeString(Preg_Replace("/[\r\n]+/", ' ', $data));

			# Проверка е-mail адреса регулярными выражениями на корректность
			if (!Preg_Match("/.+@.+\..+/i", $data)) {
				$data = '';
			}
		}


		return $data;
	}
	public function SafeBoolean($data)
	{
		if ($this->SafeInt($data) == 1) {
			$data = 'TRUE';
		} else if (StrToUpper($data) == 'TRUE') {
			$data = 'TRUE';
		} else {
			$data = 'FALSE';
		}

		return $data;
	}
	public function SafeHexColor($data)
	{
		$hex_str = SubStr(Trim($data), 0, 7);

		$hex_str = Preg_Replace("/[^#0-9A-Fa-f]/", '', $hex_str);

		if (!cType_AlNum($hex_str)) {
			$hex_str = NULL;
		}

		return $hex_str;
	}

	public function SafeLogin($data, $allowCyrillic = FALSE)
	{
		// Меняем кодировку на кириллицу для проверки символов
		// $data = All_to_cp1251($data);

		$data = $this->SafeString($data);

		// Обрезаем лишнее
		if ($allowCyrillic == TRUE) {
			$data = Preg_Replace("/[^a-zA-Zа-яА-ЯЁё0-9_\.-]/u", '',  $data);
		} else {
			$data = Preg_Replace("/[^a-zA-Z0-9_\.-]/",          '',  $data);
		}

		// Обрезаем до 60 символов
		$data = SubStr($data, 0, 60);

		return $data;

		/*
		 // Меняем кодировку опять на UTF8 для работы с БД
		 
		 setLocale(LC_CTYPE, array('ru_RU.utf8', 'ru_UA.utf8')); 
			 setLocale(LC_ALL,   array('ru_RU.utf8', 'ru_UA.utf8')); 
			 // Меняем кодировку на кириллицу для проверки символов
			 $str = All_to_cp1251($str);
			 
			 if (Preg_Match('/[^\pL]/u', $str))
				{
				 AjaxResponse('no', 30, 'preg1');
				}
			 
			 if (Preg_Match('/[^\p{L}]/u', $str))
				{
				 AjaxResponse('no', 30, 'preg2');
				}
		 */
	}
	public function SafeLoginOrEmail($data, $allowCyrillic = FALSE)
	{
		$data = $this->SafeString($data);

		// Обрезаем лишнее
		if ($allowCyrillic == TRUE) {
			$data = Preg_Replace("/[^a-zA-Zа-яА-ЯЁё0-9_\.-@]/u",  '',  $data);
		} else {
			$data = Preg_Replace("/[^a-zA-Z0-9_\.-@]/",           '',  $data);
		}

		// Обрезаем до 60 символов
		$data = SubStr($data, 0, 60);

		return $data;
	}


	#=====================================================================================
	public function CheckRepairOptimaze_Table($TableName, $LOCK_TABLES = TRUE)
	{
		$status = 'No';


		if ($LOCK_TABLES != FALSE) {
			$this->myQuery('LOCK TABLES `' . $TableName . '` WRITE');
		}


		$t_info = $this->myQuery('CHECK TABLE `' . $TableName . '` EXTENDED')->Fetch_Assoc();

		if (($t_info['Msg_text'] != 'OK') and (($TableName != 'site_links') and $t_info['Msg_text'] != 'Found row where the auto_increment column has the value 0')) {
			$this->myQuery('REPAIR TABLE `' . $TableName . '`');
		}


		$res_op = $this->myQuery('OPTIMIZE TABLE `' . $TableName . '`')->Fetch_Assoc();


		if ($LOCK_TABLES != FALSE) {
			$this->myQuery('UNLOCK TABLES');
		}

		$status = (($res_op['Msg_text'] == 'OK')  ?  '<span style="color:blue;">Done!</span>'  :  '<small>' . $res_op['Msg_text'] . '</small>');

		return $status;
	}
}
#===============================================================================================================
