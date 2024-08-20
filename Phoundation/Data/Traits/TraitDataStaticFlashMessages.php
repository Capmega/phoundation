<?php

/**
 * Trait TraitDataFlashMessages
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opentable.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Html\Components\Widgets\FlashMessages\FlashMessages;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces\FlashMessagesInterface;

trait TraitDataStaticFlashMessages
{
    /**
     * Flash messages source object
     *
     * @var FlashMessagesInterface|null
     */
    protected static ?FlashMessagesInterface $flash_messages = null;


    /**
     * Returns the page flash messages
     *
     * @return FlashMessagesInterface
     */
    public static function getFlashMessagesObject(): FlashMessagesInterface
    {
        if (!static::$flash_messages) {
            static::$flash_messages = FlashMessages::new();
        }

        return static::$flash_messages;
    }
}
