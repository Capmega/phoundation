<?php

/**
 * Class RestrictionsException
 *
 * This is the standard exception thrown by the Restrictions class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests\Restrictions\Exception;

use Phoundation\Data\Validator\CookieValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Requests\Exception\RequestException;
use Throwable;


class RequestMethodRestrictionsException extends RequestException
{
    /**
     * RequestMethodRestrictionsException constructor
     *
     * @param Throwable|array|string|null $messages The exception messages
     * @param Throwable|null              $previous A previous exception, if available.
     */
    public function __construct(Throwable|array|string|null $messages, ?Throwable $previous = null)
    {
        parent::__construct($messages, $previous);

        $this->setData([
            'get'     => GetValidator::getBackup(),
            'post'    => PostValidator::getBackup(),
            'cookies' => CookieValidator::getBackup(),
            'session' => $_SESSION,
            'server'  => $_SERVER
        ]);
    }
}
