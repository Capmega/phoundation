<?php
    require_once(__DIR__.'/../libs/startup.php');

	load_libs('json');
	json_reply(tr('The website is under maintenance'), 'MAINTENANCE');
?>
