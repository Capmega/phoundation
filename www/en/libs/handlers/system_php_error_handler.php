<?php
global $core;

if(empty($core->register['ready'])){
    throw new bException('Pre core-ready PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"', $errno);
}

$session   = "\n\n\n<br><br>SESSION DATA<br><br>\n\n\n".htmlentities(print_r(variable_zts_safe(isset_get($_SESSION)), true));
$server    = "\n\n\n<br><br>SERVER DATA<br><br>\n\n\n".htmlentities(print_r(variable_zts_safe(isset_get($_SERVER)), true));
$trace     = "\n\nFUNCTION TRACE\n".htmlentities(var_export(variable_zts_safe(debug_trace()), true));

notify('error', '<pre> PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"'.$server.$session.$trace.'</pre>');

if(PLATFORM_HTTP){
    error_log('PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"');
    log_file('PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"', 'php-errors');
}

throw new bException('PHP ERROR ['.$errno.'] "'.$errstr.'" in "'.$errfile.'@'.$errline.'"', $errno);
?>
