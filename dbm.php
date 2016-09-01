<?php



class DBM{


	private $dbm;
	private $dbm_file;

	function __construct($file){

		$this->dbm_file = $file;
		#$this->openDBM($file);
	}

	function __destructor(){

		#$this->closeDBM();
	}

	function openDBM($file){

		$this->dbm = dba_open($file, "c", "flatfile");

		if (!$this->dbm) {
			echo "dba_open failed\n";
			exit;
		}
	}


	function readValue($key){

		$this->openDBM($this->dbm_file);

		$value = '';
		if (dba_exists($key, $this->dbm)) {
		    $value =  dba_fetch($key, $this->dbm);
		    #dba_delete($key, $this->dbm);
		}
		$this->closeDBM();
		return $value;

	}

	function writeValue($key, $value){
		$this->openDBM($this->dbm_file);
		dba_replace($key, $value, $this->dbm);
		$this->closeDBM();


	}

	function closeDBM() {
		dba_close($this->dbm);
	}

	function _test(){

		$dbm = new DBM;
		$dbm->openDBM("/tmp/test.db");
		$dbm->writeValue("test", "this is a test2");
		$dbm->readValue("test");
		$dbm->closeDBM();
	}

}
?>
