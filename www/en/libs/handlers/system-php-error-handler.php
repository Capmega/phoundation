<?php
global $core;

if(!isset($core)){
    throw new BException('Pre core available PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"', $errno);
}

if(!$core->register['ready']){
    throw new BException('Pre core-ready PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"', $errno);
}

$trace = "\n\nFUNCTION TRACE\n".htmlentities(print_r(debug_trace(), true));

notify(array('code'    => 'php-error',
             'groups'  => 'developers',
             'title'   => tr('PHP Error'),
             'message' => tr('PHP ERROR [:errno] ":errstr" in ":errfile@:errline" with trace ":trace"', array(':errno' => $errno, ':errfile' => $errfile, ':errline' => $errline, ':trace' => $trace))));

throw new BException(tr('PHP ERROR [:errno] ":errstr" in ":errfile@:errline"', array(':errno' => $errno, ':errfile' => $errfile, ':errline' => $errline)), $errno);
?>
