<?php

/**
 * Trait TraitDataFlashMessageObject
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces\FlashMessageInterface;


trait TraitDataFlashMessageObject
{
    /**
     * Tracks the FlashMessage object for this object
     *
     * @var FlashMessageInterface|null
     */
    protected ?FlashMessageInterface $_message = null;


    /**
     * Returns the FlashMessage object for this object
     *
     * @return FlashMessageInterface|null
     */
    public function getFlashMessageObject(): ?FlashMessageInterface
    {
        return $this->_message;
    }


    /**
     * Sets the FlashMessage object for this object
     *
     * @param FlashMessageInterface $_message
     *
     * @return static
     */
    public function setFlashMessageObject(FlashMessageInterface $_message): static
    {
        $this->_message = $_message;
        return $this;
    }
}
