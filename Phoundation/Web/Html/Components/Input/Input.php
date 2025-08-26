<?php

/**
 * Class Input
 *
 * This class gives basic <input> functionality
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Web\Html\Components\Element;
use Phoundation\Web\Html\Components\Input\Interfaces\InputInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\ValueInterface;
use Phoundation\Web\Html\Traits\TraitBeforeAfterContent;
use Phoundation\Web\Html\Traits\TraitInputElement;


abstract class Input extends Element implements InputInterface, ValueInterface
{
    use TraitInputElement;
    use TraitBeforeAfterContent;


    /**
     * Input class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);

        $this->requires_closing_tag = false;
        $this->element              = 'input';
    }


    /**
     * Add the system arguments to the arguments list
     *
     * @note The system attributes (id, name, class, autofocus, readonly, disabled) will overwrite those same
     *       values that were added as general attributes using Element::getAttributes()->add()
     * @return IteratorInterface
     */
    protected function renderAttributesArray(): IteratorInterface
    {
        $this->o_attributes = $this->renderInputAttributes()->appendSource($this->o_attributes);
        return parent::renderAttributesArray();
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
                     ->setHidden($this->getHidden())
                     ->setPlaceholder($this->getPlaceholder())
                     ->setAutoSubmit($this->getAutoSubmit());
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
                     ->setRequired($o_definition->getRequired(false))
                     ->setHidden($o_definition->getHidden())
                     ->setAutoSubmit($o_definition->getAutoSubmit());
    }
}
