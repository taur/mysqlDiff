<?php
/**
 * PHP 4.4.2  Written and tested in version. This class was written to facilitate operations on the mysql database server.
 * You can save time by using this class rather than rewriting the necessary database error and control codes in each application.
 *
 * NOTE: PHP 5 If you are using a higher version, this class may not work.  Because radical changes were made to classes in the php5 version.
 *
 * mustafa
*/

class db{

	var $host='localhost';
	var $username='root';
	var $password='';
	var $database='mysql';

	var $connection;
	var $reader;
	var $affectedRows;
	var $numRows;
	var $error;
	var $charSet='utf8mb4';
	var $collate='utf8mb4_unicode_ci';

	public function __construct(){
		/**
		 * if database connection constants are defined
		 */
		if(defined('_dbHost')!=null){
			$this->host=constant('_dbHost');
			$this->username=constant('_dbUser');
			$this->password=constant('_dbPassword');
			if(constant('_dbDatabase')!=null)
				$this->database=constant('_dbDatabase');
		}

	}

	function connect(){
		if($this->connection=new mysqli($this->host,$this->username,$this->password)){
			$this->query('set names "'.$this->charSet.'" collate "'.$this->collate.'"');
			if(@$this->connection->select_db($this->database)){
				$this->query('set names "'.$this->charSet.'" collate "'.$this->collate.'"');
				return true;
			}
			$this->error='Database could not be selected.';
			return false;
		}
		$this->error='Could not connect to database server.';return false;
	}

	function query($sql,$buffered=true){
		$this->affectedRows=0;
		$this->numRows=0;
		if(!$this->connection && !$this->connect())	return false;
		if(($buffered && $this->reader=$this->connection->query($sql)) ||
			(!$buffered && $this->reader=$this->connection->query($sql))){

				if(gettype($this->reader)=='object')
					$this->numRows=$this->reader->num_rows;
				else
					$this->affectedRows=$this->connection->affected_rows;

			return true;
		}
		$this->error='Query failed.';return false;
	}

	function unbufferedQuery($sql){
		return $this->query($sql,false);
	}

	function fetchObject(){
		return $this->reader->fetch_object();
	}
	function fetchArray(){
		return $this->reader->fetch_array();
	}
	function fetchRow(){
		return $this->reader->fetch_row();
	}
	function nextIncrement($t){
		$this->query('show table status like \''.$t.'\'');
		$nau=$this->fetchObject(); // next auto_increment
		return $nau->Auto_increment;
	}
	function lastIncrement($t){
		return $this->nextIncrement($t)-1;
	}
	function getInsertId(){
		return $this->connection->insert_id;
	}
	function getError(){
		return $this->connection->error;
	}
	function fetchListByQuery($sql,$style='object'){
		if($this->query($sql) ){
			$arr=array();
			if($style=='object')
				while($r=$this->fetchObject()) $arr[]=$r;
			elseif($style=='array')
				while($r=$this->fetchArray()) $arr[]=$r;
			elseif($style=='row')
				while($r=$this->fetchRow()) $arr[]=$r;
			return $arr;
		}
		return false;
	}
	function fetchFirstRecord($q){
		if($this->query($q)){
			if($this->numRows>0)
			return $this->fetchObject();
		}
		return false;
	}

	/**
	 * The following are new methods.
	 * will be replaced with the ones above over time
	 */
	public function fetch($sql,$style='object'){
		return $this->fetchListByQuery($sql,$style='object');
	}
	public function fetchFirst($q){
		return $this->fetchFirstRecord($q);
	}

	public function escape($s,$strip=true){

		if(!$this->connection && !$this->connect())	return false;
		if(!is_array($s)){
			if($strip){
				if(strpos($s,'\\\'')!==false || strpos($s,'\\"')!==false)
					$s=stripslashes($s);
			}
			return $this->connection->real_escape_string($s);
		}
		else{

			if($strip){
				foreach($s as $k=>$i)
				if(strpos($i,'\\\'')!==false || strpos($i,'\\"')!==false)
					$s[$k]=stripslashes($i);
			}
			foreach($s as $k=>$i)
				$s[$k]=$this->connection->real_escape_string($i);

			return $s;
		}
	}
}
