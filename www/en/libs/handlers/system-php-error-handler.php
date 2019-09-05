<?php
global $core;

if(!isset($core)){
    throw new BException(tr('Pre core available PHP ERROR [:errno] ":errstr" in ":errfile@:errline"', array(':errstr' => $errstr, ':errno' => $errno, ':errfile' => $errfile, ':errline' => $errline)), 'PHP'.$errno);
}

if(empty($core->register['ready'])){
    throw new BException(tr('Pre core ready PHP ERROR [:errno] ":errstr" in ":errfile@:errline"', array(':errstr' => $errstr, ':errno' => $errno, ':errfile' => $errfile, ':errline' => $errline)), 'PHP'.$errno);
}

$trace = debug_trace();
unset($trace[0]);
unset($trace[1]);

notify(array('code'    => 'PHP-ERROR-'.$errno,
             'groups'  => 'developers',
             'title'   => tr('PHP ERROR ":errno"', array(':errno' => $errno)),
             'data'    => $trace,
             'throw'   => false,
             'message' => tr('PHP ERROR [:errno] ":errstr" in ":errfile@:errline"', array(':errstr' => $errstr, ':errno' => $errno, ':errfile' => $errfile, ':errline' => $errline))));

throw new BException(tr('PHP ERROR [:errno] ":errstr" in ":errfile@:errline"', array(':errstr' => $errstr, ':errno' => $errno, ':errfile' => $errfile, ':errline' => $errline)), 'PHP'.$errno);
