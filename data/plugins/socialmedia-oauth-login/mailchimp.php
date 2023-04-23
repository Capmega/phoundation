<?php
/*
	@author		:	Giriraj Namachivayam
	@date 		:	Apr 25, 2013
	@demourl		:	http://ngiriraj.com/socialMedia/oauthlogin/
	@license		: 	Free to use, 
*/

include "socialmedia_oauth_connect.php";

$oauth = new socialmedia_oauth_connect();

$oauth->provider="MailChimp";
$oauth->client_id = "255562924407";
$oauth->client_secret = "xxxxxxxxxxxxxxxxxxxxxxxxxxxxx";
$oauth->scope="";
$oauth->redirect_uri  ="http://ngiriraj.com/socialMedia/oauthlogin/mailchimp.php";

$oauth->Initialize();

$code = ($_REQUEST["code"]) ?  ($_REQUEST["code"]) : "";

if (empty($code)) {
	$oauth->Authorize();
} else {
	$oauth->code = $code;
#	print $oauth->getAccessToken();
	$getData = json_decode($oauth->getUserProfile());
	$oauth->debugJson($getData);
}

?>