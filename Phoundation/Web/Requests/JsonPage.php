<?php

/**
 * Class JsonPage
 *
 * This class contains methods to assist in building web pages
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Core\Core;
use Phoundation\Developer\Debug;
use Phoundation\Exception\Exception;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Notifications\Notification;
use Phoundation\Utils\Enums\EnumJsonAfterReply;
use Phoundation\Utils\Enums\EnumJsonResponse;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderJsonInterface;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces\FlashMessageInterface;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces\FlashMessagesInterface;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Json\Interfaces\JsonHtmlInterface;
use Phoundation\Web\Requests\Interfaces\JsonPageInterface;
use Stringable;
use Throwable;


class JsonPage implements JsonPageInterface
{
    /**
     * Tracks HTML sections for this reply
     *
     * @var array $html
     */
    protected array $html = [];

    /**
     * Tracks HTML flash messages for this reply
     *
     * @var array $flash
     */
    protected array $flash = [];

    /**
     * Tracks what this object will do after JsonPage::new()->reply() finishes
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
     * Returns what this object will do after JsonPage::new()->reply() finishes
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
    #[NoReturn] public function replyWithError(array|Stringable|string|null $data = null): void
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
     * Adds HTML sections to the JSON reply
     *
     * @param RenderJsonInterface $sections
     *
     * @return static
     */
    public function addHtmlSections(RenderJsonInterface $sections): static
    {
        if ($sections instanceof JsonHtmlInterface) {
            // Multiple HTML sections, add each one individually
            foreach ($sections as $section) {
                $this->addHtmlSections($section);
            }

        } else {
            // This is just a single HTML section, make a list out of it
            $this->html[] = $sections->renderJson();
        }

        return $this;
    }


    /**
     * Adds HTML flash message sections to the JSON reply
     *
     * @param FlashMessagesInterface|FlashMessageInterface $messages
     *
     * @return static
     */
    public function addFlashMessageSections(FlashMessagesInterface|FlashMessageInterface $messages): static
    {
        if ($messages instanceof FlashMessagesInterface) {
            // Multiple HTML sections, add each one individually
            foreach ($messages as $message) {
                $this->addFlashMessageSections($message);
            }

        } else {
            // This is just a single HTML section, make a list out of it
            $this->flash[] = $messages->renderJson();
        }

        return $this;
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
        // Clean up the data array and create a message
        $data = static::createMessage($data);

        Response::setContentType('application/json');
        Response::setOutput(Json::encode($data));
        Response::send(false);

        static::afterAction();
    }


    /**
     * Execute the specified JSON page
     *
     * @return string|null
     */
    public function execute(): ?string
    {
        return execute();
    }


    /**
     * Build and send JSON specific HTTP headers
     *
     * @param string $output
     *
     * @return void
     */
    public function renderHttpHeaders(string $output): void
    {
        Response::setContentType('application/json');
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
     * @param array|Stringable|string|null $data
     *
     * @return array
     */
    protected function createMessage(array|Stringable|string|null $data): array
    {
        // Clean up the data array
        $data = static::normalizeData($data);
        $data = static::fixJavascriptNumbers($data);

        // What kind of exposure are we going to give?
        $expose = match (Core::getExposePhoundation()) {
            'full'    => 'Phoundation/' . FRAMEWORK_CODE_VERSION,
            'limited' => 'phoundation',        // No version at all
            'fake'    => 'phoundation/4.11.1', // Fake version
            'none'    => 'phoundation',        // Still gotta give something, we need to recognize the format.
        };

        // Create and return the message
        return [
            'phoundation' => $expose,
            'response'    => $this->getResponse(),
            'http_code'   => Response::getHttpCode(),
            'flash'       => $this->flash,
            'html'        => $this->html,
            'data'        => $data,
        ];
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