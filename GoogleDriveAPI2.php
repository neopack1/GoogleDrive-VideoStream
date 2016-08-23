<?php

define('CLIENT_ID', '');
define('CLIENT_SECRET', '');
define('REDIRECT_URI', 'urn:ietf:wg:oauth:2.0:oob');


function getOAuth2RefreshToken($token)
{

#        'approval_prompt' => 'force',

    $params = array(

            'code' => $code,
            'client_id' => CLIENT_ID,
            'client_secret' => CLIENT_SECRET,
            'refresh_token' => $token,
            'grant_type' => 'refresh_token'
    );

    $request_token = "https://www.googleapis.com/oauth2/v3/token" . '?' . http_build_query($params);

    // Redirect to Google's OAuth 2.0 server
    header('Location: ' . $request_token);

}

function getOAuth2Code($code)
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
        CURLOPT_URL => "https://www.googleapis.com/oauth2/v3/token",
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_RETURNTRANSFER => true
    ));

    $response_data = curl_exec($curl);

    curl_close($curl);

    $response = json_decode($response_data);

    if (isset($response->refresh_token)) {
        // Refresh tokens are for long term user and should be stored
        // They are granted first authorization for offline access
        file_put_contents("./GmailToken.txt", $token);
    }

    // The access token should be used first else invalid_grant error
    $_SESSION['access_token'] = $response->access_token;

    // Reload the page but without the Query String
    header("Location: " . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

}

?>