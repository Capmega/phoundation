<?php
try{
    if(!debug()){
        return array();
    }

    $filters = array_force($filters);
    $trace   = array();

    foreach(debug_backtrace() as $key => $value){
        if($skip_own and ($key <= 1)){
            continue;
        }

        foreach($filters as $filter){
            unset($value[$filter]);
        }

        $trace[] = $value;
    }

    return $trace;

}catch(Exception $e){
    throw new BException('debug_trace(): Failed', $e);
}
?>