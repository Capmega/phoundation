<?php

/**
 * Class Json
 *
 * This class contains various JSON methods and can reply with JSON data structures to the client
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Utils
 */


declare(strict_types=1);

namespace Phoundation\Utils;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Core;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Enums\EnumJsonAfterReply;
use Phoundation\Utils\Enums\EnumJsonResponse;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderJsonInterface;
use Phoundation\Web\Html\Components\P;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Json\Interfaces\JsonHtmlSectionInterface;
use Phoundation\Web\Requests\Response;
use Stringable;
use Throwable;


class Json
{
    /**
     * Tracks what this object will do after Json::new()->reply() finishes
     *
     * @var EnumJsonAfterReply $action_after
     */
    protected EnumJsonAfterReply $action_after = EnumJsonAfterReply::die;

    /**
     * Tracks the reply that will be given. One of ok, error, signin, redirect, reload
     *
     * @var EnumJsonResponse|null $response
     */
    protected ?EnumJsonResponse $response;


    /**
     * Json class constructor
     *
     * @param EnumJsonResponse $response
     */
    public function __construct(EnumJsonResponse $response = EnumJsonResponse::ok)
    {
        $this->setResponse($response);
    }


    /**
     * Returns a new Json object
     *
     * @param EnumJsonResponse $reply
     *
     * @return static
     */
    public static function new(EnumJsonResponse $reply = EnumJsonResponse::ok): static
    {
        return new static($reply);
    }


    /**
     * Returns what this object will do after Json::new()->reply() finishes
     *
     * @return EnumJsonAfterReply
     */
    public function getActionAfter(): EnumJsonAfterReply
    {
        return $this->action_after;
    }


    /**
     * Returns the reply that will be given. One of ok, error, signin, redirect, reload
     *
     * @param EnumJsonAfterReply $action_after
     */
    public function setActionAfter(EnumJsonAfterReply $action_after): void
    {
        $this->action_after = $action_after;
    }


    /**
     * Returns the reply that will be given. One of ok, error, signin, redirect, reload
     *
     * @return EnumJsonResponse|null
     */
    public function getResponse(): ?EnumJsonResponse
    {
        // Apply default reply
        if ($this->response === null) {
            $this->response = static::getDefaultResponseForHttpCode(Response::getHttpCode());
        }

        return $this->response;
    }


    /**
     * Sets the reply that will be given. One of ok, error, signin, redirect, reload
     *
     * @param EnumJsonResponse|null $response
     *
     * @return Json
     */
    public function setResponse(?EnumJsonResponse $response): static
    {
        $this->response = $response;

        return $this;
    }


    /**
     * Send a JSON message from an HTTP code
     *
     * @param string|int|Exception $code
     * @param mixed                $data
     *
     * @return void
     */
    public function replyWithHttpCode(string|int|Throwable $code, mixed $data = null): void
    {
        // Get valid HTTP code, as code here may also be code words
        $int_code = static::getHttpCode($code);

        Response::setHttpCode($int_code);

        // Process code specific replies
        switch ($code) {
            case 'reload':
                $this->setResponse(EnumJsonResponse::reload)
                     ->reply();

            case 'signin':
                $this->setResponse(EnumJsonResponse::signin)
                     ->reply(['location' => $data]);
        }

        // Process HTTP code specific replies
        switch ($int_code) {
            case 301:
                $this->setResponse(EnumJsonResponse::redirect)
                     ->reply(['location' => $data]);

            case 302:
                $this->setResponse(EnumJsonResponse::redirect)
                     ->reply(['location' => $data]);

            case 400:
                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => $data ?? tr('bad request')]);

            case 403:
                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => $data ?? tr('forbidden')]);

            case 404:
                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => $data ?? tr('not found')]);

            case 405:
                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => $data ?? tr('method not allowed')]);

            case 406:
                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => $data ?? tr('not acceptable')]);

            case 408:
                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => $data ?? tr('timeout')]);

            case 409:
                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => $data ?? tr('conflict')]);

            case 412:
                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => $data ?? tr('expectation failed')]);

            case 418:
                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => $data ?? tr('im a teapot')]);

            case 429:
                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => $data ?? tr('too many requests')]);

            case 451:
                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => $data ?? tr('unavailable for legal reasons')]);

            case 500:
                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => $data ?? tr('internal server error')]);

            case 503:
                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => $data ?? tr('service unavailable')]);

            case 504:
                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => $data ?? tr('gateway timeout')]);

            default:
                Notification::new()
                            ->setMode(EnumDisplayMode::exception)
                            ->setCode('unknown')
                            ->setRoles('developer')
                            ->setTitle('Unknown message specified')
                            ->setMessage(tr('Json::message(): Unknown code ":code" specified', [':code' => $code]))
                            ->setDetails([
                                'code' => $code,
                                'data' => $data
                            ]);

                $this->setResponse(EnumJsonResponse::error)
                     ->reply(['message' => tr('internal server error')]);
        }
    }


    /**
     * Send JSON error to the client
     *
     * @param mixed $data
     *
     * @return void
     *
     * @see Json::reply()
     * @see Json::replyWithHttpCode()
     */
    #[NoReturn] public function error(array|Stringable|string|null $data = null): void
    {
        if (!$data) {
            $data = tr('Something went wrong, please try again later');

        } elseif ($data instanceof Throwable) {
            if ($data instanceof Exception) {
                if ($data->isWarning()) {
                    $data = $data->getMessage();

                } elseif (Debug::isEnabled()) {
                    $data = $data->getSource();

                } else {
                    $data = tr('Something went wrong, please try again later');
                }

            } elseif (Debug::isEnabled()) {
                $data = $data->getSource();

            } else {
                $data = tr('Something went wrong, please try again later');
            }
        }

        $this->reply($data);
    }


    /**
     * Send a JSON reply
     *
     * @param RenderJsonInterface $data
     *
     * @todo Split this in 3 functions, one for exit(), one for continue, one for close connection and continue
     * @return void
     */
    #[NoReturn] public function replyWithHtml(RenderJsonInterface $data): void
    {
        if ($data instanceof JsonHtmlSectionInterface) {
            // This is just a single HTML section, make a list out of it
            $this->doReply(['html' => [$data->renderJson()]]);

        } else {
            $this->doReply($data->renderJson());
        }
    }


    /**
     * Send a JSON reply
     *
     * @param array|Stringable|string|null $data
     *
     * @todo Split this in 3 functions, one for exit(), one for continue, one for close connection and continue
     * @return void
     */
    #[NoReturn] public function reply(array|Stringable|string|null $data = null): void
    {
        // Clean up the data array
        $data = static::normalizeData($data);
        $data = static::fixJavascriptNumbers($data);

        static::doReply(['data' => $data]);
    }


    /**
     * Send a JSON reply
     *
     * @param array|Stringable|string|null $data
     *
     * @todo Split this in 3 functions, one for exit(), one for continue, one for close connection and continue
     * @return void
     */
    #[NoReturn] protected function doReply(array|Stringable|string|null $data = null): void
    {
        // Clean up the data array
        $data = static::createMessage($data);

        Response::setContentType('application/json');
        Response::setOutput(Json::encode($data));
        Response::send(false);

        static::afterAction();
    }


    /**
     * Encode the specified variable into a JSON string
     *
     * @param mixed $source
     * @param int   $options
     * @param int   $depth Until what depth will we recurse until an exception will be thrown
     *
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
     * @param int   $options
     * @param int   $depth Until what depth will we recurse until an exception will be thrown
     *
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
     * Ensure the given variable is decoded.
     *
     * If it is a JSON string it will be decoded back into the original data. If it is not a string, this method will
     * assume it already was decoded. Can optionally decode into standard object classes or arrays [default]
     *
     * @param mixed $source
     * @param int   $options
     * @param int   $depth    Until what depth will we recurse until an exception will be thrown
     * @param bool  $as_array If $as_array is set true [default] then this method will always return an array. If not,
     *                        it will return a PHP JSON object
     *
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
     * Decode the given JSON string back into the original data. Can optionally decode into standard object classes or
     * arrays [default]
     *
     * @param mixed $source
     * @param int   $options
     * @param int   $depth    Until what depth will we recurse until an exception will be thrown
     * @param bool  $as_array If $as_array is set true [default] then this method will always return an array. If not,
     *                        it will return a PHP JSON object
     *
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
     * Returns the specified source, but limiting its maximum size to the specified $max_size.
     *
     * If it crosses this threshold, it will truncate entries in the $source array
     *
     * @param array|string $source
     * @param int          $max_size
     * @param string       $fill
     * @param string       $method
     * @param bool         $on_word
     * @param int          $options
     * @param int          $depth
     *
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
            $array  = static::decode($source, $options, $depth);

        } else {
            $array  = $source;
            $string = static::encode($source, $options, $depth);
        }

        if ($max_size < 64) {
            throw new OutOfBoundsException(tr('Cannot truncate JSON string to ":size" characters, the minimum is 64 characters', [
                ':size' => $max_size,
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


    /**
     * Returned data will ALWAYS be in a key > value array
     *
     * If no data is returned, an empty data array will be returned
     *
     * If a single string is returned, the key "message" will be assumed
     *
     * @param array|Stringable|string|null $data
     *
     * @return array|string[]
     */
    protected static function normalizeData(array|Stringable|string|null $data): array
    {
        if (!$data) {
            return [];
        }

        if (is_array($data)) {
            return $data;
        }

        return ['message' => (string) $data];
    }


    /**
     * Fixes all data numbers, making them strings, as Javascript borks BADLY on large numbers, WTF JS?!
     *
     * @param array $data
     *
     * @return array
     */
    protected static function fixJavascriptNumbers(array $data): array
    {
        if (is_array($data)) {
            $data = array_map(function ($value) {
                if (is_numeric($value)) {
                    return strval($value);
                }

                return $value;

            }, $data);
        }

        return $data;
    }


    /**
     * Returns the default JSON response for the given HTTP code
     *
     * @param int $http_code
     *
     * @return EnumJsonResponse
     */
    protected static function getDefaultResponseForHttpCode(int $http_code): EnumJsonResponse
    {
        return match ($http_code) {
            200, 304      => EnumJsonResponse::ok,
            301, 302, 307 => EnumJsonResponse::redirect,
            401           => EnumJsonResponse::signin,
            default       => EnumJsonResponse::error,
        };
    }


    /**
     * Creates and returns the JSON reply message
     *
     * @param array $data
     *
     * @return array
     */
    protected function createMessage(array $data): array
    {
        $expose = Core::getExposePhoundation();

        switch ($expose) {
            case 'full':
                $data['phoundation'] = 'Phoundation/' . FRAMEWORK_CODE_VERSION;
                break;

            case 'limited':
                $data['phoundation'] = 'phoundation';
                break;

            case 'fake':
                $data['phoundation'] = 'phoundation/4.11.1';
                break;
        }

        $data['response']  = $this->getResponse();
        $data['http_code'] = Response::getHttpCode();
        $data['flash']     = Response::getFlashMessagesObject()->renderJson();

        return $data;
    }


    /**
     * Determines what the action is after a Json::reply() call, continue, die, or close connection and continue
     *
     * @return void
     */
    protected function afterAction(): void
    {
        switch ($this->action_after) {
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
        }
    }


    /**
     * Returns a valid, integer HTTP code for the specified code
     *
     * @param string|int|Throwable $code
     *
     * @return int
     */
    protected static function getHttpCode(string|int|Throwable $code): int
    {
        if (is_int($code)) {
            return $code;
        }

        if (is_object($code)) {
            // This is an exception
            $code = $code->getCode();
        }

        if (str_contains((string)$code, '_')) {
            // Codes should always use -, never _
            Notification::new()
                ->setException(new JsonException(tr('Specified code ":code" contains an _ which should never be used, always use a -', [
                    ':code' => $code,
                ])))
                ->send();
        }

        switch ($code) {
            case 'reload':
            case 'redirect':
                return 301;

            case 'signin':
                return 302;

            case 'invalid':
            case 'validation':
                return 400;

            case 'locked':
            case 'forbidden':
            case 'access-denied':
                return 403;

            case 'not-found':
            case 'not-exists':
                return 404;

            case 'method-not-allowed':
                return 405;

            case 'not-acceptable':
                return 406;

            case 'timeout':
                return 408;

            case 'conflict':
                return 409;

            case 'expectation-failed':
                return 412;

            case 'im-a-teapot':
                return 418;

            case 'too-many-requests':
                return 429;

            case 'unavailable-for-legal-reasons':
                return 451;

            case 'error':
                return 500;

            case 'maintenance':
            case 'service-unavailable':
                return 503;

            case 'gateway-timeout':
                return 504;
        }

        throw new OutOfBoundsException(tr('Unknown or unsupported HTTP code ":code" specified', [
            ':code' => $code,
        ]));
    }
}
