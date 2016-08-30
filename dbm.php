<?php



class DBM{


	private $dbm;
	private $dbm_file;

	function __construct($file){

		#$this->dbm_file = $file;
		$this->openDBM($file);
	}

	function __destructor(){

		$this->closeDBM();
	}

	function openDBM($file){

		$this->dbm = dba_open($file, "c", "flatfile");

		if (!$this->dbm) {
			echo "dba_open failed\n";
			exit;
		}
	}


	function readValue($key){

		if (dba_exists($key, $this->dbm)) {
		    return dba_fetch($key, $this->dbm);
		    #dba_delete($key, $this->dbm);
		}


	}

	function writeValue($key, $value){
		dba_replace($key, $value, $this->dbm);

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
