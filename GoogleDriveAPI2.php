<?php


class GoogleDrive{


	private $dbm;
	private $cookie;
	private $auth;
	private $refreshToken;

	function __construct($username, $code){

		$this->dbm = new DBM('/tmp/' . $username . '.db');

		if ($code != ''){
			$this->getOAuth2($code);
		}else{
			$this->auth = $this->dbm->readValue("auth");
			$this->refreshToken = $this->dbm->readValue("refreshToken");

			if ($this->refreshToken == ''){
				print "please visit " . SCOPE . " to authorize.<br>\n";
				exit;
			}
		}

	}

	function __destruct(){
		#
	}

	function refreshToken()
	{


	    $curl = curl_init();

	    curl_setopt_array($curl, array(
	        CURLOPT_POST => true,
	        CURLOPT_POSTFIELDS => array(
	            'client_id' => CLIENT_ID,
	            'client_secret' => CLIENT_SECRET,
	            'refresh_token' => $this->refreshToken,
	            'grant_type' => 'refresh_token'
	        ),
	        CURLOPT_URL => "https://accounts.google.com/o/oauth2/token",
	        CURLOPT_SSL_VERIFYPEER => false,
	        CURLOPT_RETURNTRANSFER => true
	    ));

	    $response_data = curl_exec($curl);

		if(curl_error($curl)){
		    print 'error:' . curl_error($curl);
		}
		curl_close ($curl);


	    $response = json_decode($response_data);


	    $this->auth = $response->access_token;
		$this->dbm->writeValue("auth", $this->auth);

	}

	function getOAuth2($code)
	{

	    $curl = curl_init();

	    curl_setopt_array($curl, array(
	        CURLOPT_POST => true,
	        CURLOPT_POSTFIELDS => array(
	            'code' => $code,
	            'client_id' => CLIENT_ID,
	            'client_secret' => CLIENT_SECRET,
	            'redirect_uri' => REDIRECT_URI,
	            'grant_type' => 'authorization_code'
	        ),
	        CURLOPT_URL => "https://accounts.google.com/o/oauth2/token",
	        CURLOPT_SSL_VERIFYPEER => false,
	        CURLOPT_RETURNTRANSFER => true
	    ));

	    $response_data = curl_exec($curl);
	    curl_close($curl);

	    $response = json_decode($response_data);

	    if (isset($response->refresh_token)) {
			$this->dbm->writeValue("refreshToken", $response->refresh_token);
			$this->refreshToken = $response->refresh_token;
	    }

	    // The access token should be used first else invalid_grant error
	    $this->auth = $response->access_token;
		$this->dbm->writeValue("auth", $this->auth);

	}


	function stream($link, $cookie){
	    $ch = curl_init($link);
		curl_setopt ($ch, CURLOPT_HTTPHEADER, array($cookie));
	    curl_setopt($ch,CURLOPT_WRITEFUNCTION , '__writeFunction');
	    curl_exec($ch);
	    curl_close($ch);

	  }

	function __writeFunction($curl, $data) {
	    echo $data;
	    return strlen($data);
	}


	function getVideoURLs($resourceID){


		$URL = 'https://drive.google.com/get_video_info?docid='.$resourceID;
		$curl = curl_init();
		curl_setopt ($curl, CURLOPT_URL, $URL);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$this->auth));
		curl_setopt ($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt ($curl, CURLOPT_HEADER, 1);

		#curl_setopt ($curl,CURLOPT_POST, 4);
		#curl_setopt ($curl,CURLOPT_POSTFIELDS, 'client_id='.$client_id.'&client_secret='.$client_secret.'&refresh_token='.$value.'&grant_type=refresh_token');
		$response_data = curl_exec ($curl);
		$response_data = urldecode(urldecode($response_data));

		if(curl_error($curl)){
		    print 'error:' . curl_error($curl);
		}

		if (preg_match("/You don't have permission/", $response_data)){
			print "error: auth";
			# need to reauth
			$this->refreshToken();
			curl_setopt ($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$this->auth));
			$response_data = curl_exec ($curl);
			$response_data = urldecode(urldecode($response_data));
			if (preg_match("/You don't have permission/", $response_data)){
				exit;
			}
		}

		curl_close ($curl);

		preg_match ("/DRIVE_STREAM\=([^\;]+);/", $response_data, $cookie);
		print "cookie " . $cookie[1];

		preg_match_all ("/([^\|]+)\|/", $response_data, $queryArray);



		#for ($i = 1; $i < sizeof($queryArray[0]); $i++) {
		#	print "try this link -- <a href=" . $queryArray[1][$i] . ">". $queryArray[1][$i] ."</a><br><br>\n";
		#}
		print "url = " . $queryArray[1][1];
		stream($queryArray[1][1], "Cookie: DRIVE_STREAM=" . $cookie[1]);
	}


	function getFolder($resourceID){


		$URL = "https://www.googleapis.com/drive/v2/files?q='".$resourceID."'+in+parents";
		$curl = curl_init();
		curl_setopt ($curl, CURLOPT_URL, $URL);
		curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt ($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$this->auth));
		curl_setopt ($curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt ($curl, CURLOPT_HEADER, 1);
		$response_data = curl_exec ($curl);

		if(curl_error($curl)){
		    print 'error:' . curl_error($curl);
		}

		if (preg_match("/Invalid Credentials/", $response_data)){
			print "error: auth";
			# need to reauth
			$this->refreshToken();
			curl_setopt ($curl, CURLOPT_HTTPHEADER, array('Authorization: Bearer '.$this->auth));
			$response_data = curl_exec ($curl);
			$response_data = urldecode(urldecode($response_data));
			if (preg_match("/Invalid Credentials/", $response_data)){
				exit;
			}
		}

		curl_close ($curl);

		##folders
		preg_match_all ("/\"kind\"\:\s+\"drive\#folder\"\,\s+\"id\"\:\s+\"([^\"]+)\"\,\s+[^\}]+\"title\"\:\s+\"([^\"]+)\"\,/", $response_data, $queryArray);
		for ($i = 1; $i < sizeof($queryArray[0]); $i++) {
		    print "<a href=?folder=".$queryArray[1][$i].">".$queryArray[2][$i]."</a><br/>";

		}

		##files
		preg_match_all ("/\"kind\"\:\s+\"drive\#file\"\,\s+\"id\"\:\s+\"([^\"]+)\"\,\s+[^\}]+\"title\"\:\s+\"([^\"]+)\"\,/", $response_data, $queryArray);
		for ($i = 1; $i < sizeof($queryArray[0]); $i++) {
		    print "<a href=?file=".$queryArray[1][$i].">".$queryArray[2][$i]."</a><br/>";

		}



	}
}
?>