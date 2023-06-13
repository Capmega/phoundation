<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Interfaces;

use Phoundation\Data\DataEntry\Definitions\Definitions;


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
     * @return Definitions
     */
    public function getFieldDefinitions(): Definitions;

    /**
     * Set the data source for this DataEntryForm
     *
     * @param Definitions $fields
     * @return static
     */
    public function setFieldDefinitions(Definitions $fields): static;

    /**
     * Returns the data source for this DataEntryForm
     *
     * @return array
     */
    public function getKeysDisplay(): array;

    /**
     * Set the data source for this DataEntryForm
     *
     * @param array $keys_display
     * @return static
     */
    public function setKeysDisplay(array $keys_display): static;
}