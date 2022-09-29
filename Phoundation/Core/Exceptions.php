<?php

namespace Phoundation\Core;

use Throwable;

/**
 * Class Exceptions
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Core
 */
class Exceptions
{
    /*
     * Throw an "under-construction" exception
     */
    function under_construction($functionality = '')
    {
        if ($functionality) {
            throw new OutOfBoundsException(tr('The functionality ":f" is under construction!', array(':f' => $functionality)), 'under-construction');
        }

        throw new OutOfBoundsException(tr('This function is under construction!'), 'under-construction');
    }


    /*
     * Throw an "obsolete" exception
     */
    function obsolete($functionality = '')
    {
        notify(array('code' => 'obsolete',
            'groups' => 'developers',
            'title' => tr('Obsolete function used'),
            'message' => tr('Function ":function" is used in ":file@:@line" in project ":project"', array(':function' => current_function(),
                ':file' => current_file(),
                ':line' => current_line(),
                ':project' => PROJECT))));

        if ($functionality) {
            throw new OutOfBoundsException(tr('The functionality ":f" is obsolete!', array(':f' => $functionality)), 'obsolete');
        }

        throw new OutOfBoundsException(tr('This function is obsolete!'), 'obsolete');
    }


    /*
     * Throw an "not-supported" exception
     */
    function not_supported($functionality = '')
    {
        if ($functionality) {
            throw new OutOfBoundsException(tr('The functionality ":f" is not support!', array(':f' => $functionality)), 'not-supported');
        }

        throw new OutOfBoundsException(tr('This function is not supported!'), 'not-supported');
    }



    /**
     * Return the correct error message that we want to display to users
     *
     * Warnings can (and will) always are about what a user did wrong and as such will be returned completely so that
     * users know what THEY did wrong, where "error" level exceptions will always only return a "Something went wrong,
     * please try again" message
     *
     * @param Throwable $e
     * @param array $messages
     * @param ?string $default
     * @return string
     */
    function getMessage(Throwable $e, array $messages = array(), string $default = null): string
    {
        /*
         * Set some default message codes
         */
        Arrays::ensure($messages);
        array_default($messages, 'validation', $e->getMessages());
        array_default($messages, 'captcha'   , $e->getMessages());

        if(debug()) {
            if($e instanceof BException) {
                return $e->getMessages();
            }

            if($e instanceof Exception) {
                return $e->getMessage();
            }

            throw new CoreException(tr('error_message(): Specified $e is not an exception object'), 'invalid');

        } elseif(empty($messages[$e->getCode()])) {
            if(!$default) {
                return tr('Something went wrong, please try again');
            }

            return $default;
        }

        return $messages[$e->getCode()];
    }
}