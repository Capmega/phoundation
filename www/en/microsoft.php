<?php
require_once(__DIR__.'/libs/startup.php');

try {
	load_libs('sso');
	sso('microsoft');

}catch(Exception $e){
	sso_fail(tr('Microsoft login failed. Please try again later'));
}
?>
