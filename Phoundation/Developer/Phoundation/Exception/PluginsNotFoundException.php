<?php

/**
 * Class PluginsNotFoundException
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Developer
 */


declare(strict_types=1);

namespace Phoundation\Developer\Phoundation\Exception;

use Phoundation\Developer\Exception\DeveloperException;
use Throwable;


class PluginsNotFoundException extends DeveloperException
{
    /**
     * PluginsNotFoundException class constructor
     *
     * @param Throwable|array|string|null $messages
     * @param mixed|null                  $data
     * @param string|null                 $code
     * @param Throwable|null              $previous
     */
    public function __construct(Throwable|array|string|null $messages = null, mixed $data = null, ?string $code = null, ?Throwable $previous = null)
    {
        if (!$messages) {
            $messages = tr('Failed to find a Phoundation plugins directory');
        }
        parent::__construct($messages, $data, $code, $previous);
    }
}
