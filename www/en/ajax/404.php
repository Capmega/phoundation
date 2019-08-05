<?php
    require_once(__DIR__.'/../libs/startup.php');

	load_libs('json');
	json_reply(tr('The specified page was not found'), '404');
?>
