<?php

declare(strict_types=1);

namespace Phoundation\Utils;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Core;
use Phoundation\Core\Exception\CoreException;
use Phoundation\Core\Log\Log;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Enums\EnumJsonAfterReply;
use Phoundation\Utils\Enums\Interfaces\EnumJsonAfterReplyInterface;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Response;
use Stringable;
use Throwable;


/**
 * Class Json
 *
 * This class contains various JSON functions
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Utils
 */
class Json
{
    /**
     * Send correct JSON reply
     *
     * @param array|Stringable|string|null $data
     * @param EnumJsonAfterReplyInterface $action_after
     * @return void
     */
    #[NoReturn] public static function reply(array|Stringable|string|null $data = null, EnumJsonAfterReplyInterface $action_after = EnumJsonAfterReply::die): void
    {
        // Always return all numbers as strings as javascript borks BADLY on large numbers, WTF JS?!
        if (is_array($data)) {
            $data = array_map(function ($value) {
                if (is_numeric($value)) {
                    return strval($value);
                }

                return $value;

            }, $data);
        }

        if (!is_string($data)) {
            if (is_object($data)) {
                // Stringable object
                $data = (string)$data;

            } else {
                // Array, JSON encode
                $data = Json::encode($data);
            }
        }

        Response::setContentType('application/json');
        Response::addOutput($data);
        Response::send(false);

        switch ($action_after) {
            case EnumJsonAfterReply::die:
                // We're done, kill the connection % process (default)
                exit();

            case EnumJsonAfterReply::continue:
                // Continue running, keep the HTTP connection open
                break;

            case EnumJsonAfterReply::closeConnectionContinue:
                // Close the current HTTP connection but continue in the background
                session_write_close();
                fastcgi_finish_request();
                break;

            default:
                throw new OutOfBoundsException(tr('Unknown after ":after" specified. Use one of "JsonAfterReply::die", "JsonAfterReply::continue", or "JsonAfterReply::closeConnectionContinue"', [
                    ':after' => $action_after
                ]));
        }
    }


    /**
     * Send JSON error to client
     *
     * @param string|array|null $message
     * @param mixed $data
     * @param mixed $result
     * @param int $http_code The HTTP code to send out with Json::reply()
     * @return void (dies)
     * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
     * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
     * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
     * @category Function reference
     * @package json
     * @see Json::reply()
     * @see Json::message()
     * @version 2.7.102: Added function and documentation
     * @note Uses Json::reply() to send the error to the client
     * @todo Fix $data and $result parameters. Are they used correctly? They are sometimes overwritten in the method
     */
    public static function error(string|array|null $message, $data = null, $result = null, int $http_code = 500): void
    {
        if (!$message) {
            $message = '';

        } elseif (is_scalar($message)) {

        } elseif (is_array($message)) {
            if (empty($message['default'])) {
                $default = tr('Something went wrong, please try again later');

            } else {
                $default = $message['default'];
                unset($message['default']);
            }

            if (empty($message['e'])) {
                if (Core::isProductionEnvironment()) {
                    $message = $default;
                    Log::warning('No exception object specified for following error');
                    Log::warning($message);

                } else {
                    if (count($message) == 1) {
                        $message = array_pop($message);
                    }
                }

            } else {
                if (Core::isProductionEnvironment()) {
                    Log::notice($message['e']);

                    $code = $message['e']->getCode();

                    if (empty($message[$code])) {
                        $message = $default;

                    } else {
                        $message = $message[$code];
                    }

                } else {
                    $message = $message['e']->getMessages("\n<br>");
                }
            }

            $message = trim(Strings::from($message, '():'));

        } elseif (is_object($message)) {
            // Assume this is an CoreException object
            if (!($message instanceof CoreException)) {
                if (!($message instanceof Exception)) {
                    $type = gettype($message);

                    if ($type === 'object') {
                        $type .= '/' . get_class($message);
                    }

                    throw new JsonException(tr('Specified message must either be a string or an CoreException ojbect, or PHP Exception ojbect, but is a ":type"', [
                        ':type' => $type
                    ]));
                }

                $code = $message->getCode();

                if (Debug::getEnabled()) {
                    /*
                     * This is a user visible message
                     */
                    $message = $message->getMessage();

                } elseif (!empty($default)) {
                    $message = $default;
                }

            } else {
                $result = $message->getCode();

                switch ($result) {
                    case 'access-denied':
                        $http_code = '403';
                        break;

                    case 'ssl-required':
                        $http_code = '403.4';
                        break;

                    default:
                        $http_code = '500';
                }

                if (Strings::until($result, '/') == 'warning') {
                    $data = $message->getMessage();

                } else {
                    if (Debug::getEnabled()) {
                        // This is a user visible message
                        $messages = $message->getMessages();

                        foreach ($messages as $id => &$message) {
                            $message = trim(Strings::from($message, '():'));

                            if ($message == tr('Failed')) {
                                unset($messages[$id]);
                            }
                        }

                        unset($message);

                        $data = implode("\n", $messages);

                    } elseif (!empty($default)) {
                        $message = $default;
                    }
                }
            }
        }

        $data = Arrays::force($data);

        Json::reply($data, ($result ? $result : 'ERROR'), $http_code);
    }


    /**
     * Send a JSON message
     *
     * @param string|int|object $code
     * @param mixed $data
     * @return void
     */
    public static function message(string|int|object $code, mixed $data = null): void
    {
        if (is_object($code)) {
            if (!$code instanceof Throwable) {
                throw new OutOfBoundsException(tr('Specified code is a ":code" object class. Code must be an numeric HTTP code, a key word string or an exception object', [
                    ':code' => $code
                ]));
            }

            // This is (presumably) an exception
            $code = $code->getCode();
        }

        if (str_contains($code, '_')) {
            // Codes should always use -, never _
            Notification::new()
                ->setException(new JsonException(tr('Specified code ":code" contains an _ which should never be used, always use a -', [
                    ':code' => $code
                ])))
                ->send();
        }

        switch ($code) {
            case 301:
                // no-break
            case 'redirect':
                Json::error(null, ['location' => $data], 'REDIRECT', 301);

            case 302:
                Json::error(null, ['location' => UrlBuilder::getAjax($_CONFIG['redirects']['signin'])], 'REDIRECT', 302);

            case 'signin':
                Json::error(null, ['location' => UrlBuilder::getAjax($_CONFIG['redirects']['signin'])], 'SIGNIN', 302);

            case 400:
                // no-break
            case 'invalid':
                // no-break
            case 'validation':
                Json::error(null, $data, 'BAD-REQUEST', 400);

            case 'locked':
                Json::error(null, $data, 'LOCKED', 403);

            case 403:
                // no-break
            case 'forbidden':
                // no-break
            case 'access-denied':
                Json::error(null, $data, 'FORBIDDEN', 403);

            case 404:
                // no-break
            case 'not-found':
                Json::error(null, $data, 'NOT-FOUND', 404);

            case 'not-exists':
                Json::error(null, $data, 'NOT-EXISTS', 404);

            case 405:
                // no-break
            case 'method-not-allowed':
                Json::error(null, $data, 'METHOD-NOT-ALLOWED', 405);

            case 406:
                // no-break
            case 'not-acceptable':
                Json::error(null, $data, 'NOT-ACCEPTABLE', 406);

            case 408:
                // no-break
            case 'timeout':
                Json::error(null, $data, 'TIMEOUT', 408);

            case 409:
                // no-break
            case 'conflict':
                Json::error(null, $data, 'CONFLICT', 409);

            case 412:
                // no-break
            case 'expectation-failed':
                Json::error(null, $data, 'EXPECTATION-FAILED', 412);

            case 418:
                // no-break
            case 'im-a-teapot':
                Json::error(null, $data, 'IM-A-TEAPOT', 418);

            case 429:
                // no-break
            case 'too-many-requests':
                Json::error(null, $data, 'TOO-MANY-REQUESTS', 429);

            case 451:
                // no-break
            case 'unavailable-for-legal-reasons':
                Json::error(null, $data, 'UNAVAILABLE-FOR-LEGAL-REASONS', 451);

            case 500:
                // no-break
            case 'error':
                Json::error(null, $data, 'ERROR', 500);

            case 503:
                // no-break
            case 'maintenance':
                // no-break
            case 'service-unavailable':
                Json::error(null, null, 'SERVICE-UNAVAILABLE', 503);

            case 504:
                // no-break
            case 'gateway-timeout':
                Json::error(null, null, 'GATEWAY-TIMEOUT', 504);

            case 'reload':
                Json::reply(null, 'RELOAD');

            default:
                Notification::new()
                    ->setMode(EnumDisplayMode::exception)
                    ->setCode('unknown')
                    ->setRoles('developer')
                    ->setTitle('Unknown message specified')
                    ->setMessage(tr('Json::message(): Unknown code ":code" specified', [':code' => $code]));

                Json::error(null, (Debug::getEnabled() ? $data : null), 'ERROR', 500);
        }
    }


    /**
     * Encode the specified variable into a JSON string
     *
     * @param mixed $source
     * @param int $options
     * @param int $depth Until what depth will we recurse until an exception will be thrown
     * @return string
     * @throws JsonException If JSON encoding failed
     */
    public static function encode(mixed $source, int $options = 0, int $depth = 512): string
    {
        if ($source === null) {
            return '';
        }

        $return = json_encode($source, $options, $depth);

        if (json_last_error()) {
            throw new JsonException(tr('JSON encoding failed with :error', [':error' => json_last_error_msg()]));
        }

        return $return;
    }


    /**
     * Ensure that the specified source is encoded into a JSON string
     *
     * This method will assume that given strings are encoded JSON. Anything else will be encoded into a JSON string
     *
     * @param mixed $source
     * @param int $options
     * @param int $depth Until what depth will we recurse until an exception will be thrown
     * @return string
     * @throws JsonException If JSON encoding failed
     */
    public static function ensureEncoded(mixed $source, int $options = 0, int $depth = 512): string
    {
        if (is_string($source)) {
            // Assume this is a JSON string
            return $source;
        }

        return static::encode($source, $options, $depth);
    }


    /**
     * Decode the given JSON string back into the original data. Can optionally decode into standard object classes or
     * arrays [default]
     *
     * @param mixed $source
     * @param int $options
     * @param int $depth Until what depth will we recurse until an exception will be thrown
     * @param bool $as_array If $as_array is set true [default] then this method will always return an array. If not,
     *                       it will return a PHP JSON object
     * @return mixed The decoded variable
     * @throws JsonException
     */
    public static function decode(?string $source, int $options = 0, int $depth = 512, bool $as_array = true): mixed
    {
        if ($source === null) {
            return null;
        }

        $return = json_decode($source, $as_array, $depth, $options);

        if (json_last_error()) {
            throw new JsonException(tr('JSON decoding failed with ":error"', [':error' => json_last_error_msg()]));
        }

        return $return;
    }


    /**
     * Ensure the given variable is decoded.
     *
     * If it is a JSON string it will be decoded back into the original data. If it is not a string, this method will
     * assume it already was decoded. Can optionally decode into standard object classes or arrays [default]
     *
     * @param mixed $source
     * @param int $options
     * @param int $depth Until what depth will we recurse until an exception will be thrown
     * @param bool $as_array If $as_array is set true [default] then this method will always return an array. If not,
     *                       it will return a PHP JSON object
     * @return mixed The decoded variable
     * @throws JsonException
     */
    public static function ensureDecoded(mixed $source, int $options = 0, int $depth = 512, bool $as_array = true): mixed
    {
        if ($source and is_string($source)) {
            return static::decode($source, $options, $depth, $as_array);
        }

        return $source;
    }


    /**
     * Returns the specified source, but limiting its maximum size to the specified $max_size.
     *
     * If it crosses this threshold, it will truncate entries in the $source array
     *
     * @param array|string $source
     * @param int $max_size
     * @param string $fill
     * @param string $method
     * @param bool $on_word
     * @param int $options
     * @param int $depth
     * @return string
     */
    public static function encodeTruncateToMaxSize(array|string $source, int $max_size, string $fill = ' ... [TRUNCATED] ... ', string $method = 'right', bool $on_word = false, int $options = 0, int $depth = 512): string
    {
        if (is_string($source)) {
            if (strlen($source) <= $max_size) {
                // We're already done, no need for more!
                return $source;
            }

            $string = $source;
            $array = static::decode($source, $options, $depth);
        } else {
            $array  = $source;
            $string = static::encode($source, $options, $depth);
        }

        if ($max_size < 64) {
            throw new OutOfBoundsException(tr('Cannot truncate JSON string to ":size" characters, the minimum is 64 characters', [
                ':size' => $max_size
            ]));
        }

        while (strlen($string) > $max_size) {
            // Okay, we're over max size
            $keys    = count($source);
            $average = floor((strlen($string) / $keys) - ($keys * 8));

            if ($average < 1) {
                $average = 10;
            }

            // Truncate and re-encode the truncated array and check size again
            $array  = Arrays::truncate($array, $average, $fill, $method, $on_word);
            $string = Json::encode($array);
        }

        return $string;
    }
}
