<?php
/*
 * Handler code for error_message() function
 */
try{
    /*
     * Set some default message codes
     */
    array_ensure($messages);
    array_default($messages, 'validation', $e->getMessages());
    array_default($messages, 'captcha'   , $e->getMessages());

    if(debug()){
        if($e instanceof BException){
            return $e->getMessages();
        }

        if($e instanceof Exception){
            return $e->getMessage();
        }

        throw new BException(tr('error_message(): Specified $e is not an exception object'), 'invalid');

    }elseif(empty($messages[$e->getCode()])){
        if(!$default){
            return tr('Something went wrong, please try again');
        }

        return $default;
    }

    return $messages[$e->getCode()];

}catch(Exception $e){
    throw new BException('error_message(): Failed', $e);
}
?>
