<?php
require_once(__DIR__.'/libs/startup.php');

try{
    load_libs('redirect');
    redirect_from_code(isset_get($_GET['code']));

}catch(Exception $e) {
    page_show(404);
}
?>
