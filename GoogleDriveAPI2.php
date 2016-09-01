<?php


class GoogleDrive{


	private $dbm;
	private $cookie;
	private $auth;
	private $refreshToken;
	private $username;

	function __construct($username, $code){
		$this->username = $username;

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
	    curl_setopt($ch,CURLOPT_WRITEFUNCTION , array($this,'__writeFunction'));
	    curl_exec($ch);
	    curl_close($ch);

	  }

	function __writeFunction($curl, $data) {
	    echo $data;
	    return strlen($data);
	}


	function getVideoURLs($resourceID, $playback, $browser){


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
			#print "error: auth";
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


		##$response_data = preg_replace("/\&url\=/", "|", $response_data);
		preg_match_all ("/([^\|]+)\|/", $response_data, $queryArray);

		#for ($i = 1; $i < sizeof($queryArray[0]); $i++) {
		#	print "try this link -- <a href=" . $queryArray[1][$i] . ">". $queryArray[1][$i] ."</a><br><br>\n";
		#}
#		print "url = " . $queryArray[1][1];
		if ($playback == 'o' and $browser == ''){
			$this->stream('https://www.googleapis.com/drive/v2/files/'.$resourceID.'?alt=media', "Authorization: Bearer " . $this->auth);
		}elseif ($playback != '' and $browser == ''){
			$this->stream($queryArray[1][$playback], "Cookie: DRIVE_STREAM=" . $cookie[1]);
		}elseif ($playback != '' and $browser == '1'){
			print '<video autoplay controls="true" height="100%" width="100%" src="?username='.$this->username.'&file='.$resourceID.'&playback='.$playback.'"></video>';
		}else{
			print "Copy one of these quality URLs into 3rd party player:<br/>";
			print "<a href=?username=".$this->username."&file=".$resourceID."&playback=o>quality original</a> [click <a href=?username=".$this->username."&file=".$resourceID."&browser=1&playback=o>here</a> to play in browser]<br/>";
			for ($i = 1; $i < sizeof($queryArray[0]); $i++) {
				preg_match ("/yes\,(\d+)/", $queryArray[1][$i], $itag);


		switch ($itag[1]) {
		    case 5:
		        $quality = 'Low Quality, 240p, FLV, 400x240';
		        break;
		    case 17:
		        $quality = 'Low Quality, 144p, 3GP, 0x0';
		        break;
		    case 18:
		        $quality = 'Medium Quality, 360p, MP4, 480x360';
		        break;
		    case 22:
		        $quality = 'High Quality, 720p, MP4, 1280x720';
		        break;
		    case 34:
		        $quality = 'Medium Quality, 360p, FLV, 640x360';
		        break;
		    case 35:
		        $quality = 'Standard Definition, 480p, FLV, 854x480';
		        break;
		    case 36:
		        $quality = 'Low Quality, 240p, 3GP, 0x0';
		        break;
		    case 37:
		        $quality = 'Full High Quality, 1080p, MP4, 1920x1080';
		        break;
		    case 38:
		        $quality = 'Original Definition, MP4, 4096x3072';
		        break;
		    case 43:
		        $quality = 'Medium Quality, 360p, WebM, 640x360';
		        break;
		    case 44:
		        $quality = 'Standard Definition, 480p, WebM, 854x480';
		        break;
		    case 45:
		        $quality = 'High Quality, 720p, WebM, 1280x720';
		        break;
		    case 46:
		        $quality = 'Full High Quality, 1080p, WebM, 1280x720';
		        break;
		    case 82:
		        $quality = 'Medium Quality 3D, 360p, MP4, 640x360';
		        break;
		    case 84:
		        $quality = 'High Quality 3D, 720p, MP4, 1280x720';
		        break;
		    case 102:
		        $quality = 'Medium Quality 3D, 360p, WebM, 640x360';
		        break;
		    case 104:
		        $quality =  'High Quality 3D, 720p, WebM, 1280x720';
		        break;

		    default:
		        $quality =  'transcoded (unknown) quality';
		}

			    print "<a href=?username=".$this->username."&file=".$resourceID."&playback=".$i.">".$quality."</a> [click <a href=?username=".$this->username."&file=".$resourceID."&browser=1&playback=".$i.">here</a> to play in browser]<br/>";
			}
		}
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
			#print "error: auth";
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
		preg_match_all ("/\"kind\"\:\s+\"drive\#file\"\,\s+\"id\"\:\s+\"([^\"]+)\"\,\s+[^\}]+\"title\"\:\s+\"([^\"]+)\"\,[^\}]+folder/", $response_data, $queryArray);
		for ($i = 0; $i < sizeof($queryArray[0]); $i++) {
		    print "<a href=?username=".$this->username."&folder=".$queryArray[1][$i].">".$queryArray[2][$i]."</a><br/>";

		}

		##files
		preg_match_all ("/\"kind\"\:\s+\"drive\#file\"\,\s+\"id\"\:\s+\"([^\"]+)\"\,\s+[^\}]+\"title\"\:\s+\"([^\"]+)\"\,[^\}]+video/", $response_data, $queryArray);
		for ($i = 0; $i < sizeof($queryArray[0]); $i++) {
		    print "<a href=?username=".$this->username."&file=".$queryArray[1][$i].">".$queryArray[2][$i]."</a><br/>";

		}



	}
}
?>