<?php

/**
 * Trait ElementAttributes
 *
 * This class is an abstract HTML element object class
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataDefinition;
use Phoundation\Data\Traits\TraitDataDisabled;
use Phoundation\Data\Traits\TraitDataProperties;
use Phoundation\Data\Traits\TraitDataReadonly;
use Phoundation\Data\Traits\TraitDataScripts;
use Phoundation\Data\Traits\TraitMethodHasRendered;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Seo;
use Phoundation\Utils\Strings;
use Phoundation\Utils\Utils;
use Phoundation\Web\Html\Components\A;
use Phoundation\Web\Html\Components\Div;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Components\Interfaces\AInterface;
use Phoundation\Web\Html\Components\Interfaces\DivInterface;
use Phoundation\Web\Html\Components\Span;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Interfaces\TooltipInterface;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Tooltip;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Http\Interfaces\UrlInterface;
use Stringable;


trait TraitElementAttributes
{
    use TraitMethodHasRendered;
    use TraitDataProperties;
    use TraitBeforeAfterContent;
    use TraitDataDefinition {
        setDefinitionObject as protected __setDefinitionObject;
        getDefinitionObject as protected __getDefinitionObject;
    }
    use TraitDataScripts;
    use TraitDataReadonly;
    use TraitDataDisabled;


    /**
     * The HTML autofocus attribute
     *
     * @var string|null $autofocus
     */
    static protected ?string $autofocus = null;

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
     * @var IteratorInterface $o_attributes
     */
    protected IteratorInterface $o_attributes;

    /**
     * The HTML data-* element attribute store
     *
     * @var IteratorInterface $o_data
     */
    protected IteratorInterface $o_data;

    /**
     * The HTML element aria-* attribute store
     *
     * @var IteratorInterface $o_aria
     */
    protected IteratorInterface $o_aria;

    /**
     * The HTML class element attribute store
     *
     * @var IteratorInterface $o_classes
     */
    protected IteratorInterface $o_classes;

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
     * The HTML visible attribute
     *
     * @var bool $visible
     */
    protected bool $visible = false;

    /**
     * The HTML display class (d-none)
     *
     * @var bool $display
     */
    protected bool $display = false;

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
     * Extra attributes or element content can be added through the "extra" variable
     *
     * @var string $extra_attributes
     */
    protected string $extra_attributes = '';

    /**
     * The element content
     *
     * @var string|null $content
     */
    protected ?string $content = null;

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
     * Right aligned elements
     *
     * @var bool $right
     */
    protected bool $right = false;

    /**
     * The tooltip object for this element
     *
     * @var TooltipInterface $o_tooltip
     */
    protected TooltipInterface $o_tooltip;

    /**
     * A possible anchor around this element
     *
     * @var AInterface|null $o_anchor
     */
    protected ?AInterface $o_anchor = null;

    /**
     * Contains data, arguments, classes, etc for the element around this element, if needed
     *
     * @var DivInterface $o_outer_div
     */
    protected DivInterface $o_outer_div;

    /**
     * Tracks the value to be displayed if the element value is NULL
     *
     * @var Stringable|string|float|int|null $null_display
     */
    protected Stringable|string|float|int|null $null_display = null;


    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->o_classes    = new Iterator();
        $this->o_attributes = new Iterator();
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
     * Sets the HTML id element attribute
     *
     * @param string|null $id
     * @param bool        $name_too
     *
     * @return static
     */
    public function setId(?string $id, bool $name_too = true): static
    {
        $this->id      = $id;
        $this->real_id = Strings::until($id, '[');

        // By default, name and id should be equal
        if ($name_too) {
            if (!$this->getReadonly() and !$this->getDisabled()) {
                $this->setName($id, false);
            }
        }

        return $this;
    }


    /**
     * Returns the (optional) anchor for this element
     *
     * @return AInterface
     */
    public function getAnchorObject(): AInterface
    {
        if (empty($this->o_anchor)) {
            $this->o_anchor = A::new()->setChildElement($this);
        }

        return $this->o_anchor;
    }


    /**
     * Sets the anchor for this element
     *
     * @param UrlInterface|AInterface|null $o_anchor
     *
     * @return Span
     */
    public function setAnchorObject(UrlInterface|AInterface|null $o_anchor): static
    {
        if ($o_anchor) {
            if ($o_anchor instanceof UrlInterface) {
                $o_anchor = A::new()->setHref($o_anchor);
            }

            $this->o_anchor = $o_anchor->setChildElement($this);

        } else {
            $this->o_anchor = null;
        }

        return $this;
    }


    /**
     * Returns true if this element has an outer div set up
     *
     * @return bool
     */
    public function hasOuterDiv(): bool
    {
        return isset($this->o_outer_div);
    }


    /**
     * Returns the (optional) outer_element for this element
     *
     * @return DivInterface
     */
    public function getOuterDivObject(): DivInterface
    {
        if (empty($this->o_outer_div)) {
            $this->o_outer_div = Div::new()->setChildElement($this);
        }

        return $this->o_outer_div;
    }


    /**
     * Sets the outer_element for this element
     *
     * @param DivInterface|null $o_outer_div
     *
     * @return Span
     */
    public function setOuterDivObject(DivInterface|null $o_outer_div): static
    {
        if ($o_outer_div) {
            $this->o_outer_div = $o_outer_div->setChildElement($this);

        } else {
            unset($this->o_outer_div);
        }

        return $this;
    }


    /**
     * Returns the tooltip title
     *
     * @return string|null
     */
    public function getTooltipTitle(): ?string
    {
        return $this->o_tooltip->getTitle();
    }


    /**
     * Returns the tooltip title
     *
     * @param string|null $title
     *
     * @return static
     */
    public function setTooltipTitle(?string $title): static
    {
        $this->getTooltipObject()
             ->setTitle($title);

        return $this;
    }


    /**
     * Returns the tooltip object for this element
     *
     * @return TooltipInterface
     */
    public function getTooltipObject(): TooltipInterface
    {
        if (empty($this->o_tooltip)) {
            $this->o_tooltip = Tooltip::new()->setSourceElement($this);
        }

        return $this->o_tooltip;
    }


    /**
     * Adds a data-KEY(=VALUE) attribute
     *
     * @param array|string|float|int|null $value
     * @param string|int                  $key
     *
     * @return static
     */
    public function addData(array|string|float|int|null $value, string|int $key): static
    {
        $this->getDataObject()->add($value, $key, false, false);

        return $this;
    }


    /**
     * Returns the data attributers for the specified key
     *
     * @param string|int $key
     *
     * @return array|string|float|int|null
     */
    public function getDataKey(string|int $key): array|string|float|int|null
    {
        return $this->getDataObject()->get($key, false);
    }


    /**
     * Renders the data attributes for the specified key
     *
     * @param             $key
     * @param string|null $prefix
     *
     * @return string|null
     */
    public function renderDataKey($key, ?string $prefix = ' '): ?string
    {
        $return = [];
        $data   = $this->getDataKey($key);

        if ($data) {
            $data = Arrays::force($data, null);

            foreach ($data as $key => $value) {
                $return[] = 'data-' . Seo::string($key) . '="' . $value . '"';
            }

            return $prefix . implode(' ', $return);
        }

        return null;
    }


    /**
     * Returns the HTML element data-* attribute store
     *
     * @return IteratorInterface
     */
    public function getDataObject(): IteratorInterface
    {
        if (empty($this->o_data)) {
            $this->o_data = new Iterator();
        }

        return $this->o_data;
    }


    /**
     * Sets the HTML element data-* attribute store
     *
     * @param IteratorInterface|array|null $o_data
     *
     * @return static
     */
    public function setDataObject(IteratorInterface|array|null $o_data): static
    {
        if (!$o_data) {
            unset($this->o_data);

        } else {
            $this->o_data = new Iterator($o_data);
        }

        return $this;
    }


    /**
     * Returns the HTML attributes as a string
     *
     * @return string|null
     */
    public function getAttributesString(): ?string
    {
        return Arrays::implodeWithKeys($this->o_attributes->getSource(), ' ', '=', '"', Utils::QUOTE_ALWAYS | Utils::HIDE_EMPTY_VALUES);
    }


    /**
     * Returns the HTML class element attribute store
     *
     * @return IteratorInterface
     */
    public function getAttributesObject(): IteratorInterface
    {
        return $this->o_attributes;
    }


    /**
     * Sets all HTML element attributes
     *
     * @param array $o_attributes
     *
     * @return static
     */
    public function setAttributesObject(array $o_attributes): static
    {
        $this->o_attributes = Iterator::new()->add($o_attributes);

        return $this;
    }


    /**
     * Adds the specified attribute
     *
     * @param string|float|int|null $value
     * @param string                $key
     *
     * @return static
     */
    public function addAttribute(string|float|int|null $value, string $key): static
    {
        $this->o_attributes->add($value, $key, exception: false);

        return $this;
    }


    /**
     * Sets a single HTML element attributes
     *
     * @param mixed                 $value
     * @param string|float|int|null $key
     * @param bool                  $skip_null_values
     *
     * @return static
     */
    public function setAttribute(mixed $value, string|float|int|null $key = null, bool $skip_null_values = true): static
    {
        $this->o_attributes->add($value, $key, $skip_null_values, exception: false);

        return $this;
    }


    /**
     * Adds an aria-KEY(=VALUE) attribute
     *
     * @param string|float|int|null $value
     * @param string                $key
     *
     * @return static
     */
    public function addAria(string|float|int|null $value, string $key): static
    {
        $this->getAriaObject()->add($value, $key, false, false);

        return $this;
    }


    /**
     * Returns the HTML element aria-* attribute store
     *
     * @return IteratorInterface
     */
    public function getAriaObject(): IteratorInterface
    {
        if (empty($this->o_aria)) {
            $this->o_aria = new Iterator();
        }

        return $this->o_aria;
    }


    /**
     * Sets the HTML element aria-* attribute store
     *
     * @param IteratorInterface|array|null $aria
     *
     * @return static
     */
    public function setAriaObject(IteratorInterface|array|null $aria): static
    {
        if (!$aria) {
            unset($this->o_aria);

        } else {
            $this->o_aria = new Iterator($aria);
        }

        return $this;
    }


    /**
     * Returns the HTML class element attribute
     *
     * @param string|null $prefix                       If true, will prefix the class list with the specified prefix
     * @param bool        $add_definition_name_to_class If true, will add the element's name attribute to the list of
     *                                                  classes
     *
     * @return string|null
     */
    public function getClass(?string $prefix = null, bool $add_definition_name_to_class = true): ?string
    {
        if (empty($this->class)) {
            $this->class = implode(' ', $this->o_classes->getSourceKeys());

            if ($add_definition_name_to_class) {
                if ($this->getName()) {
                    $identifier = $this->getName();

                } elseif ($this->getId()) {
                    $identifier = $this->getId();
                }

                if (isset($identifier)) {
                    if (preg_match('/^\d+_/', $identifier)) {
                        // Add the name attribute from the definition minus the prefixed identifier
                        $this->class .= ' ' . Strings::from($identifier, '_');

                    } else {
                        // Add the name attribute from the definition
                        $this->class .= ' ' . $identifier;
                    }
                }
            }
        }

        return get_null($prefix . $this->class);
    }


    /**
     * Adds a class to the HTML class element attribute
     *
     * @param array|string|null $classes
     *
     * @return static
     */
    public function setClass(array|string|null $classes): static
    {
        return $this->setClassesObject(Arrays::force($classes, ' '));
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
     * Set the HTML tabindex element attribute
     *
     * @param int|null $tabindex
     *
     * @return static
     */
    public function setTabIndex(?int $tabindex): static
    {
        $this->tabindex = $tabindex;
        return $this;
    }


    /**
     * Clears all the extra element attribute code
     *
     * @return static
     */
    public function clearExtraAttributes(): static
    {
        $this->extra_attributes = '';
        return $this;
    }


    /**
     * Returns the extra element attribute code
     *
     * @return string
     */
    public function getExtraAttributes(): string
    {
        return $this->extra_attributes;
    }


    /**
     * Sets all the extra element attribute code
     *
     * @param string|null $extra
     *
     * @return static
     */
    public function setExtraAttributes(?string $extra): static
    {
        $this->extra_attributes = '';
        return $this->addExtraAttributes($extra);
    }


    /**
     * Adds more to the extra element attribute code
     *
     * @param Stringable|string|null $extra
     *
     * @return static
     */
    public function addExtraAttributes(Stringable|string|null $extra): static
    {
        $this->extra_attributes .= $extra;
        return $this;
    }


    /**
     * Appends the specified content to the content of the element
     *
     * @param Stringable|string|float|int|null $content
     * @param bool                             $make_safe
     *
     * @return static
     */
    public function appendContent(Stringable|string|float|int|null $content, bool $make_safe = false): static
    {
        if (is_object($content)) {
            // This object is Stringable so it can be converted to string.
            // If it is a RenderableInterface, it will automatically render
            $content   = (string) $content;
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
     * @param bool                             $make_safe
     *
     * @return static
     */
    public function prependContent(Stringable|string|float|int|null $content, bool $make_safe = false): static
    {
        if ($content instanceof Stringable) {
            // This object is Stringable so it can be converted to string.
            // If it is a RenderableInterface, it will automatically render
            $content   = (string) $content;
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
     * @return Stringable|string|float|int|null
     */
    public function getContent(): Stringable|string|float|int|null
    {
        return $this->content ?? $this->null_display;
    }


    /**
     * Sets the content of the element
     *
     * @param Stringable|string|float|int|null $content
     * @param bool                             $make_safe
     *
     * @return static
     */
    public function setContent(Stringable|string|float|int|null $content, bool $make_safe = false): static
    {
        $this->content = null;

        return $this->appendContent($content, $make_safe);
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
     * Sets the height of the element to display
     *
     * @param int|null $height
     *
     * @return static
     */
    public function setHeight(?int $height): static
    {
        if ($height < 0) {
            throw new OutOfBoundsException(tr('Invalid element height ":value" specified, it should be 0 or above', [
                ':value' => $height,
            ]));
        }

        $this->height = $height;

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
     * Sets the width of the element to display
     *
     * @param int|null $width
     *
     * @return static
     */
    public function setWidth(?int $width): static
    {
        if ($width < 0) {
            throw new OutOfBoundsException(tr('Invalid element width ":value" specified, it should be 0 or above', [
                ':value' => $width,
            ]));
        }

        $this->width = $width;

        return $this;
    }


    /**
     * Set if the button is right aligned or not
     *
     * @param bool $right
     *
     * @return static
     */
    public function setFloatRight(bool $right): static
    {
        if ($right) {
            $this->o_classes->add(true, 'float-right', exception: false);

        } else {
            $this->o_classes->removeKeys('float-right');
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
        return $this->getClassesObject()->keyExists('float-right');
    }


    /**
     * Returns the HTML class element attribute store
     *
     * @return IteratorInterface
     */
    public function getClassesObject(): IteratorInterface
    {
        return $this->o_classes;
    }


    /**
     * Sets the HTML element class attribute
     *
     * @param IteratorInterface|array|string|null $classes
     *
     * @return static
     */
    public function setClassesObject(IteratorInterface|array|string|null $classes): static
    {
        $this->o_classes = new Iterator();

        return $this->addClasses($classes);
    }


    /**
     * Sets the HTML class element attribute
     *
     * @param bool $auto_focus
     *
     * @return static
     */
    public function setAutofocus(bool $auto_focus): static
    {
        if ($auto_focus) {
            if (static::$autofocus !== null) {
                if (static::$autofocus !== $this->name) {
                    throw new OutOfBoundsException(tr('Cannot set autofocus on element ":name", its already being used by HTML element name ":already"', [
                        ':name'    => $this->name,
                        ':already' => static::$autofocus,
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
                // Some element has autofocus, is it this one?
                if (static::$autofocus === $this->name) {
                    throw new OutOfBoundsException(tr('Cannot remove autofocus from element name ":name", it does not have autofocus', [
                        ':name' => $this->name,
                    ]));
                }

                static::$autofocus = null;
            }
        }

        return $this;
    }


    /**
     * Returns the HTML name attribute for this element
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }


    /**
     * Sets the HTML name attribute for this element
     *
     * @param string|null $name   The "name" attribute for this element
     * @param bool        $id_too If true, will make the elements id the same as the name
     *
     * @return static
     */
    public function setName(?string $name, bool $id_too = true): static
    {
        if ($this->getReadonly() or $this->getDisabled()) {
            // Name is not allowed in readonly or disabled. Use ID instead
            return $this->setId($name, false);
        }

        $this->name      = $name;
        $this->real_name = Strings::until($name, '[');

        // By default, name and id should be equal
        if ($id_too) {
            $this->setId($name, false);
        }

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
     * @param bool      $disabled
     * @param bool|null $set_readonly
     *
     * @return static
     */
    public function setDisabled(bool $disabled, ?bool $set_readonly = null): static
    {
        if ($disabled) {
            $this->o_classes->add('disabled', 'disabled', exception: false);

        } else {
            $this->o_classes->removeKeys('disabled');
        }

        $this->disabled = $disabled;
        $set_readonly   = $set_readonly ?? config()->getBoolean('web.elements.readonly.auto.disabled', false);

        if ($set_readonly ) {
            return $this->setReadonly($disabled, false);
        }

        return $this->updateReadonlyDisabledName();
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
     * @param bool      $readonly
     * @param bool|null $set_disabled
     *
     * @return static
     */
    public function setReadonly(bool $readonly, ?bool $set_disabled = null): static
    {
        if ($readonly) {
            $this->o_classes->add('readonly', 'readonly', exception: false);

        } else {
            $this->o_classes->removeKeys('readonly');
        }

        $this->readonly = $readonly;
        $set_disabled   = $set_disabled ?? config()->getBoolean('web.elements.readonly.auto.disabled', false);

        if ($set_disabled) {
            return $this->setDisabled($readonly, false);
        }

        return $this->updateReadonlyDisabledName();
    }


    /**
     * Updates the element's name if the object is readonly or disabled
     *
     * @return static
     */
    protected function updateReadonlyDisabledName(): static
    {
        if ($this->getReadonly() or $this->getDisabled()) {
            $this->setId($this->getName(), false)
                 ->setName(null, false);

        } else {
            // In reverse, when not readonly or disabled and  name is empty, update name with id
            if (empty($this->getName())) {
                $this->setId($this->getId(), false);
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
     * Returns the DataEntry Definition on this element
     *
     * If no Definition object was set, one will be created using the data in this object
     *
     * @return DefinitionInterface|null
     */
    public function getDefinitionObject(): ?DefinitionInterface
    {
        if (empty($this->o_definition)) {
            $this->__setDefinitionObject(Definition::new($this->getName())
                                                   ->setDisplay($this->getDisplay())
                                                   ->setAfterContent($this->getAfterContent())
                                                   ->setBeforeContent($this->getBeforeContent())
                                                   ->setVisible($this->getVisible())
                                                   ->setOptional(!$this->getRequired())
                                                   ->addClasses($this->getClassesObject()->getSource())
                                                   ->setData($this->getDataObject())
                                                   ->setAria($this->getAriaObject())
                                                   ->setDisabled($this->getDisabled())
                                                   ->setReadOnly($this->getReadonly())
                                                   ->setAutoFocus($this->getAutoFocus())
                                                   ->setNullDisplay($this->getNullDisplay())
                                                   ->setProperties($this->getProperties())
                                                   ->setScriptsObject($this->getScriptsObject()));
        }

        return $this->__getDefinitionObject();
    }


    /**
     * Set the DataEntry Definition on this element
     *
     * @param DefinitionInterface|null $o_definition
     *
     * @return static
     */
    public function setDefinitionObject(?DefinitionInterface $o_definition): static
    {
        if ($o_definition) {
            // Apply the definition rules to this element
            $this->setName($o_definition->getName())
                 ->setDisplay($o_definition->getDisplay())
                 ->setAfterContent($o_definition->getAfterContent())
                 ->setBeforeContent($o_definition->getBeforeContent())
                 ->setVisible($o_definition->getVisible())
                 ->setRequired($o_definition->getRequired())
                 ->addClasses($o_definition->getClasses())
                 ->setDataObject($o_definition->getData())
                 ->setAriaObject($o_definition->getAria())
                 ->setDisabled($o_definition->getDisabled())
                 ->setReadOnly($o_definition->getReadonly())
                 ->setAutoFocus($o_definition->getAutoFocus())
                 ->setNullDisplay($o_definition->getNullDisplay())
                 ->setProperties($o_definition->getProperties())
                 ->setScriptsObject($o_definition->getScriptsObject());
        }

        return $this->__setDefinitionObject($o_definition);
    }


    /**
     * Adds the specified class to the HTML element class attribute
     *
     * @param IteratorInterface|array|string|null $o_class
     *
     * @return static
     */
    public function addClass(IteratorInterface|array|string|null $o_class): static
    {
        return $this->addClasses($o_class);
    }


    /**
     * Adds the specified classes to the HTML element class attribute
     *
     * @param IteratorInterface|array|string|null $o_classes
     *
     * @return static
     */
    public function addClasses(IteratorInterface|array|string|null $o_classes): static
    {
        foreach (Arrays::force($o_classes, ' ') as $class) {
            $this->o_classes->add($class, $class, exception: false);
        }

        return $this;
    }


    /**
     * Removes the specified classes from the HTML element class attribute
     *
     * @note This is a wrapper method for Element::removeClass()
     *
     * @param IteratorInterface|array|string|null $o_class
     *
     * @return static
     */
    public function removeClass(IteratorInterface|array|string|null $o_class): static
    {
        return $this->removeClasses($o_class);
    }


    /**
     * Removes the specified class from the HTML element class attribute
     *
     * @param IteratorInterface|array|string|null $o_classes
     *
     * @return static
     */
    public function removeClasses(IteratorInterface|array|string|null $o_classes): static
    {
        foreach (Arrays::force($o_classes, ' ') as $class) {
            $this->o_classes->removeKeys($class);
        }

        return $this;
    }


    /**
     * Renders and returns the content that come before this element
     *
     * @return string|null
     */
    public function renderBeforeContent(): ?string
    {
        if ($this->hasBeforeContent()) {
            return $this->renderContent($this->getBeforeContent());
        }

        return null;
    }


    /**
     * Renders and returns the content that come after this element
     *
     * @return string|null
     */
    public function renderAfterContent(): ?string
    {
        if ($this->hasAfterContent()) {
            return $this->renderContent($this->getAfterContent());
        }

        return null;
    }


    /**
     * Renders and returns the specified content
     *
     * @param RenderInterface|array|callable|string|null $content
     *
     * @return string|null
     */
    protected function renderContent(RenderInterface|array|callable|string|null $content): ?string
    {
        $return = null;

        if (is_array($content)) {
            // This is a list of content items. Render them one by one, and return them all together
            foreach ($content as $item) {
                $return .= $this->renderContent($item);
            }

            return $return;
        }

        if ($content instanceof RenderInterface) {
            if ($content instanceof ButtonInterface) {
                // When rendering added buttons, add this aria
                $content->addAria($this->getId() ?? $this->getName(), 'described-by');
            }

            return $content->setReadonly($content->getReadonly() or $this->getReadonly())
                           ->setDisabled($content->getDisabled() or $this->getDisabled())
                           ->render();
        }

         return (string) $content;
    }


    /**
     * Returns the HTML visible element attribute
     *
     * @return bool
     */
    public function getVisible(): bool
    {
        return $this->visible;
    }


    /**
     * Set the HTML visible element attribute
     *
     * @param bool $visible
     * @param bool $parent_only
     *
     * @return static
     */
    public function setVisible(bool $visible, bool $parent_only = true): static
    {
        if ($parent_only) {
            if ($visible) {
                $this->o_classes->removeKeys('invisible');

            } else {
                $this->o_classes->add(true, 'invisible', exception: false);
            }
        }

        $this->visible = $visible;

        return $this;
    }


    /**
     * Returns the HTML visible element attribute
     *
     * @return bool
     */
    public function getDisplay(): bool
    {
        return $this->display;
    }


    /**
     * Set the HTML visible element attribute
     *
     * @param bool $display
     * @param bool $parent_only
     *
     * @return static
     */
    public function setDisplay(bool $display, bool $parent_only = true): static
    {
        if ($parent_only) {
            if ($display) {
                $this->o_classes->removeKeys('d-none');

            } else {
                $this->o_classes->add(true, 'd-none', exception: false);
            }
        }

        $this->display = $display;

        return $this;
    }


    /**
     * Returns the HTML "required" element attribute
     *
     * @return bool
     */
    public function getRequired(): bool
    {
        return $this->required;
    }


    /**
     * Set the HTML "required" element attribute
     *
     * @param bool $required
     *
     * @return static
     */
    public function setRequired(bool $required): static
    {
        if ($required) {
            $this->o_classes->add('required', 'required', exception: false);

        } else {
            $this->o_classes->removeKeys('required');
        }

        $this->required = $required;
        return $this;
    }


    /**
     * Returns the HTML "null_display" element attribute
     *
     * @return Stringable|string|float|int|null
     */
    public function getNullDisplay(): Stringable|string|float|int|null
    {
        return $this->null_display;
    }


    /**
     * Set the HTML "null_display" element attribute
     *
     * @param Stringable|string|float|int|null $null_display
     *
     * @return static
     */
    public function setNullDisplay(Stringable|string|float|int|null $null_display): static
    {
        $this->null_display = $null_display;

        return $this;
    }
}
