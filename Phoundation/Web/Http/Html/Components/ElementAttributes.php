<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components;

use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Data\Traits\UsesNew;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use Phoundation\Web\Http\Html\Html;
use Stringable;


/**
 * Trait ElementAttributes
 *
 * This class is an abstract HTML element object class
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
trait ElementAttributes
{
    use UsesNew;


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
     * The HTML data-* element attribute store
     *
     * @var array $data
     */
    protected array $data = [];

    /**
     * The HTML aria-* element attribute store
     *
     * @var array $aria
     */
    protected array $aria = [];

    /**
     * The HTML class element attribute cache
     *
     * @var string|null $class
     */
    protected ?string $class = null;

    /**
     * The HTML readonly attribute
     *
     * @var bool $readonly
     */
    protected bool $readonly = false;

    /**
     * The HTML disabled attribute
     *
     * @var bool $disabled
     */
    protected bool $disabled = false;

    /**
     * The tabindex for this element
     *
     * @var int|null
     */
    protected ?int $tabindex = null;

    /**
     * The HTML autofocus attribute
     *
     * @var string|null $autofocus
     */
    static protected ?string $autofocus = null;

    /**
     * Extra attributes or element content can be added through the "extra" variable
     *
     * @var string $extra
     */
    protected string $extra = '';

    /**
     * The attributes for this element
     *
     * @var array $attributes
     */
    protected array $attributes = [];

    /**
     * The element content
     *
     * @var object|string|null $content
     */
    protected object|string|null $content = null;

    /**
     * The element height
     *
     * @var int|null $height
     */
    protected ?int $height = null;

    /**
     * The element width
     *
     * @var int|null $width
     */
    protected ?int $width = null;

    /**
     * Render output storage
     *
     * @var string|null
     */
    protected ?string $render = null;

    /**
     * Right aligned elements
     *
     * @var bool $right
     */
    protected bool $right = false;


    /**
     * Sets the HTML id element attribute
     *
     * @param string|null $id
     * @param bool $name_too
     * @return static
     */
    public function setId(?string $id, bool $name_too = true): static
    {
        $this->id      = $id;
        $this->real_id = Strings::until($id, '[');

        // By default, name and id should be equal
        if ($name_too) {
            $this->setName($id, false);
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
     * @param bool $id_too
     * @return static
     */
    public function setName(?string $name, bool $id_too = true): static
    {
        $this->name      = $name;
        $this->real_name = Strings::until($name, '[');

        // By default, name and id should be equal
        if ($id_too) {
            $this->setId($name, false);
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
     * Clears the HTML class element attribute
     *
     * @return static
     */
    public function clearClasses(): static
    {
        $this->classes = [];
        return $this;
    }


    /**
     * Sets the HTML class element attribute
     *
     * @param array|string|null $classes
     * @return static
     */
    public function setClasses(array|string|null $classes): static
    {
        $this->classes = [];
        return $this->addClasses($classes);
    }


    /**
     * Sets the HTML class element attribute
     *
     * @param array|string|null $classes
     * @return static
     */
    public function addClasses(array|string|null $classes): static
    {
        foreach (Arrays::force($classes, ' ') as $class) {
            $this->addClass($class);
        }

        return $this;
    }


    /**
     * Adds a class to the HTML class element attribute
     *
     * @param string|null $class
     * @return static
     */
    public function addClass(?string $class): static
    {
        // Only add class if specified.
        if ($class) {
            $this->classes[$class] = true;
            $this->class           = null;
        }

        return $this;
    }


    /**
     * Removes the specified class for this element
     *
     * @param string $class
     * @return $this
     */
    public function removeClass(string $class): static
    {
        unset($this->classes[$class]);
        return $this;
    }


    /**
     * Adds a class to the HTML class element attribute
     *
     * @param ?string $class
     * @return static
     */
    public function setClass(?string $class): static
    {
        if ($class) {
            $this->classes = [$class => true];
            $this->class = null;
        }

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
            if ($this->classes) {
                $this->class = implode(' ', array_keys($this->classes));
            } else {
                $this->class = null;
            }
        }

        return $this->class;
    }


    /**
     * Returns if this element has the specified class or not
     *
     * @param string $class
     * @return bool
     */
    public function hasClass(string $class): bool
    {
        return isset($this->classes[$class]);
    }


    /**
     * Clears the HTML class element attribute
     *
     * @return static
     */
    public function clearData(): static
    {
        $this->data = [];
        return $this;
    }


    /**
     * Sets the HTML class element attribute
     *
     * @param array|string|null $data
     * @return static
     */
    public function setData(array|string|null $data): static
    {
        $this->data = [];
        return $this->addDatas($data);
    }


    /**
     * Sets the HTML class element attribute
     *
     * @param array|string|null $data
     * @return static
     */
    public function addDatas(array|string|null $data): static
    {
        foreach (Arrays::force($data, ' ') as $key => $value) {
            $this->addData($key, $value);
        }

        return $this;
    }


    /**
     * Adds a class to the HTML class element attribute
     *
     * @param string $key
     * @param string $value
     * @return static
     */
    public function addData(string $key, string $value): static
    {
        if ($key) {
            $this->data[$key] = $value;
        }

        return $this;
    }


    /**
     * Removes the specified class for this element
     *
     * @param string $key
     * @return $this
     */
    public function removeDataEntry(string $key): static
    {
        unset($this->data[$key]);
        return $this;
    }


    /**
     * Returns the HTML class element attribute store
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }


    /**
     * Returns if this element has the specified class or not
     *
     * @param string $key
     * @return bool
     */
    public function hasData(string $key): bool
    {
        return isset($this->data[$key]);
    }


    /**
     * Clears the HTML class element attribute
     *
     * @return static
     */
    public function clearAria(): static
    {
        $this->aria = [];
        return $this;
    }


    /**
     * Sets the HTML class element attribute
     *
     * @param array|string|null $aria
     * @return static
     */
    public function setAria(array|string|null $aria): static
    {
        $this->aria = [];
        return $this->addArias($aria);
    }


    /**
     * Sets the HTML class element attribute
     *
     * @param array|string|null $aria
     * @return static
     */
    public function addArias(array|string|null $aria): static
    {
        foreach (Arrays::force($aria, ' ') as $key => $value) {
            $this->addAria($key, $value);
        }

        return $this;
    }


    /**
     * Adds a class to the HTML class element attribute
     *
     * @param string $key
     * @param string $value
     * @return static
     */
    public function addAria(string $key, string $value): static
    {
        if ($key) {
            $this->aria[$key] = $value;
        }

        return $this;
    }


    /**
     * Removes the specified class for this element
     *
     * @param string $key
     * @return $this
     */
    public function removeAriaEntry(string $key): static
    {
        unset($this->aria[$key]);
        return $this;
    }


    /**
     * Returns the HTML class element attribute store
     *
     * @return array
     */
    public function getAria(): array
    {
        return $this->aria;
    }


    /**
     * Returns if this element has the specified class or not
     *
     * @param string $key
     * @return bool
     */
    public function hasAria(string $key): bool
    {
        return isset($this->aria[$key]);
    }


    /**
     * Set the HTML tabindex element attribute
     *
     * @param int|null $tabindex
     * @return static
     */
    public function setTabIndex(?int $tabindex): static
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
        return ($this->disabled ? null : $this->tabindex);
    }


    /**
     * Clears all the extra element attribute code
     *
     * @return static
     */
    public function clearExtra(): static
    {
        $this->extra = '';
        return $this;
    }


    /**
     * Sets all the extra element attribute code
     *
     * @param string|null $extra
     * @return static
     */
    public function setExtra(?string $extra): static
    {
        $this->extra = '';
        return $this->addExtra($extra);
    }


    /**
     * Adds more to the extra element attribute code
     *
     * @param string|null $extra
     * @return static
     */
    public function addExtra(?string $extra): static
    {
        $this->extra .= ' ' . $extra;
        return $this;
    }


    /**
     * Returns the extra element attribute code
     *
     * @return string
     */
    public function getExtra(): string
    {
        return $this->extra;
    }


    /**
     * Sets the HTML class element attribute
     *
     * @param bool $auto_focus
     * @return static
     */
    public function setAutofocus(bool $auto_focus): static
    {
        if ($auto_focus) {
            if (static::$autofocus !== null) {
                if (static::$autofocus !== $this->id) {
                    throw new OutOfBoundsException(tr('Cannot set autofocus on element ":id", its already being used by id ":already"', [
                        ':id'      => $this->id,
                        ':already' => static::$autofocus
                    ]));
                }
            }

            if (!$this->id) {
                throw new OutOfBoundsException(tr('Cannot set autofocus on element, it has no id specified yet'));
            }

            static::$autofocus = $this->id;

        } else {
            // Unset autofocus? Only if this is the element that had it in the first place!
            if (static::$autofocus !== null) {
                // Some element has auto focus, is it this one?
                if (static::$autofocus === $this->id) {
                    throw new OutOfBoundsException(tr('Cannot remove autofocus from element ":id", it does not have autofocus', [
                        ':id' => $this->id
                    ]));
                }

                static::$autofocus = null;
            }
        }


        return $this;
    }


    /**
     * Returns the HTML class element attribute
     *
     * @note Returns true if the static autofocus variable was set and is equal to the ID of this specific element
     * @return bool
     */
    public function getAutofocus(): bool
    {
        return static::$autofocus and (static::$autofocus === $this->id);
    }


    /**
     * Set the HTML disabled element attribute
     *
     * @param bool $disabled
     * @return static
     */
    public function setDisabled(bool $disabled): static
    {
        if ($disabled) {
            $this->addClass('disabled');

        } else {
            $this->removeClass('disabled');
        }

        $this->disabled = $disabled;
        return $this;
    }


    /**
     * Returns the HTML disabled element attribute
     *
     * @return bool
     */
    public function getDisabled(): bool
    {
        return $this->disabled;
    }


    /**
     * Set the HTML readonly element attribute
     *
     * @param bool $readonly
     * @return static
     */
    public function setReadonly(bool $readonly): static
    {
        if ($readonly) {
            $this->addClass('readonly');

        } else {
            $this->removeClass('readonly');
        }

        $this->readonly = $readonly;
        return $this;
    }


    /**
     * Returns the HTML readonly element attribute
     *
     * @return bool
     */
    public function getReadonly(): bool
    {
        return $this->readonly;
    }


    /**
     * Clears all HTML element attributes
     *
     * @return static
     */
    public function clearAttributes(): static
    {
        $this->attributes = [];
        return $this;
    }


    /**
     * Sets all HTML element attributes
     *
     * @param array $notifications
     * @return static
     */
    public function setAttributes(array $notifications): static
    {
        $this->attributes = [];
        return $this->addAttributes($notifications);
    }


    /**
     * Sets all HTML element attributes
     *
     * @param array $attributes
     * @return static
     */
    public function addAttributes(array $attributes): static
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
     * @param string|null $value
     * @param bool $skip_on_null
     * @return static
     */
    public function addAttribute(string $attribute, ?string $value, bool $skip_on_null = false): static
    {
        if ($value === null) {
            if ($skip_on_null) {
                return $this;
            }
        }

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
     * Sets the content of the element
     *
     * @param Stringable|string|float|int|null $content
     * @param bool $make_safe
     * @return static
     */
    public function setContent(Stringable|string|float|int|null $content, bool $make_safe = false): static
    {
        $this->content = null;
        return $this->addContent($content, $make_safe);
    }


    /**
     * Adds the specified content to the content of the element
     *
     * @param Stringable|string|float|int|null $content
     * @param bool $make_safe
     * @return static
     */
    public function addContent(Stringable|string|float|int|null $content, bool $make_safe = false): static
    {
        if (is_object($content)) {
            // This object must be able to render HTML. Check this and then render.
            static::canRenderHtml($content);
            $content   = $content->render();
            $make_safe = false;
        }

        if ($make_safe) {
            $content = Html::safe($content);
        }

        $this->content .= $content;
        return $this;
    }


    /**
     * Returns the content of the element to display
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }


    /**
     * Sets the height of the element to display
     *
     * @param int|null $height
     * @return static
     */
    public function setHeight(?int $height): static
    {
        if ($height < 0) {
            throw new OutOfBoundsException(tr('Invalid element height ":value" specified, it should be 0 or above', [
                ':value' => $height
            ]));
        }

        $this->height = $height;
        return $this;
    }


    /**
     * Returns the height of the element to display
     *
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }


    /**
     * Sets the width of the element to display
     *
     * @param int|null $width
     * @return static
     */
    public function setWidth(?int $width): static
    {
        if ($width < 0) {
            throw new OutOfBoundsException(tr('Invalid element width ":value" specified, it should be 0 or above', [
                ':value' => $width
            ]));
        }

        $this->width = $width;
        return $this;
    }


    /**
     * Returns the width of the element to display
     *
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }


    /**
     * Set if the button is right aligned or not
     *
     * @param bool $right
     * @return static
     */
    public function setRight(bool $right): static
    {
        if ($right) {
            return $this->addClass('float-right');
        }

        return $this->removeClass('float-right');
    }


    /**
     * Returns if the button is right aligned or not
     *
     * @return string
     */
    public function getRight(): string
    {
        return $this->hasClass('float-right');
    }


    /**
     * Ensures that the specified object has ElementAttributes
     *
     * @note This is just a wrapper around ElementAttributes::ensureElementAttributesTrait(). While that function
     *       explains more clearly what it does, this one says more clearly WHY and as such is the public one.
     * @param object|string $class
     * @return void
     * @see ElementAttributes::ensureElementAttributesTrait()
     */
    public static function canRenderHtml(object|string $class): void
    {
        static::ensureElementAttributesTrait($class);
    }


    /**
     * Ensures that the specified object has ElementAttributes
     *
     * @param object|string $class
     * @return void
     */
    protected static function ensureElementAttributesTrait(object|string $class): void
    {
        if (!has_trait(ElementAttributes::class, $class)) {
            if (is_object($class)) {
                $class = get_class($class);
            }

            throw new OutOfBoundsException(tr('Specified object or class ":class" is not using ElementAttributes trait and thus cannot render HTML', [
                ':class' => $class
            ]));
        }
    }
}