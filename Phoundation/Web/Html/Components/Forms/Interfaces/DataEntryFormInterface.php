<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Forms\Interfaces;

use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;

interface DataEntryFormInterface extends ElementsBlockInterface
{
    /**
     * Returns the element that will receive autofocus
     *
     * @return string|null
     */
    public function getAutoFocusId(): ?string;


    /**
     * Sets the element that will receive autofocus
     *
     * @param string|null $auto_focus_id
     *
     * @return $this
     */
    public function setAutoFocusId(?string $auto_focus_id): static;


    /**
     * Returns the optional class for input elements
     *
     * @return string
     */
    public function getInputClass(): string;


    /**
     * Sets the optional class for input elements
     *
     * @param string $input_class
     *
     * @return static
     */
    public function setInputClass(string $input_class): static;


    /**
     * Returns the data fields for this DataEntryForm
     *
     * @return DefinitionsInterface|null
     */
    public function getDefinitionsObject(): ?DefinitionsInterface;


    /**
     * Set the data source for this DataEntryForm
     *
     * @param Definitions $definitions
     *
     * @return static
     */
    public function setDefinitionsObject(Definitions $definitions): static;
}