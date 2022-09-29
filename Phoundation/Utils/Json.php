<?php

namespace Phoundation\Utils;

use Exception;
use JsonException;
use Phoundation\Core\CoreException\CoreException;

/**
 * Class Json
 *
 * This class contains various JSON functions
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Utils
 */
class Json
{
    /**
     * Send correct JSON reply
     */
    static function reply($data = null, $result = 'OK', $http_code = null, $after = 'die')
    {
        global $core;

        try {
            if (!$data) {
                $data = Arrays::force($data);
            }

            /*
             * Auto assume result = "OK" entry if not specified
             */
            if (empty($data['data'])) {
                $data = ['data' => $data];
            }

            if ($result) {
                if (isset($data['result'])) {
                    throw new JsonException(tr('Json::reply(): Result was specified both in the data array as ":result1" as wel as the separate variable as ":result2"', array(':result1' => $data['result'], ':result2' => $result)), 'invalid');
                }

                /*
                 * Add result to the reply
                 */
                $data['result'] = $result;
            }

            /*
             * Send a new CSRF code with this payload?
             */
            if (!empty($core->register['csrf_ajax'])) {
                $data['csrf'] = $core->register['csrf_ajax'];
                unset($core->register['csrf_ajax']);
            }

            $data['result'] = strtoupper($data['result']);
            $data = Json::encode($data);

            $params = [
                'http_code' => $http_code,
                'mimetype' => 'application/json'
            ];

            http_headers($params, strlen($data));

            echo $data;

            switch ($after) {
                case 'die':
                    /*
                     * We're done, kill the connection % process (default)
                     */
                    die();

                case 'continue':
                    /*
                     * Continue running
                     */
                    return;

                case 'close_continue':
                    /*
                     * Close the current HTTP connection but continue in the background
                     */
                    session_write_close();
                    fastcgi_finish_request();
                    return;

                default:
                    throw new JsonException(tr('Json::reply(): Unknown after ":after" specified. Use one of "die", "continue", or "close_continue"', array(':after' => $after)), 'unknown');
            }

        } catch (Exception $e) {
            throw new JsonException('Json::reply(): Failed', $e);
        }
    }


    /**
     * Send JSON error to client
     *
     * @param string $message
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
     *
     */
    static public function error($message, $data = null, $result = null, $http_code = 500)
    {
        global $_CONFIG;

        try {
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
                    if ($_CONFIG['production']) {
                        $message = $default;
                        log_console('Json::error(): No exception object specified for following error', 'yellow');
                        log_console($message, 'yellow');

                    } else {
                        if (count($message) == 1) {
                            $message = array_pop($message);
                        }
                    }

                } else {
                    if ($_CONFIG['production']) {
                        log_console($message['e']);

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
                /*
                 * Assume this is an CoreException object
                 */
                if (!($message instanceof CoreException)) {
                    if (!($message instanceof Exception)) {
                        $type = gettype($message);

                        if ($type === 'object') {
                            $type .= '/' . get_class($message);
                        }

                        throw new JsonException(tr('Json::error(): Specified message must either be a string or an CoreException ojbect, or PHP Exception ojbect, but is a ":type"', array(':type' => $type)), 'invalid');
                    }

                    $code = $message->getCode();

                    if (debug()) {
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
                        if (debug()) {
                            /*
                             * This is a user visible message
                             */
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

        } catch (Exception $e) {
            throw new JsonException('Json::error(): Failed', $e);
        }
    }


    /**
     *
     */
    static public function message($code, $data = null)
    {
        global $_CONFIG;

        try {
            if (is_object($code)) {
                /*
                 * This is (presumably) an exception
                 */
                $code = $code->getRealCode();
            }

            if (str_contains($code, '_')) {
                /*
                 * Codes should always use -, never _
                 */
                notify(new JsonException(tr('Json::message(): Specified code ":code" contains an _ which should never be used, always use a -', array(':code' => $code)), 'warning/invalid'));
            }

            switch ($code) {
                case 301:
                    // FALLTHROUGH
                case 'redirect':
                    Json::error(null, array('location' => $data), 'REDIRECT', 301);

                case 302:
                    Json::error(null, array('location' => domain($_CONFIG['redirects']['signin'])), 'REDIRECT', 302);

                case 'signin':
                    Json::error(null, array('location' => domain($_CONFIG['redirects']['signin'])), 'SIGNIN', 302);

                case 400:
                    // FALLTHROUGH
                case 'invalid':
                    // FALLTHROUGH
                case 'validation':
                    Json::error(null, $data, 'BAD-REQUEST', 400);

                case 'locked':
                    Json::error(null, $data, 'LOCKED', 403);

                case 403:
                    // FALLTHROUGH
                case 'forbidden':
                    // FALLTHROUGH
                case 'access-denied':
                    Json::error(null, $data, 'FORBIDDEN', 403);

                case 404:
                    // FALLTHROUGH
                case 'not-found':
                    Json::error(null, $data, 'NOT-FOUND', 404);

                case 'not-exists':
                    Json::error(null, $data, 'NOT-EXISTS', 404);

                case 405:
                    // FALLTHROUGH
                case 'method-not-allowed':
                    Json::error(null, $data, 'METHOD-NOT-ALLOWED', 405);

                case 406:
                    // FALLTHROUGH
                case 'not-acceptable':
                    Json::error(null, $data, 'NOT-ACCEPTABLE', 406);

                case 408:
                    // FALLTHROUGH
                case 'timeout':
                    Json::error(null, $data, 'TIMEOUT', 408);

                case 409:
                    // FALLTHROUGH
                case 'conflict':
                    Json::error(null, $data, 'CONFLICT', 409);

                case 412:
                    // FALLTHROUGH
                case 'expectation-failed':
                    Json::error(null, $data, 'EXPECTATION-FAILED', 412);

                case 418:
                    // FALLTHROUGH
                case 'im-a-teapot':
                    Json::error(null, $data, 'IM-A-TEAPOT', 418);

                case 429:
                    // FALLTHROUGH
                case 'too-many-requests':
                    Json::error(null, $data, 'TOO-MANY-REQUESTS', 429);

                case 451:
                    // FALLTHROUGH
                case 'unavailable-for-legal-reasons':
                    Json::error(null, $data, 'UNAVAILABLE-FOR-LEGAL-REASONS', 451);

                case 500:
                    // FALLTHROUGH
                case 'error':
                    Json::error(null, $data, 'ERROR', 500);

                case 503:
                    // FALLTHROUGH
                case 'maintenance':
                    // FALLTHROUGH
                case 'service-unavailable':
                    Json::error(null, null, 'SERVICE-UNAVAILABLE', 503);

                case 504:
                    // FALLTHROUGH
                case 'gateway-timeout':
                    Json::error(null, null, 'GATEWAY-TIMEOUT', 504);

                case 'reload':
                    Json::reply(null, 'RELOAD');

                default:
                    notify(array('code' => 'unknown',
                        'groups' => 'developers',
                        'title' => tr('Unknown message specified'),
                        'message' => tr('Json::message(): Unknown code ":code" specified', array(':code' => $code))));

                    Json::error(null, (debug() ? $data : null), 'ERROR', 500);
            }

        } catch (Exception $e) {
            throw new JsonException('Json::message(): Failed', $e);
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
    static public function encode(mixed $source, int $options = 0, int $depth = 512): string
    {
        $return = json_encode($source, $options, $depth);

        if (json_last_error()) {
            throw new JsonException(tr('JSON encoding failed with :error', [':error' => json_last_error_msg()]), 'error');
        }

        return $return;
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
    static public function decode(?string $source, int $options = 0, int $depth = 512, bool $as_array = true): mixed
    {
        if ($source === null) {
            return null;
        }

        $return = json_decode($source, $as_array, $depth, $options);

        if (json_last_error()) {
            throw new JsonException(tr('JSON decoding failed with :error', [':error' => json_last_error_msg()]), 'error');
        }

        return $return;
    }
}