<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;

use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;


/**
 * Class DataEntryForm
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Web
 */
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
     * @return $this
     */
    public function setAutoFocusId(?string $auto_focus_id): static;

    /**
     * Returns true if the specified input type is supported
     *
     * @param string $input
     * @return bool
     */
    public function inputTypeSupported(string $input): bool;

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
     * @return static
     */
    public function setInputClass(string $input_class): static;

    /**
     * Returns the data fields for this DataEntryForm
     *
     * @return DefinitionsInterface|null
     */
    public function getDefinitions(): ?DefinitionsInterface;

    /**
     * Set the data source for this DataEntryForm
     *
     * @param Definitions $definitions
     * @return static
     */
    public function setDefinitions(Definitions $definitions): static;
}