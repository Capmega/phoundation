<?php

/**
 * Class UploadButton
 *
 * This class is an extension of the Button class, and is used specifically to pre-configure upload buttons to ensure that all upload buttons look and
 * behave exactly the same
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input\Buttons;

use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Enums\EnumButtonType;
use Phoundation\Web\Html\Enums\EnumDisplayMode;


class UploadButton extends Button
{
    /**
     * UnlockButton class constructor
     *
     * @param callable|float|RenderInterface|int|string|null $content
     * @param bool                                           $make_safe
     */
    public function __construct(callable|float|RenderInterface|int|string|null $content = null, bool $make_safe = false) {
        parent::__construct($content, $make_safe);

        $this->setRequireKeysToEnable($this->getConfiguredModifierKeysToEnableUnlockButton(), 'button-upload')
             ->setName('button-upload')
             ->setButtonType(EnumButtonType::submit)
             ->setContent($content ?? tr('Unlock'), $make_safe)
             ->setOutlined($this->getConfiguredOutline())
             ->setMode($this->getConfiguredMode());
    }


    /**
     * Returns the configured modifier keys to enable this upload button
     *
     * @return array
     */
    public function getConfiguredModifierKeysToEnableUnlockButton(): array
    {
        return config()->getArray('platforms.web.html.components.buttons.upload.modifier-keys', []);
    }


    /**
     * Returns the configured value if this button should be outlined or not
     *
     * @return bool
     */
    public function getConfiguredOutline(): bool
    {
        return config()->getBoolean('platforms.web.html.components.buttons.upload.outlined', false);
    }


    /**
     * Returns the configured value if this button should be outlined or not
     *
     * @return EnumDisplayMode
     */
    public function getConfiguredMode(): EnumDisplayMode
    {
        return EnumDisplayMode::tryFrom(config()->getString('platforms.web.html.components.buttons.upload.mode', 'warning'));
    }


    /**
     * Renders and returns the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {

        return parent::render() . Script::new('document.getElementById("' . $this->getId() . '").addEventListener("click", () => {
console.log("aaaaaaaaaaaaaaaaaaaaaaaaaa");        
            window.PhoFileUploadDropZone.hiddenFileInput.click();
        });');
    }
}
