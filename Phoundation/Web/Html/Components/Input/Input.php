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
    protected function renderAttributesIteratorObject(): IteratorInterface
    {
        $this->_attributes = $this->renderInputAttributes()->appendSource($this->_attributes);
        return parent::renderAttributesIteratorObject();
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
     * @param DefinitionInterface|null $_definition
     *
     * @return static
     */
    public function setDefinitionObject(?DefinitionInterface $_definition): static
    {
        // Copy data used for input controls
        return parent::setDefinitionObject($_definition)
                     ->setRequired($_definition->getRequired(false))
                     ->setHidden($_definition->getHidden())
                     ->setAutoSubmit($_definition->getAutoSubmit());
    }
}
