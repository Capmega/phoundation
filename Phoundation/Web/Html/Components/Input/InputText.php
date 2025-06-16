<?php

/**
 * Class InputText
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumInputType;


class InputText extends Input implements InputTextInterface
{
    /**
     * InputText class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->input_type = $this->input_type ?? EnumInputType::text;
        parent::__construct($content);
    }


    /**
     * Returns the minimum length this text input
     *
     * @return int|null
     */
    public function getMinLength(): ?int
    {
        return $this->o_attributes->get('minlength', false);
    }


    /**
     * Returns the minimum length this text input
     *
     * @param int|null $minlength
     *
     * @return static
     */
    public function setMinLength(?int $minlength): static
    {
        return $this->setAttribute($minlength, 'minlength');
    }


    /**
     * Returns the maximum length this text input
     *
     * @return int|null
     */
    public function getMaxLength(): ?int
    {
        return $this->o_attributes->get('maxlength', false);
    }


    /**
     * Returns the maximum length this text input
     *
     * @param int|null $maxlength
     *
     * @return static
     */
    public function setMaxLength(?int $maxlength): static
    {
        return $this->setAttribute($maxlength, 'maxlength');
    }


    /**
     * Returns the auto complete setting
     *
     * @return bool
     */
    public function getAutoComplete(): bool
    {
        return Strings::toBoolean($this->o_attributes->get('autocomplete', false));
    }


    /**
     * Sets the auto complete setting
     *
     * @param bool $auto_complete
     *
     * @return static
     */
    public function setAutoComplete(bool $auto_complete): static
    {
        return $this->setAttribute($auto_complete ? 'on' : 'off', 'autocomplete');
    }


    /**
     * Returns placeholder text
     *
     * @return string|null
     */
    public function getPlaceholder(): ?string
    {
        return $this->o_attributes->get('placeholder', false);
    }


    /**
     * Sets placeholder text
     *
     * @param string|null $placeholder
     *
     * @return static
     */
    public function setPlaceholder(?string $placeholder): static
    {
        return $this->setAttribute($placeholder, 'placeholder');
    }


    /**
     * Returns input_mask for this input control
     *
     * @return string|null
     */
    public function getInputMask(): ?string
    {
        return $this->o_attributes->get('input_mask', false);
    }


    /**
     * Sets input_mask for this input control
     *
     * @param string|null $mask
     *
     * @return static
     */
    public function setInputMask(?string $mask): static
    {
        return $this->setAttribute($mask, 'input_mask');
    }


    /**
     * Returns the DataEntry Definition on this element
     *
     * If no Definition object was set, one will be created using the data in this object
     *
     * @return DefinitionInterface|null
     */
    public function getDefinitionObject(): ?DefinitionInterface
    {
        // Copy data used for input controls
        return parent::getDefinitionObject()
                     ->setClearButton($this->getClearButton())
                     ->setInputMask($this->getInputMask());
    }


    /**
     * Set the DataEntry Definition on this element
     *
     * @param DefinitionInterface|null $o_definition
     *
     * @return static
     */
    public function setDefinitionObject(?DefinitionInterface $o_definition): static
    {
        // Copy data used for input controls
        return parent::setDefinitionObject($o_definition)
                     ->setClearButton($o_definition->getClearButton())
                     ->setInputMask($o_definition->getInputMask());
    }
}
