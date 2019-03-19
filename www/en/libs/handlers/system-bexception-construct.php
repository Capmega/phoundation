<?php
global $core;

if($code === 'missing-module'){
    if($data === 'mb'){
        /*
         * VERY low level exception, the multibyte module is not installed. Die directly @startup
         */
        die($messages);
    }
}

$messages = array_force($messages, "\n");

if(is_object($code)){
    /*
     * Specified code is not a code but a previous exception. Get
     * history from previous exception and add new exception message
     */
    $e    = $code;
    $code = null;

    if($e instanceof BException){
        $this->messages = $e->getMessages();
        $this->data     = $e->getData();

    }else{
        if(!($e instanceof Exception)){
            if(!($e instanceof Error)){
                throw new BException(tr('BException: Specified exception object for exception ":message" is not valid (either it is not an object or not a PHP Exception or PHP Error object)', array(':message' => $messages)), 'invalid');
            }
        }

        $this->messages[] = $e->getMessage();
    }

    $orgmessage = $e->getMessage();
    $code       = $e->getCode();

    if($data){
        $this->data = $data;

    }elseif(method_exists($e, 'getData')){
        $this->data = $e->getData();
    }

}else{
    if(!is_scalar($code)){
        throw new BException(tr('BException: Specified exception code ":code" for exception ":message" is not valid (should be either scalar, or an exception object)', array(':code' => $code, ':message' => $messages)), 'invalid');
    }

    $orgmessage = reset($messages);
    $this->data = $data;
}

if(!$messages){
    throw new Exception(tr('BException: No exception message specified in file ":file" @ line ":line"', array(':file' => current_file(1), ':line' => current_line(1))));
}

if(!is_array($messages)){
    $messages = array($messages);
}

parent::__construct($orgmessage, null);
$this->code = (string) $code;

/*
 * If there are any more messages left, then add them as well
 */
if($messages){
    foreach($messages as $id => $message){
        $this->messages[] = $message;
    }
}
?>