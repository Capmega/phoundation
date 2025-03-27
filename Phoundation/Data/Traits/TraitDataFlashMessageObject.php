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
    protected ?FlashMessageInterface $o_message = null;


    /**
     * Returns the FlashMessage object for this object
     *
     * @return FlashMessageInterface|null
     */
    public function getFlashMessageObject(): ?FlashMessageInterface
    {
        return $this->o_message;
    }


    /**
     * Sets the FlashMessage object for this object
     *
     * @param FlashMessageInterface $o_message
     *
     * @return static
     */
    public function setFlashMessageObject(FlashMessageInterface $o_message): static
    {
        $this->o_message = $o_message;
        return $this;
    }
}
