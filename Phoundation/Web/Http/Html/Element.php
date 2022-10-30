<?php

namespace Phoundation\Web\Http\Html;



use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;

/**
 * Class Element
 *
 * This class is an abstract HTML element object class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Element
{
    /**
     * The element type
     *
     * @var string $type
     */
    protected string $type;

    /**
     * The HTML id element attribute
     *
     * @var string|null $id
     */
    protected ?string $id = null;

    /**
     * The real HTML id element attribute. If id contains "element[]", this will contain "element"
     *
     * @var string|null $real_id
     */
    protected ?string $real_id = null;

    /**
     * The HTML name element attribute
     *
     * @var string|null $name
     */
    protected ?string $name = null;

    /**
     * The real HTML name element attribute. If name contains "element[]", this will contain "element"
     *
     * @var string|null $real_name
     */
    protected ?string $real_name = null;

    /**
     * The HTML class element attribute store
     *
     * @var array $classes
     */
    protected array $classes = [];

    /**
     * The HTML class element attribute cache
     *
     * @var string|null $class
     */
    protected ?string $class = null;

    /**
     * The HTML tabindex element attribute
     *
     * @var int|null $tabindex
     */
    protected ?int $tabindex = null;

    /**
     * The HTML readonly attribute
     *
     * @var string|null $readonly
     */
    protected ?string $readonly = null;

    /**
     * The HTML disabled attribute
     *
     * @var string|null $disabled
     */
    protected ?string $disabled = null;

    /**
     * The HTML autofocus attribute
     *
     * @var string|null $autofocus
     */
    protected ?string $autofocus = null;

    /**
     * The attributes for this element
     *
     * @var array $attributes
     */
    protected array $attributes = [];



    /**
     * HtmlObject constructor
     */
    public function __construct(string $type)
    {
        $this->type     = $type;
        $this->tabindex = Html::getTabIndex();
    }



    /**
     * Sets the HTML id element attribute
     *
     * @param string|null $id
     * @return Element
     */
    public function setId(?string $id): self
    {
        $this->id      = $id;
        $this->real_id = Strings::until($id, '[');

        // By default, name and id should be equal
        if (empty($this->name)) {
            $this->setName($id);
        }

        return $this;
    }



    /**
     * Returns the HTML id element attribute
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }



    /**
     * Sets the HTML name element attribute
     *
     * @param string|null $name
     * @return Element
     */
    public function setName(?string $name): self
    {
        $this->name      = $name;
        $this->real_name = Strings::until($name, '[');

        // By default, name and id should be equal
        if (empty($this->id)) {
            $this->setId($name);
        }

        return $this;
    }



    /**
     * Returns the HTML name element attribute
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }



    /**
     * Sets the HTML class element attribute
     *
     * @param array|string|null $classes
     * @return Element
     */
    public function setClasses(array|string|null $classes): self
    {
        $this->classes = [];
        return $this->addClasses($classes);
    }



    /**
     * Sets the HTML class element attribute
     *
     * @param string|null $classes
     * @return Element
     */
    public function addClasses(?string $classes): self
    {
        foreach (Arrays::force($classes, ' ') as $class) {
            $this->addClass($class);
        }

        return $this;
    }



    /**
     * Adds an class to the HTML class element attribute
     *
     * @param string $class
     * @return Element
     */
    public function addClass(string $class): self
    {
        $this->classes[$class] = true;
        $this->class = null;
        return $this;
    }



    /**
     * Returns the HTML class element attribute store
     *
     * @return array
     */
    public function getClasses(): array
    {
        return $this->classes;
    }



    /**
     * Returns the HTML class element attribute
     *
     * @return string|null
     */
    public function getClass(): ?string
    {
        if (!$this->class) {
            $this->class = implode(' ', $this->classes);
        }

        return $this->class;
    }



    /**
     * Sets the HTML class element attribute
     *
     * @param bool $autofocus
     * @return Element
     */
    public function setAutofocus(bool $autofocus): self
    {
        $this->autofocus = ($autofocus ? 'autofocus' : null);
        return $this;
    }



    /**
     * Returns the HTML class element attribute
     *
     * @return bool
     */
    public function getAutofocus(): bool
    {
        return (bool) $this->autofocus;
    }



    /**
     * Set the HTML tabindex element attribute
     *
     * @param int|null $tabindex
     * @return Element
     */
    public function setTabIndex(?int $tabindex): self
    {
        $this->tabindex = $tabindex;
        return $this;
    }


    /**
     * Returns the HTML tabindex element attribute
     *
     * @return int|null
     */
    public function getTabIndex(): ?int
    {
        return $this->tabindex;
    }



    /**
     * Set the HTML disabled element attribute
     *
     * @param bool $disabled
     * @return Element
     */
    public function setDisabled(bool $disabled): self
    {
        $this->tabindex = ($disabled ? 'disabled' : null);
        return $this;
    }


    /**
     * Returns the HTML disabled element attribute
     *
     * @return bool
     */
    public function getDisabled(): bool
    {
        return (bool) $this->disabled;
    }



    /**
     * Set the HTML readonly element attribute
     *
     * @param bool $readonly
     * @return Element
     */
    public function setReadonly(bool $readonly): self
    {
        $this->readonly = ($readonly ? 'readonly' : null);
        return $this;
    }


    /**
     * Returns the HTML readonly element attribute
     *
     * @return bool
     */
    public function getReadonly(): bool
    {
        return $this->tabindex;
    }



    /**
     * Sets all HTML element attributes
     *
     * @param array $attributes
     * @return Element
     */
    public function setAttributes(array $attributes): self
    {
        $this->attributes = [];
        $this->addAttributes($attributes);
        return $this;
    }



    /**
     * Sets all HTML element attributes
     *
     * @param array $attributes
     * @return Element
     */
    public function addAttributes(array $attributes): self
    {
        foreach ($attributes as $attribute => $value) {
            $this->addAttribute($attribute, $value);
        }

        return $this;
    }



    /**
     * Sets all HTML element attributes
     *
     * @param string $attribute
     * @param string $value
     * @return Element
     */
    public function addAttribute(string $attribute, string $value): self
    {
        $this->attributes[$attribute] = $value;
        return $this;
    }



    /**
     * Returns all HTML element attributes
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }



    /**
     * Generates and returns the HTML string
     *
     * @return string
     */
    public function render(): string
    {
        $attributes = $this->buildAttributes();
        $attributes = Arrays::implodeWithKeys($attributes, ' ', '=', '"');

        return '<' . $this->type. ' ' . $attributes . '>';
    }



    /**
     * Builds and returns the class string
     *
     * @return string|null
     */
    protected function buildClassString(): ?string
    {
        $class = $this->getClass();

        if ($class) {
            return ' class="' . $class . '"';
        }

        return null;
    }



    /**
     * Add the system arguments to the arguments list
     *
     * @note The system attributes (id, name, class, tabindex, autofocus, readonly, disabled) will overwrite those same
     *       values that were added as general attributes using Element::addAttribute()
     * @return array
     */
    protected function buildAttributes(): array
    {
        return array_merge($this->attributes, [
            'id'        => $this->id,
            'name'      => $this->name,
            'class'     => implode(' ', array_keys($this->classes)),
            'tabindex'  => $this->tabindex,
            'autofocus' => $this->autofocus,
            'readonly'  => $this->readonly,
            'disabled'  => $this->disabled,
        ]);
    }
}