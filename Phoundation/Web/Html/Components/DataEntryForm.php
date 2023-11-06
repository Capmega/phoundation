<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Data\DataEntry\Definitions\Definitions;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Web\Html\Components\Interfaces\DataEntryFormInterface;


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
     * @var Definitions|null $definitions
     */
    protected ?Definitions $definitions = null;

    /**
     * Optional class for input elements
     *
     * @var string $input_class
     */
    protected string $input_class;

    /**
     * If set, the screen focus will automatically go to the specified element
     *
     * @var string|null $auto_focus_id
     */
    protected ?string $auto_focus_id = null;

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
        'week',
        'auto-suggest'
    ];


    /**
     * Returns if meta information is visible at all, or not
     *
     * @return bool
     */
    public function getMetaVisible(): bool
    {
        return $this->definitions->getMetaVisible();
    }


    /**
     * Sets if meta information is visible at all, or not
     *
     * @param bool $meta_visible
     * @return static
     */
    public function setMetaVisible(bool $meta_visible): static
    {
        $this->definitions->setMetaVisible($meta_visible);
        return $this;
    }


    /**
     * Returns the element that will receive autofocus
     *
     * @return string|null
     */
    public function getAutoFocusId(): ?string
    {
        return $this->auto_focus_id;
    }


    /**
     * Sets the element that will receive autofocus
     *
     * @param string|null $auto_focus_id
     * @return $this
     */
    public function setAutoFocusId(?string $auto_focus_id): static
    {
        $this->auto_focus_id = $auto_focus_id;
        return $this;
    }


    /**
     * Returns true if the specified input type is supported
     *
     * @param string $input
     * @return bool
     */
    public function inputTypeSupported(string $input): bool
    {
        return in_array($input, static::$supported_input);
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
     * @return DefinitionsInterface|null
     */
    public function getDefinitions(): ?DefinitionsInterface
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
}
