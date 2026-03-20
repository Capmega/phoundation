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
 *
 * @todo Input control attributes must be updated so that their internal source also is managed with source arrays. This way, setDefinition() could literally dump the definition source array into an input control source array
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Traits;

use Phoundation\Data\DataEntries\Definitions\Definition;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataBoolRenderToNull;
use Phoundation\Data\Traits\TraitDataContent;
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
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Components\Div;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Interfaces\AnchorInterface;
use Phoundation\Web\Html\Components\Interfaces\DivInterface;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
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
    use TraitDataContent;
    use TraitDataReadonly;
    use TraitDataDisabled;
    use TraitDataScripts;
    use TraitDataBoolRenderToNull;


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
     * @var IteratorInterface $_attributes
     */
    protected IteratorInterface $_attributes;

    /**
     * The HTML data-* element attribute store
     *
     * @var IteratorInterface $_data
     */
    protected IteratorInterface $_data;

    /**
     * The HTML element aria-* attribute store
     *
     * @var IteratorInterface $_aria
     */
    protected IteratorInterface $_aria;

    /**
     * The HTML class element attribute store
     *
     * @var IteratorInterface $_classes
     */
    protected IteratorInterface $_classes;

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
     * @var string|null $extra_attributes
     */
    protected ?string $extra_attributes = null;

    /**
     * The element content
     *
     * @var RenderInterface|string|float|int|null $content
     */
    protected RenderInterface|string|float|int|null $content = null;

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
     * @var TooltipInterface $_tooltip
     */
    protected TooltipInterface $_tooltip;

    /**
     * A possible anchor around this element
     *
     * @var AnchorInterface|null $_anchor
     */
    protected ?AnchorInterface $_anchor = null;

    /**
     * Contains data, arguments, classes, etc for the element around this element, if needed
     *
     * @var DivInterface $_outer_div
     */
    protected DivInterface $_outer_div;

    /**
     * Tracks the value to be displayed if the element value is NULL
     *
     * @var Stringable|string|float|int|null $null_display
     */
    protected Stringable|string|float|int|null $null_display = null;

    /**
     * Tracks the title attribute for the element
     *
     * @var string|null $title
     */
    protected ?string $title = null;

    /**
     * Tracks the type attribute for the element
     *
     * @var string|null $type
     */
    protected ?string $type = null;

    /**
     * Tracks the role attribute for the element
     *
     * @var string|null $role
     */
    protected ?string $role = null;

    /**
     * Tracks the style attribute for the element
     *
     * @var string|null $style
     */
    protected ?string $style = null;


    /**
     * Class constructor
     */
    public function __construct()
    {
        $this->_classes    = Iterator::new();
        $this->_attributes = Iterator::new()->setExceptionOnGet(false);
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
     * Returns the HTML title element attribute
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }


    /**
     * Sets the HTML title element attribute
     *
     * @param string|false|null $title            The title for this object
     * @param bool              $make_safe [true] If true, will make the title safe for use with HTML
     *
     * @return static
     */
    public function setTitle(string|false|null $title, bool $make_safe = true): static
    {
        $this->title = get_value_unless_false($this->title, $title);
        $this->title = get_null($this->title);

        if ($make_safe) {
            $this->title = Html::safe($this->title);
        }

        return $this;
    }


    /**
     * Returns the HTML type element attribute
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }


    /**
     * Sets the HTML type element attribute
     *
     * @param string|null $type
     *
     * @return static
     */
    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }


    /**
     * Returns the HTML role element attribute
     *
     * @return string|null
     */
    public function getRole(): ?string
    {
        return $this->role;
    }


    /**
     * Sets the HTML role element attribute
     *
     * @param string|null $role
     *
     * @return static
     */
    public function setRole(?string $role): static
    {
        $this->role = $role;
        return $this;
    }


    /**
     * Returns the HTML style element attribute
     *
     * @return string|null
     */
    public function getStyle(): ?string
    {
        return $this->style;
    }


    /**
     * Sets the HTML style element attribute
     *
     * @param string|null $style
     *
     * @return static
     */
    public function setStyle(?string $style): static
    {
        $this->style = $style;
        return $this;
    }


    /**
     * Adds to the HTML style element attribute
     *
     * @param string|null $style
     *
     * @return static
     */
    public function addStyle(?string $style): static
    {
        $this->style .= $style;
        return $this;
    }


    /**
     * Returns the (optional) anchor for this element
     *
     * @return AnchorInterface
     */
    public function getAnchorObject(): AnchorInterface
    {
        if (empty($this->_anchor)) {
            $this->_anchor = Anchor::new()->setChildElement($this);
        }

        return $this->_anchor;
    }


    /**
     * Sets the anchor for this element
     *
     * @param UrlInterface|AnchorInterface|null $_anchor
     *
     * @return Span
     */
    public function setAnchorObject(UrlInterface|AnchorInterface|null $_anchor): static
    {
        if ($_anchor) {
            if ($_anchor instanceof UrlInterface) {
                $_anchor = Anchor::new()->setUrlObject($_anchor);
            }

            $this->_anchor = $_anchor->setChildElement($this);

        } else {
            $this->_anchor = null;
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
        return isset($this->_outer_div);
    }


    /**
     * Returns the (optional) outer_element for this element
     *
     * @return DivInterface
     */
    public function getOuterDivObject(): DivInterface
    {
        if (empty($this->_outer_div)) {
            $this->_outer_div = Div::new()->setChildElement($this);
        }

        return $this->_outer_div;
    }


    /**
     * Sets the outer_element for this element
     *
     * @param DivInterface|null $_outer_div
     *
     * @return Span
     */
    public function setOuterDivObject(DivInterface|null $_outer_div): static
    {
        if ($_outer_div) {
            $this->_outer_div = $_outer_div->setChildElement($this);

        } else {
            unset($this->_outer_div);
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
        return $this->getTooltipObject()->getTitle();
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
        if (empty($this->_tooltip)) {
            $this->_tooltip = Tooltip::new()->setSourceElement($this);
        }

        return $this->_tooltip;
    }


    /**
     * Adds a data-KEY(=VALUE) attribute
     *
     * @param array|string|float|int|null $value
     * @param string|int                  $key
     * @param bool                        $skip_null_values
     *
     * @return static
     */
    public function addData(array|string|float|int|null $value, string|int $key, bool $skip_null_values = true): static
    {
        $this->getDataObject()->add($value, $key, $skip_null_values, false);

        return $this;
    }


    /**
     * Returns the data attributes for the specified key
     *
     * @param string|int $key
     *
     * @return array|string|float|int|null
     */
    public function getDataKey(string|int $key): array|string|float|int|null
    {
        return $this->getDataObject()->get($key, exception: false);
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
     * @param bool $resolve_callbacks
     *
     * @return IteratorInterface
     */
    public function getDataObject(bool $resolve_callbacks = true): IteratorInterface
    {
        if (empty($this->_data)) {
            $this->_data = new Iterator();
        }

        if ($resolve_callbacks) {
            // Resolve any value that is a callback instead of a normal value
            $this->_data = Arrays::resolveCallbacks($this->_data);
        }

        return $this->_data;
    }


    /**
     * Sets the HTML element data-* attribute store
     *
     * @param IteratorInterface|array|null $_data
     *
     * @return static
     */
    public function setDataObject(IteratorInterface|array|null $_data): static
    {
        if (!$_data) {
            unset($this->_data);

        } else {
            $this->_data = new Iterator($_data);
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
        return Arrays::implodeWithKeys($this->_attributes->getSource(), ' ', '=', '"', Utils::QUOTE_ALWAYS | Utils::HIDE_EMPTY_VALUES);
    }


    /**
     * Returns the HTML class element attribute store
     *
     * @return IteratorInterface
     */
    public function getAttributesObject(): IteratorInterface
    {
        return $this->_attributes;
    }


    /**
     * Sets all HTML element attributes
     *
     * @param array $_attributes
     *
     * @return static
     */
    public function setAttributesObject(array $_attributes): static
    {
        $this->_attributes = Iterator::new()->add($_attributes);

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
        $this->_attributes->add($value, $key, exception: false);

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
        $this->_attributes->add($value, $key, $skip_null_values, exception: false);

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
     * @param bool $resolve_callbacks
     *
     * @return IteratorInterface
     */
    public function getAriaObject(bool $resolve_callbacks = true): IteratorInterface
    {
        if (empty($this->_aria)) {
            $this->_aria = new Iterator();
        }

        if ($resolve_callbacks) {
            // Resolve any value that is a callback instead of a normal value
            $this->_aria = Arrays::resolveCallbacks($this->_aria);
        }

        return $this->_aria;
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
            unset($this->_aria);

        } else {
            $this->_aria = new Iterator($aria);
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
            $this->class = implode(' ', Arrays::resolveCallbacks($this->_classes->getSource()));

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
        $this->extra_attributes = null;
        return $this;
    }


    /**
     * Returns the extra element attribute code
     *
     * @return string|null
     */
    public function getExtraAttributes(): ?string
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
        $this->extra_attributes = null;
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
     * @param bool|null $right              If true, button will be right aligned, if false, button will be left aligned, if NULL, button will have default
     *                                      alignment
     * @param bool      $reset_block [true] If true, will reset the "float right" property to false, as these two are mutually exclusive
     *
     * @return static
     */
    public function setFloatRight(?bool $right, bool $reset_block = true): static
    {
        if ($right) {
            $this->addClass('float-right');

            if ($reset_block) {
                $this->setBlock(false, false);
            }

        } elseif ($right !== null) {
            $this->removeClass('float-right');
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
        return $this->_classes;
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
        $this->_classes = new Iterator();

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
     * Returns true when this object is neither readonly nor disabled
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return !($this->getReadonly() or $this->getDisabled());
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
     * @param bool              $disabled
     * @param bool|null         $set_readonly
     * @param string|false|null $title
     *
     * @return static
     */
    public function setDisabled(bool $disabled, ?bool $set_readonly = null, string|false|null $title = false): static
    {
        if ($disabled) {
            $this->addClass('disabled');

        } else {
            $this->removeClass('disabled');
        }

        $this->disabled = $disabled;
        $set_readonly   = $set_readonly ?? config()->getBoolean('platforms.web.elements.readonly.auto.disabled', false);

        if ($set_readonly ) {
            return $this->setReadonly($disabled, false);
        }

        return $this->updateReadonlyDisabledName()->setTitle($title);
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
     * @param bool              $readonly
     * @param bool|null         $set_disabled
     * @param string|false|null $title
     *
     * @return static
     */
    public function setReadonly(bool $readonly, ?bool $set_disabled = null, string|false|null $title = false): static
    {
        if ($readonly) {
            $this->addClass('readonly');

        } else {
            $this->removeClass('readonly');
        }

        $this->readonly = $readonly;
        $set_disabled   = $set_disabled ?? config()->getBoolean('platforms.web.elements.readonly.auto.disabled', false);

        if ($set_disabled) {
            return $this->setDisabled($readonly, false);
        }

        return $this->updateReadonlyDisabledName()->setTitle($title);
    }


    /**
     * Updates the element's name if the object is readonly or disabled
     *
     * @return static
     */
    protected function updateReadonlyDisabledName(): static
    {
        if (!$this instanceof ButtonInterface) {
            if ($this->getReadonly() or $this->getDisabled()) {
                $this->setId($this->getName(), false)
                     ->setName(null, false);

            } else {
                // In reverse, when not readonly or disabled and  name is empty, update name with id
                if (empty($this->getName())) {
                    $this->setId($this->getId(), false);
                }
            }
        }

        return $this;
    }


    /**
     * Returns if the contents of the element should be selectable by a user, or not
     *
     * @see https://duckduckgo.com/?t=ffab&q=make+text+unselectable+in+html&atb=v446-1&ia=web&iax=qa
     *
     * @return bool
     */
    public function getSelectable(): bool
    {
        return !$this->_classes->hasKey('unselectable');
    }


    /**
     * Sets if the contents of the element should be selectable by a user, or not
     *
     * @see https://duckduckgo.com/?t=ffab&q=make+text+unselectable+in+html&atb=v446-1&ia=web&iax=qa
     *
     *
     * @param bool $selectable
     *
     * @return static
     */
    public function setSelectable(bool $selectable): static
    {
        if ($selectable) {
            // Being selectable is the default state, so remove "unselectable"
            $this->removeClass('unselectable');

        } else {
            $this->addClass('unselectable');
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
        if (empty($this->_definition)) {
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
                                                   ->setSelectable($this->getSelectable())
                                                   ->setScriptsObject($this->getScriptsObject()));
        }

        return $this->__getDefinitionObject();
    }


    /**
     * Set the DataEntry Definition on this element
     *
     * @param DefinitionInterface|null $_definition
     *
     * @return static
     */
    public function setDefinitionObject(?DefinitionInterface $_definition): static
    {
        if ($_definition) {
            // Apply the definition rules to this element
            $this->setName($_definition->getName())
                 ->setDisplay($_definition->getDisplay())
                 ->setAfterContent($_definition->getAfterContent())
                 ->setBeforeContent($_definition->getBeforeContent())
                 ->setVisible($_definition->getVisible())
                 ->addClasses($_definition->getClasses())
                 ->setDataObject($_definition->getData())
                 ->setAriaObject($_definition->getAria())
                 ->setDisabled($_definition->getDisabled())
                 ->setReadOnly($_definition->getReadonly())
                 ->setAutoFocus($_definition->getAutoFocus())
                 ->setNullDisplay($_definition->getNullDisplay())
                 ->setProperties($_definition->getProperties())
                 ->setSelectable($_definition->getSelectable())
                 ->setScriptsObject($_definition->getScriptsObject());
        }

        return $this->__setDefinitionObject($_definition);
    }


    /**
     * Adds the specified class to the HTML element class attribute
     *
     * @param IteratorInterface|callable|array|string|null $_class
     *
     * @return static
     */
    public function addClass(IteratorInterface|callable|array|string|null $_class): static
    {
        return $this->addClasses($_class);
    }


    /**
     * Adds the specified classes to the HTML element class attribute
     *
     * @param IteratorInterface|callable|array|string|null $_classes
     *
     * @return static
     */
    public function addClasses(IteratorInterface|callable|array|string|null $_classes): static
    {
        foreach (Arrays::force($_classes, ' ') as $class) {
            $this->_classes->add($class, $class, exception: false);
        }

        return $this;
    }


    /**
     * Removes the specified classes from the HTML element class attribute
     *
     * @note This is a wrapper method for Element::removeClass()
     *
     * @param IteratorInterface|array|string|null $_class
     *
     * @return static
     */
    public function removeClass(IteratorInterface|array|string|null $_class): static
    {
        return $this->removeClasses($_class);
    }


    /**
     * Removes the specified class from the HTML element class attribute
     *
     * @param IteratorInterface|array|string|null $_classes
     *
     * @return static
     */
    public function removeClasses(IteratorInterface|array|string|null $_classes): static
    {
        foreach (Arrays::force($_classes, ' ') as $class) {
            $this->_classes->removeKeys($class);
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

        if (is_callable($content)) {
            // Content was specified as a callback; Execute the callback to get the actual content.
            $content = $content();
        }

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
                $this->removeClass('invisible');

            } else {
                $this->addClass('invisible');
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
                $this->removeClass('d-none');

            } else {
                $this->addClass('d-none');
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
            $this->addClass('required');

        } else {
            $this->removeClass('required');
        }

        $this->required = $required;
        return $this;
    }


    /**
     * Returns the HTML "null_display" element attribute
     *
     * @return string|null
     */
    public function getNullDisplay(): string|null
    {
        return $this->null_display;
    }


    /**
     * Set the HTML "null_display" element attribute
     *
     * @param RenderInterface|string|float|int|null $value
     * @param bool                                  $make_safe
     *
     * @return static
     */
    public function setNullDisplay(RenderInterface|string|float|int|null $value, bool $make_safe = false): static
    {
        $this->null_display = Html::safe($value, $make_safe, true);
        return $this;
    }
}
