<?php

require('GoogleDriveAPI2.php');


$URL = 'https://drive.google.com/get_video_info?docid=1oZLVQulDNKvpT10pmn-P1_tyQNafLdwgIA';
$curl = curl_init();
curl_setopt ($curl, CURLOPT_URL, $URL);
curl_setopt ($curl, CURLOPT_RETURNTRANSFER, 1);

#curl_setopt ($curl,CURLOPT_POST, 4);
#curl_setopt ($curl,CURLOPT_POSTFIELDS, 'client_id='.$client_id.'&client_secret='.$client_secret.'&refresh_token='.$value.'&grant_type=refresh_token');
$response_data = curl_exec ($curl);
curl_close ($curl);

$query = urldecode(urldecode($response_data));


#printf("Value for parameter is \"%s\"<br/>\n", C);

preg_match_all ("/([^\|]+)\|/", $query, $queryArray);


for ($i = 1; $i < sizeof($queryArray[0]); $i++) {
	print "try this link -- <a href=" . $queryArray[1][$i] . ">". $queryArray[1][$i] ."</a><br><br>\n";
}

?>