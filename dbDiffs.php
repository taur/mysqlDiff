<?php
/**
 * http://github.com/muatik/dbDiffs
 * http://cookingthecode.com/a48_Veritabanlari-Arasindaki-Farklar
 * Mustafa Atik
 * Apr 14 2011
 */

require_once('db.php');
require_once('arrays.php');
class dbDiffs
{

	public $db;
	private $tableFields;
	private $columnFields;

	public function __construct(){

		// tablo karşılaştırma kriterleri.
		$this->tableFields=array(
			'ENGINE','TABLE_COLLATION','TABLE_COMMENT'
		);

		// sütun karşılaştırma kriterleri
		$this->columnFields=array(
			'COLUMN_TYPE','COLUMN_DEFAULT','IS_NULLABLE',
			'CHARACTER_SET_NAME','COLLATION_NAME',
			'COLUMN_KEY','EXTRA','COLUMN_COMMENT'
		);

		$this->db=new db();
		$this->db->database='information_schema';
	}

	/**
	 * Finds differences between two named databases
	 * @param db1 name of first database
	 * @param db2 name of second database
	 * @return array
	 */
	public function check($db1Name,$db2Name){
		$diffs=new stdClass;

		$db1=new stdClass;
		$db2=new stdClass;
		$db1->tables=$this->fetchTables($db1Name);
		$db2->tables=$this->fetchTables($db2Name);

		// Getting different tables of db1 from db2
		$diffs->tableDiff=arrays::diff(
			$db2->tables,$db1->tables,'TABLE_NAME','to'
		);
		// Getting tables of db2 different from db1
		$diffs->tableDiffr=arrays::diff(
			$db2->tables,$db1->tables,'TABLE_NAME','from'
		);

		// A list of tables containing both is being prepared.
		$tables=arrays::intersect(
			$db1->tables,$db2->tables,'TABLE_NAME'
		);



		foreach($tables as $t){

			// Comparing two table structures
			$tblDiff=$this->compareObjects(
				$db1->tables[$t],
				$db2->tables[$t],
				$this->tableFields
			);


			if(count($tblDiff)>0)
				$diffs->tables[$t]->structure=$tblDiff;



			// table columns are pulled
			$t1Clm=$this->fetchColumns($db1Name,$t);
			$t2Clm=$this->fetchColumns($db2Name,$t);

			// Column between table db1 and table db2
			// looking at the differences
			$diffs->tables[$t]->columnDiff=arrays::diff(
				$t1Clm,$t2Clm,'COLUMN_NAME','to'
			);
			// and vice versa, differences of db2 from db1
			$diffs->tables[$t]->columnDiffr=arrays::diff(
				$t1Clm,$t2Clm,'COLUMN_NAME','from'
			);

			// Preparing a list of columns that are in both
			$columns=arrays::intersect(
				$t1Clm,$t2Clm,'COLUMN_NAME'
			);


			foreach($columns as $c){

				// Comparing two table structures
				$cDiff=$this->compareObjects(
					$t1Clm[$c],
					$t2Clm[$c],
					$this->columnFields
				);

				if(count($cDiff)>0)
					$diffs->tables[$t]->columns[$c]=$cDiff;
			}

		}

		return $diffs;

	}


	/**
	 * compares two objects based on specified fields and
	 * gives disputes.
	 * @param object 1st object
	 * @param object 2nd object
	 * @param filelds object properties to compare
	 * @return array Differences
	 */
	private function compareObjects($o1,$o2,$fields){

		$diffs=array();
		foreach($fields as $f){

			if($o1->$f==$o2->$f) continue;

			$diff=array('field'=>$f,'value1'=>$o1->$f,
				'value2'=>$o2->$f
			);

			$diffs[]=$diff;
		}
		return $diffs;
	}


	/**
	 * Returns table records in the specified database.
	 * @param db name of database
	 */
	public function fetchTables($db){
		$sql='select * from TABLES
		where TABLE_SCHEMA=\''.$db.'\'';

		$rs=$this->db->fetch($sql);

		$rs2=array();
		foreach($rs as $i)
			$rs2[$i->TABLE_NAME]=$i;
		return $rs2;
	}

	/**
	 * Returns the column records in the specified table.
	 */
	public function fetchColumns($db,$table){
		$sql='select * from COLUMNS
		where TABLE_SCHEMA=\''.$db.'\' and TABLE_NAME=\''.$table.'\'';
		$rs=$this->db->fetch($sql);

		$rs2=array();
		foreach($rs as $i)
			$rs2[$i->COLUMN_NAME]=$i;
		return $rs2;
	}

	/**
	 * Provides a list of databases with access permission.
	 */
	public function getDbList(){
		return $this->db->fetch(
			'select SCHEMA_NAME as name from SCHEMATA order by name'
		);
	}
}
