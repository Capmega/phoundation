<?php

declare(strict_types=1);

namespace Phoundation\Web\Http;

use JetBrains\PhpStorm\ExpectedValues;


/**
 * Class Flash
 *
 * This class contains methods to manage HTML flash messages
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Flash
{
    /**
     * The messages store
     *
     * @var array $messages
     */
    protected array $messages = [];


    /**
     * Add a new flash message for the current user
     *
     * @param string $message
     * @param string $type
     * @return void
     */
    public function add(string $message, #[ExpectedValues(values: ['info', 'information', 'success', 'notice', 'warning', 'error', 'exception'])] string $type): void
    {
        $this->messages[] = [
            'type'    => $type,
            'message' => $message
        ];
    }
}