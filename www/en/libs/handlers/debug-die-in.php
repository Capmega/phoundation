<?php
static $counter = 1;

if(!$message){
    $message = tr('Terminated process because die counter reached "%count%"');
}

if($counter++ >= $count){
    /*
     * Ensure that the shutdown function doesn't try to show the 404 page
     */
    unregister_shutdown('route_404');

    die(str_ends(str_replace('%count%', $count, $message), "\n"));
}
?>