<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Core\Arrays;
use Phoundation\Core\Strings;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Tooltips\Enums\Interfaces\TooltipInterface;
use Phoundation\Web\Html\Components\Tooltips\Tooltip;
use Phoundation\Web\Html\Html;
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
     * The HTML element attributes store
     *
     * @var IteratorInterface $attributes
     */
    protected IteratorInterface $attributes;

    /**
     * The HTML data-* element attribute store
     *
     * @var IteratorInterface $data
     */
    protected IteratorInterface $data;

    /**
     * The HTML element aria-* attribute store
     *
     * @var IteratorInterface $aria
     */
    protected IteratorInterface $aria;

    /**
     * The HTML class element attribute store
     *
     * @var IteratorInterface $classes
     */
    protected IteratorInterface $classes;

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
     * The HTML required attribute
     *
     * @var bool $required
     */
    protected bool $required = false;

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
     * The tooltip object for this element
     *
     * @var TooltipInterface $tooltip
     */
    protected TooltipInterface $tooltip;


    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->classes    = new Iterator();
        $this->attributes = new Iterator();
    }


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
     * Returns the tooltip object for this element
     *
     * @return TooltipInterface
     */
    public function getTooltip(): TooltipInterface
    {
        if (empty($this->tooltip)) {
            $this->tooltip = new Tooltip($this);
        }

        return $this->tooltip;
    }


    /**
     * Returns the tooltip title
     *
     * @return string|null
     */
    public function getTooltipTitle(): ?string
    {
        return $this->tooltip->getTitle();
    }


    /**
     * Returns the tooltip title
     *
     * @param string|null $title
     * @return static
     */
    public function setTooltipTitle(?string $title): static
    {
        $this->getTooltip()->setTitle($title);
        return $this;
    }


    /**
     * Sets the HTML name element attribute
     *
     * @param string|null $name
     * @param bool $id_too
     * @return static
     */
    public function setName(?string $name, bool $id_too = false): static
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
     * Sets the HTML element class attribute
     *
     * @param array|string|null $classes
     * @return static
     */
    public function setClasses(array|string|null $classes): static
    {
        $this->classes = new Iterator();
        return $this->addClasses($classes);
    }


    /**
     * Adds the specified classes to the HTML element class attribute
     *
     * @param IteratorInterface|array|string|null $classes
     * @return static
     */
    public function addClasses(IteratorInterface|array|string|null $classes): static
    {
        foreach (Arrays::force($classes, ' ') as $class) {
            $this->classes->add($class);
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
        $this->classes->add($class);
        return $this;
    }


    /**
     * Adds a class to the HTML class element attribute
     *
     * @param array|string|null $classes
     * @return static
     */
    public function setClass(array|string|null $classes): static
    {
        return $this->setClasses(Arrays::force($classes, ' '));
    }


    /**
     * Returns the HTML class element attribute store
     *
     * @return IteratorInterface
     */
    public function getAttributes(): IteratorInterface
    {
        return $this->attributes;
    }


    /**
     * Sets all HTML element attributes
     *
     * @param array $attributes
     * @return static
     */
    public function setAttributes(array $attributes): static
    {
        $this->attributes = Iterator::new()->add($attributes);
        return $this;
    }


    /**
     * Sets a single HTML element attributes
     *
     * @param mixed $value
     * @param string|float|int|null $key
     * @param bool $skip_null
     * @return static
     */
    public function setAttribute(mixed $value, string|float|int|null $key = null, bool $skip_null = true): static
    {
        $this->attributes->add($value, $key, $skip_null);
        return $this;
    }


    /**
     * Returns the HTML element data-* attribute store
     *
     * @return IteratorInterface
     */
    public function getData(): IteratorInterface
    {
        if (!isset($this->data)) {
            // Lazy initialization
            $this->data = new Iterator();
        }

        return $this->data;
    }


    /**
     * Returns the HTML element aria-* attribute store
     *
     * @return IteratorInterface
     */
    public function getAria(): IteratorInterface
    {
        if (!isset($this->aria)) {
            // Lazy initialization
            $this->aria = new Iterator();
        }

        return $this->aria;
    }


    /**
     * Returns the HTML class element attribute store
     *
     * @return IteratorInterface
     */
    public function getClasses(): IteratorInterface
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
        if (empty($this->class)) {
            $this->class = implode(' ', $this->classes->getSource());
        }

        return $this->class;
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
                if (static::$autofocus !== $this->name) {
                    throw new OutOfBoundsException(tr('Cannot set autofocus on element ":name", its already being used by HTML element name ":already"', [
                        ':name'      => $this->name,
                        ':already' => static::$autofocus
                    ]));
                }
            }

            if (!$this->name) {
                throw new OutOfBoundsException(tr('Cannot set autofocus on element, it has no HTML element name specified yet'));
            }

            static::$autofocus = $this->name;

        } else {
            // Unset autofocus? Only if this is the element that had it in the first place!
            if (static::$autofocus !== null) {
                // Some element has auto focus, is it this one?
                if (static::$autofocus === $this->name) {
                    throw new OutOfBoundsException(tr('Cannot remove autofocus from element name ":name", it does not have autofocus', [
                        ':name' => $this->name
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
        return static::$autofocus and (static::$autofocus === $this->name);
    }


    /**
     * Returns the HTML required element attribute
     *
     * @return bool
     */
    public function getRequired(): bool
    {
        return $this->required;
    }


    /**
     * Set the HTML required element attribute
     *
     * @param bool $required
     * @return static
     */
    public function setRequired(bool $required): static
    {
        $this->required = $required;
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
     * Set the HTML disabled element attribute
     *
     * @param bool $disabled
     * @return static
     */
    public function setDisabled(bool $disabled): static
    {
        if ($disabled) {
            $this->classes->add('disabled');

        } else {
            $this->classes->delete('disabled');
        }

        $this->disabled = $disabled;
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
     * Set the HTML readonly element attribute
     *
     * @param bool $readonly
     * @return static
     */
    public function setReadonly(bool $readonly): static
    {
        if ($readonly) {
            $this->classes->add('readonly');

        } else {
            $this->classes->delete('readonly');
        }

        $this->readonly = $readonly;
        return $this;
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
        return $this->appendContent($content, $make_safe);
    }


    /**
     * Appends the specified content to the content of the element
     *
     * @param Stringable|string|float|int|null $content
     * @param bool $make_safe
     * @return static
     */
    public function appendContent(Stringable|string|float|int|null $content, bool $make_safe = false): static
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
     * Prepends the specified content to the content of the element
     *
     * @param Stringable|string|float|int|null $content
     * @param bool $make_safe
     * @return static
     */
    public function prependContent(Stringable|string|float|int|null $content, bool $make_safe = false): static
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

        $this->content = $content . $this->content;
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
    public function setFloatRight(bool $right): static
    {
        if ($right) {
            $this->classes->add('float-right');
        } else {
            $this->classes->delete('float-right');
        }

        return $this;
    }


    /**
     * Returns if the button is right aligned or not
     *
     * @return bool
     */
    public function getFloatRight(): bool
    {
        return $this->getClasses()->exists('float-right');
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
