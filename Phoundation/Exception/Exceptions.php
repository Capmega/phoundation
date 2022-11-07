<?php

namespace Phoundation\Exception;

use Exception;
use Phoundation\Cli\Exception\CliInvalidArgumentsException;
use Phoundation\Cli\Exception\MethodNotFoundException;
use Phoundation\Core\Arrays;
use Phoundation\Core\Exception\ConfigException;
use Phoundation\Core\Exception\ConfigNotExistsException;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Developer\Debug;
use Phoundation\Filesystem\Exception\RestrictionsException;
use \Phoundation\Libraries\Exception\InitException;
use Phoundation\Notifications\Notification;
use Phoundation\Web\Exception\WebException;
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
        Notification::new()
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
        Notification::new()
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
        Notification::new()
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
        Arrays::default($messages, 'validation', $e->getMessages());
        Arrays::default($messages, 'captcha'   , $e->getMessages());

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
     * @return OutOfBoundsException
     */
    public static function outOfBoundsException(string|array $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null): OutOfBoundsException
    {
        return new OutOfBoundsException($messages, $data, $code, $previous);
    }



    /**
     * Exceptions factory for ScriptException
     *
     * @param string|array $messages
     * @param mixed|null $data
     * @param string|null $code
     * @param Throwable|null $previous
     * @return ScriptException
     */
    public static function ScriptException(string|array $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null): ScriptException
    {
        return new ScriptException($messages, $data, $code, $previous);
    }



    /**
     * Exceptions factory for WebException
     *
     * @param string|array $messages
     * @param mixed|null $data
     * @param string|null $code
     * @param Throwable|null $previous
     * @return WebException
     */
    public static function WebException(string|array $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null): WebException
    {
        return new WebException($messages, $data, $code, $previous);
    }



    /**
     * Exceptions factory for MethodNotFoundException
     *
     * @param string|array $messages
     * @param mixed|null $data
     * @param string|null $code
     * @param Throwable|null $previous
     * @return MethodNotFoundException
     */
    public static function MethodNotFoundException(string|array $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null): MethodNotFoundException
    {
        return new MethodNotFoundException($messages, $data, $code, $previous);
    }



    /**
     * Exceptions factory for CliInvalidArgumentsException
     *
     * @param string|array $messages
     * @param mixed|null $data
     * @param string|null $code
     * @param Throwable|null $previous
     * @return CliInvalidArgumentsException
     */
    public static function CliInvalidArgumentsException(string|array $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null): CliInvalidArgumentsException
    {
        return new CliInvalidArgumentsException($messages, $data, $code, $previous);
    }



    /**
     * Exceptions factory for ValidationFailedException
     *
     * @param string|array $messages
     * @param mixed|null $data
     * @param string|null $code
     * @param Throwable|null $previous
     * @return ValidationFailedException
     */
    public static function ValidationFailedException(string|array $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null): ValidationFailedException
    {
        return new ValidationFailedException($messages, $data, $code, $previous);
    }



    /**
     * Exceptions factory for ValidationFailedException
     *
     * @param string|array $messages
     * @param mixed|null $data
     * @param string|null $code
     * @param Throwable|null $previous
     * @return InitException
     */
    public static function InitException(string|array $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null): InitException
    {
        return new InitException($messages, $data, $code, $previous);
    }



    /**
     * Exceptions factory for NotExistsException
     *
     * @param string|array $messages
     * @param mixed|null $data
     * @param string|null $code
     * @param Throwable|null $previous
     * @return NotExistsException
     */
    public static function NotExistsException(string|array $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null): NotExistsException
    {
        return new NotExistsException($messages, $data, $code, $previous);
    }



    /**
     * Exceptions factory for ConfigException
     *
     * @param string|array $messages
     * @param mixed|null $data
     * @param string|null $code
     * @param Throwable|null $previous
     * @return ConfigException
     */
    public static function ConfigException(string|array $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null): ConfigException
    {
        return new ConfigException($messages, $data, $code, $previous);
    }



    /**
     * Exceptions factory for ConfigNotExistsException
     *
     * @param string|array $messages
     * @param mixed|null $data
     * @param string|null $code
     * @param Throwable|null $previous
     * @return ConfigNotExistsException
     */
    public static function ConfigNotExistsException(string|array $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null): ConfigNotExistsException
    {
        return new ConfigNotExistsException($messages, $data, $code, $previous);
    }



    /**
     * Exceptions factory for RestrictionsException
     *
     * @param string|array $messages
     * @param mixed|null $data
     * @param string|null $code
     * @param Throwable|null $previous
     * @return RestrictionsException
     */
    public static function RestrictionsException(string|array $messages, mixed $data = null, ?string $code = null, ?Throwable $previous = null): RestrictionsException
    {
        return new RestrictionsException($messages, $data, $code, $previous);
    }
}