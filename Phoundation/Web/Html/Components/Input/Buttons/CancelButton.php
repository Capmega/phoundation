<?php

/**
 * Class CancelButton
 *
 * This class is an extension of the Button class, and is used specifically to pre-configure cancel buttons to ensure that all cancel buttons look and behave
 * exactly the same
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Buttons;

use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Enums\EnumDisplayMode;


class CancelButton extends Button
{
    /**
     * CancelButton class constructor
     *
     * @param callable|float|RenderInterface|int|string|null $content
     * @param bool                                           $make_safe
     */
    public function __construct(callable|float|RenderInterface|int|string|null $content = null, bool $make_safe = false) {
        parent::__construct($content, $make_safe);

        $this->setRequireKeysToEnable($this->getConfiguredModifierKeysToEnableCancelButton(), 'button-lock')
             ->setButtonType(EnumButtonType::submit)
             ->setContent($content ?? tr('Cancel'), $make_safe)
             ->setOutlined($this->getConfiguredOutline())
             ->setMode($this->getConfiguredMode())
             ->setFloatRight(true);
    }


    /**
     * Returns the configured modifier keys to enable this cancel button
     *
     * @return array
     */
    public function getConfiguredModifierKeysToEnableCancelButton(): array
    {
        return config()->getArray('web.html.components.buttons.cancel.modifier-keys', ['ctrl', 'alt']);
    }


    /**
     * Returns the configured value if this button should be outlined or not
     *
     * @return bool
     */
    public function getConfiguredOutline(): bool
    {
        return config()->getBoolean('web.html.components.buttons.cancel.outlined', true);
    }


    /**
     * Returns the configured value if this button should be outlined or not
     *
     * @return EnumDisplayMode
     */
    public function getConfiguredMode(): EnumDisplayMode
    {
        return EnumDisplayMode::tryFrom(config()->getString('web.html.components.buttons.cancel.mode', 'warning'));
    }
}
