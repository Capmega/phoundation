<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Web\Http\Html\Components\Interfaces\DataEntryFormInterface;


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
class DataEntryForm extends ElementsBlock implements DataEntryFormInterface
{
    /**
     * The key metadata for the specified data
     *
     * @var Definitions $definitions
     */
    protected Definitions $definitions;

    /**
     * The form specific metadata for the keys for the specified data
     *
     * @var array $keys_display
     */
    protected array $keys_display;

    /**
     * Optional class for input elements
     *
     * @var string $input_class
     */
    protected string $input_class;

    /**
     * Supported input element types
     *
     * @var array[] $supported_input
     */
    protected static array $supported_input = [
        'button',
        'checkbox',
        'color',
        'date',
        'datetime-local',
        'email',
        'file',
        'hidden',
        'image',
        'month',
        'number',
        'password',
        'radio',
        'range',
        'reset',
        'search',
        'submit',
        'tel',
        'text',
        'time',
        'url',
        'week'
    ];


    /**
     * Returns true if the specified input type is supported
     *
     * @param string $input
     * @return bool
     */
    public function inputTypeSupported(string $input): bool
    {
        return in_array($input, self::$supported_input);
    }


    /**
     * Returns the optional class for input elements
     *
     * @return string
     */
    public function getInputClass(): string
    {
        return $this->input_class;
    }


    /**
     * Sets the optional class for input elements
     *
     * @param string $input_class
     * @return static
     */
    public function setInputClass(string $input_class): static
    {
        $this->input_class = $input_class;
        return $this;
    }


    /**
     * Returns the data fields for this DataEntryForm
     *
     * @return Definitions
     */
    public function getDefinitions(): Definitions
    {
        return $this->definitions;
    }


    /**
     * Set the data source for this DataEntryForm
     *
     * @param Definitions $definitions
     * @return static
     */
    public function setDefinitions(Definitions $definitions): static
    {
        $this->definitions = $definitions;
        return $this;
    }


    /**
     * Returns the data source for this DataEntryForm
     *
     * @return array
     */
    public function getKeysDisplay(): array
    {
        return $this->keys_display;
    }


    /**
     * Set the data source for this DataEntryForm
     *
     * @param array $keys_display
     * @return static
     */
    public function setKeysDisplay(array $keys_display): static
    {
        $this->keys_display = $keys_display;
        return $this;
    }
}