<?php
require_once(__DIR__.'/libs/startup.php');

try{
	load_libs('sso');
	sso('google');

}catch(Exception $e) {
	sso_fail(tr('Google login failed. Please try again later'), 'signin.php');
}
?>
