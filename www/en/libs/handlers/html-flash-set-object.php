<?php
/*
 * Implementation of html_flash_set() section where object messages are
 * processed
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package html
 */
$object = $params;
$params = array('class' => $class, // Done for backward compatibility
                'title' => tr('Oops'));

if($object instanceof BException){
    if(debug()){
        if(($object->getCode() !== 'validation') and (str_until($object->getCode(), '/') !== 'warning')){
            /*
             * This is not a warning, this is a real exception!
             * On non debugs (usually production) show an "oops"
             * html_flash(), but on debug systems, make it an
             * uncaught exception so that it can be fixed
             */
            throw($object->setCode('_'.$object->getCode()));
        }
    }

    if(!$class){
        $class = $type;
    }

    if(str_until($object->getCode(), '/') == 'warning'){
        $params['type'] = 'warning';
        $params['html'] = trim(str_from($object->getMessage(), '():'));

    }elseif($object->getCode() == 'validation'){
        foreach($object->getMessages() as $message){
            if(strstr($message, 'ValidateForm::isValid()')){
                break;
            }

            $messages[] = $message;
        }

        $params['type'] = 'warning';
        $params['html'] = implode('<br>', $messages);

    }elseif(str_from($object->getCode(), '/') == 'unknown'){
        $params['type'] = 'warning';
        $params['html'] = $object->getMessage();

    }else{
        $params['type'] = 'error';

        if(debug()){
            $params['html'] = $object->getMessage();

        }else{
            /*
             * This may or may not contain messages that are confidential.
             * All BExceptions thrown by functions will contain the function name like function():
             * If (): is detected in the primary message, assume it must be confidential
             * If PHP error is detected in the primary message, assume it must be confidential
             * Any other messages are generated by the web pages themselves and should be
             * considered ok to show on production sites
             */
            $messages       = $object->getMessages();
            $params['html'] = current($messages);

            if(preg_match('/^[a-z_]+\(\): /', $params['html']) or preg_match('/PHP ERROR [\d+] /', $params['html'])){
                $params['html'] = tr('Something went wrong, please try again later');
                notify('html_flash/BException', tr('html_flash_set(): Received BException ":code" with message trace ":trace"', array(':code' => $params['type'], ':trace' => $params['html'])), 'developers');

            }else{
                /*
                 * Show all messages until a function(): message is found, those are considered to be
                 * confidential and should not be shown on production websites
                 */
                foreach($messages as $id => $message){
                    if(!empty($delete) or preg_match('/^[a-z_]+\(\): /', $message) or preg_match('/PHP ERROR [\d+] /', $message)){
                        unset($messages[$id]);
                        $delete = true;
                    }
                }

                unset($delete);
                $params['html'] = implode('<br>', $messages);
            }
        }
    }

}elseif($object instanceof Exception){
    if(!$class){
        $class = $type;
    }

    if(str_from($object->getCode(), '/') == 'validation'){
        $params['type'] = 'warning';
        $params['html'] = $object->getMessage();

    }else{
        $params['type'] = 'error';

        if(debug()){
            $params['html'] = $object->getMessage();

        }else{
            /*
             * Non BExceptions basically are caused by PHP and should basically not ever happen.
             * These should also be considdered confidential and their info should never be
             * displayed in production sites
             */
            $params['html'] = tr('Something went wrong, please try again later');
            notify('html_flash/Exception', tr('html_flash_set(): Received PHP exception class ":class" with code ":code" and message ":message"', array(':class' => get_class($object), ':code' => str_from($object->getCode(), '/'), ':message' => $object->getMessage())), 'developers');
        }
    }

}else{
    $params['type'] = 'error';
    $params['html'] = tr('Something went wrong, please try again later');
    notify('html_flash/object', tr('html_flash_set(): Received PHP object with class ":class" and content ":content"', array(':class' => get_class($object), ':content' => print_r($object->getMessage(), true))), 'developers');
}

$_SESSION['flash'][] = $params;
?>