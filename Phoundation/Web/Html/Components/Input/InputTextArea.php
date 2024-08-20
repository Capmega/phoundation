<?php

/**
 * Class InputTextArea
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Utils\Strings;


class InputTextArea extends Input
{
    /**
     * The number of columns to use for this text area
     *
     * @var int|null $cols
     */
    protected ?int $cols = null;

    /**
     * The number of rows to use for this text area
     *
     * @var int|null $cols
     */
    protected ?int $rows = null;


    /**
     * Form class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        $this->requires_closing_tag = true;
        $this->element              = 'textarea';
        $this->input_type           = null;
    }


    /**
     * Returns the number of cols to use for this text area
     *
     * @return int|null
     */
    public function getCols(): ?int
    {
        return $this->cols;
    }


    /**
     * Sets the number of cols to use for this text area
     *
     * @param int|null $cols
     *
     * @return static
     */
    public function setCols(?int $cols): static
    {
        $this->cols = $cols;

        return $this;
    }


    /**
     * Returns the number of rows to use for this text area
     *
     * @return int|null
     */
    public function getRows(): ?int
    {
        return $this->rows;
    }


    /**
     * Sets the number of rows to use for this text area
     *
     * @param int|null $rows
     *
     * @return static
     */
    public function setRows(?int $rows): static
    {
        $this->rows = $rows;

        return $this;
    }


    /**
     * Returns the minimum length this text area
     *
     * @return int|null
     */
    public function getMinLength(): ?int
    {
        return $this->attributes->get('minlength', false);
    }


    /**
     * Sets the minimum length this text area
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
     * Returns the maximum length this text area
     *
     * @return int|null
     */
    public function getMaxLength(): ?int
    {
        return $this->attributes->get('maxlength', false);
    }


    /**
     * Sets the maximum length this text area
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
        return Strings::toBoolean($this->attributes->get('autocomplete', false));
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
     * Add the system arguments to the arguments list
     *
     * @note The system attributes (id, name, class, autofocus, readonly, disabled) will overwrite those same
     *       values that were added as general attributes using Element::getAttributes()->add() or
     *       Element::getAttributes()->set()
     * @return IteratorInterface
     */
    protected function renderAttributes(): IteratorInterface
    {
        $return = [
            'cols' => $this->cols,
            'rows' => $this->rows,
        ];

        // Merge the system values over the set attributes
        return parent::renderAttributes()
                     ->appendSource($this->attributes, $return);
    }
}
