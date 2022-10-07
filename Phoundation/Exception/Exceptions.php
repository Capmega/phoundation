<?php

namespace Phoundation\Exception;

use Exception;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Developer\Debug;
use Phoundation\Notify\Notification;
use Throwable;



/**
 * Class Exceptions
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Exceptions
 */
class Exceptions
{
    /**
     * Throw an "under-construction" exception
     *
     * @todo Detect function automatically if not specified
     * @param string|null $functionality
     * @return void
     * @throws UnderConstructionException
     */
    public static function underConstruction(?string $functionality = null): void
    {
        Notification::getInstance()
            ->setCode('under-construction')
            ->setGroups('developers')
            ->setTitle(tr('Obsolete function used'))
            ->setMessage(tr('Function ":function" is used in ":file@:@line" in project ":project"', [
                ':function' => Debug::currentFunction(1),
                ':file' => Debug::currentFile(1),
                ':line' => Debug::currentLine(1),
                ':project' => PROJECT]))
            ->send();

        if ($functionality) {
            throw new UnderConstructionException(tr('The functionality ":f" is under construction!', [':f' => $functionality]));
        }

        throw new UnderConstructionException(tr('This function is under construction!'));
    }



    /**
     * Throw an "obsolete" exception
     *
     * @todo Detect function automatically if not specified
     * @param string|null $functionality
     * @return void
     * @throws ObsoleteException
     */
    public static function obsolete(?string $functionality = null): void
    {
        Notification::getInstance()
            ->setCode('obsolete')
            ->setGroups('developers')
            ->setTitle(tr('Obsolete function used'))
            ->setMessage(tr('Function ":function" is used in ":file@:@line" in project ":project"', [
                ':function' => Debug::currentFunction(1),
                ':file' => Debug::currentFile(1),
                ':line' => Debug::currentLine(1),
                ':project' => PROJECT]))
            ->send();

        if ($functionality) {
            throw new ObsoleteException(tr('The functionality ":f" is obsolete!', [':f' => $functionality]));
        }

        throw new ObsoleteException(tr('This function is obsolete!'), 'obsolete');
    }



    /**
     * Throw an "not-supported" exception
     *
     * @todo Detect function automatically if not specified
     * @param string|null $functionality
     * @return void
     * @throws ObsoleteException
     */
    public static function notSupported(?string $functionality = null): void
    {
        Notification::getInstance()
            ->setCode('not-supported')
            ->setGroups('developers')
            ->setTitle(tr('Obsolete function used'))
            ->setMessage(tr('Function ":function" is used in ":file@:@line" in project ":project"', [
                ':function' => Debug::currentFunction(1),
                ':file' => Debug::currentFile(1),
                ':line' => Debug::currentLine(1),
                ':project' => PROJECT]))
            ->send();

        if ($functionality) {
            throw new NotSupportedException(tr('The functionality ":f" is not support!', array(':f' => $functionality)), 'not-supported');
        }

        throw new NotSupportedException(tr('This function is not supported!'), 'not-supported');
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
     * @param string|null $default
     * @return string
     */
    public static function getMessage(Throwable $e, array $messages = array(), string $default = null): string
    {
        // Set some default message codes
        Arrays::ensure($messages);
        array_default($messages, 'validation', $e->getMessages());
        array_default($messages, 'captcha'   , $e->getMessages());

        if (Debug::enabled()) {
            if ($e instanceof CoreException) {
                return $e->getMessages();
            }

            if ($e instanceof Exception) {
                return $e->getMessage();
            }

            throw new CoreException(tr('Specified $e is not an exception object'));

        } elseif (empty($messages[$e->getCode()])) {
            if (!$default) {
                return tr('Something went wrong, please try again');
            }

            return $default;
        }

        return $messages[$e->getCode()];
    }



    /**
     * Exceptions factory for OutOfBoundsException
     *
     * @param string|array $messages
     * @param mixed|null $data
     * @param string|null $code
     * @param Throwable|null $previous
     * @return \Phoundation\Exception\OutOfBoundsException
     */
    public static function outOfBoundsException(string|array $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null): OutOfBoundsException
    {
        return new OutOfBoundsException($messages, $data, $code, $previous);
    }
}