<?php

/**
 * Class Tooltip
 *
 * This is the default tooltip class, initially designed to work well with popper. This class can generate data-*
 * attributes for an in-element tooltip, external element bound tooltips with an "?" icon, or stand-alone tooltip icons
 *
 * @see       https://popper.js.org/
 * @see       https://getbootstrap.com/docs/4.1/components/tooltips/ for documentation
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Tooltips;

use Phoundation\Core\Sessions\SessionConfig;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Element;
use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Enums\EnumTooltipBoundary;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Enums\EnumTooltipPlacement;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Enums\EnumTooltipTrigger;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Enums\Interfaces\EnumTooltipBoundaryInterface;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Enums\Interfaces\EnumTooltipPlacementInterface;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Enums\Interfaces\EnumTooltipTriggerInterface;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Interfaces\TooltipInterface;
use Phoundation\Web\Html\Enums\EnumJavascriptWrappers;

class Tooltip extends Element implements TooltipInterface
{
    /**
     * Tracks if the required javascript has already been sent or not
     *
     * @var bool $javascript_sent
     */
    protected static bool $javascript_sent = false;

    /**
     * The element to which this tooltip belongs
     *
     * @var ElementInterface|null $source_element
     */
    protected ?ElementInterface $source_element;

    /**
     * Tracks if the required javascript has already been sent or not
     *
     * @var bool $javascript_sent
     */
    protected bool $use_icon;

    /**
     * The HTML used to render the icon
     *
     * @var string $icon_html
     */
    protected string $icon_html;

    /**
     * The tooltip data information
     *
     * @var IteratorInterface $data
     */
    protected IteratorInterface $data;

    /**
     * Sets if the tooltip icon should be rendered before or after the object
     *
     * @var bool $icon_prefix
     */
    protected bool $render_before;


    /**
     * Tooltip class constructor
     *
     * @param string|null $content
     *
     * @note This method does NOT call its parent constructor!
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        $this->data    = new Iterator();
        $this->element = 'tooltip';
        // Set default values
        $this->setRenderBefore(SessionConfig::getBoolean('web.html.tooltips.icon.before', false))
             ->setPlacement(EnumTooltipPlacement::right)
             ->setTriggers(EnumTooltipTrigger::from(SessionConfig::getString('web.html.tooltips.trigger', 'click')))
             ->setUseIcon(SessionConfig::getBoolean('web.html.tooltips.icon.use', false))
             ->setHtml(SessionConfig::getBoolean('web.html.tooltips.html', false));
    }


    /**
     * Sets if the tooltip may contain HTML or plain text
     *
     * @param bool $html
     *
     * @return static
     */
    public function setHtml(bool $html): static
    {
        $this->data->set($html, 'html');

        return $this;
    }


    /**
     * Sets the tooltip trigger for this element
     *
     * @param EnumTooltipTriggerInterface ...$triggers
     *
     * @return static
     */
    public function setTriggers(EnumTooltipTriggerInterface ...$triggers): static
    {
        foreach ($triggers as &$trigger) {
            if ($trigger === EnumTooltipTrigger::manual) {
                if (count($triggers) > 1) {
                    throw OutOfBoundsException::new(tr('Cannot define combined tooltip triggers with EnumTooltipTrigger::manual'));
                }
            }
            $trigger = $trigger->value;
        }
        $this->data->set($triggers, 'trigger');
        unset($trigger);

        return $this;
    }


    /**
     * Sets the positioning the tooltip - auto | top | bottom | left | right.
     *
     * @param EnumTooltipPlacementInterface $placement
     *
     * @return static
     */
    public function setPlacement(EnumTooltipPlacementInterface $placement): static
    {
        $this->data->set($placement->value, 'placement');

        return $this;
    }


    /**
     * Returns the source element to which this tooltip is bound, if any
     *
     * @return ElementInterface|null
     */
    public function getSourceElement(): ?ElementInterface
    {
        return $this->source_element;
    }


    /**
     * Sets the source element to which this tooltip is bound, if any
     *
     * @param ElementInterface|null $source_element
     *
     * @return static
     */
    public function setSourceElement(?ElementInterface $source_element): static
    {
        $this->source_element = $source_element;

        return $this;
    }


    /**
     * Returns the tooltip trigger for this element
     *
     * @return array
     */
    public function getTriggers(): array
    {
        $triggers = $this->data->get('trigger', false);
        foreach ($triggers as &$trigger) {
            $trigger = EnumTooltipTrigger::from($trigger);
        }
        unset($trigger);

        return $triggers;
    }


    /**
     * Returns the tooltip title for this element
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->data->get('title', false);
    }


    /**
     * Sets the tooltip title for this element
     *
     * @param string|null $title
     *
     * @return static
     */
    public function setTitle(?string $title): static
    {
        $this->data->set($title, 'title');

        return $this;
    }


    /**
     * Returns if CSS fade transition will be applied to the tooltip
     *
     * @return bool
     */
    public function getAnimation(): bool
    {
        return (bool) $this->data->get('animation', false);
    }


    /**
     * Sets if CSS fade transition will be applied to the tooltip
     *
     * @param bool $animation
     *
     * @return static
     */
    public function setAnimation(bool $animation): static
    {
        $this->data->set($animation, 'animation');

        return $this;
    }


    /**
     * Returns if tooltip should be added on a separate icon
     *
     * @return bool
     */
    public function getUseIcon(): bool
    {
        return $this->use_icon;
    }


    /**
     * Sets if tooltip should be added on a separate icon
     *
     * @param bool $use_icon
     *
     * @return static
     */
    public function setUseIcon(bool $use_icon): static
    {
        $this->use_icon = $use_icon;

        return $this;
    }


    /**
     * Returns if tooltip should be added on a separate icon
     *
     * @return bool
     */
    public function getRenderBefore(): bool
    {
        return $this->render_before;
    }


    /**
     * Sets if tooltip should be added on a separate icon
     *
     * @param bool $render_before
     *
     * @return static
     */
    public function setRenderBefore(bool $render_before): static
    {
        $this->render_before = $render_before;

        return $this;
    }


    /**
     * Returns if the tooltip should be appended to the specified element
     *
     * @return string|null
     */
    public function getContainer(): ?string
    {
        return $this->data->get('container', false);
    }


    /**
     * Sets if the tooltip should be appended to the specified element
     *
     * @param string|null $container
     *
     * @return static
     */
    public function setContainer(?string $container): static
    {
        $this->data->set($container, 'container');

        return $this;
    }


    /**
     * Returns the delay showing and hiding the tooltip (ms)
     *
     * @note Does not apply to the manual trigger type
     * @return int|null
     */
    public function getDelay(): ?int
    {
        return $this->data->get('delay', false);
    }


    /**
     * Sets the delay showing and hiding the tooltip (ms)
     *
     * @note Does not apply to the manual trigger type
     *
     * @param int|null $delay
     *
     * @return static
     */
    public function setDelay(?int $delay): static
    {
        $this->data->set($delay, 'delay');

        return $this;
    }


    /**
     * Returns if the tooltip may contain HTML or plain text
     *
     * @return bool
     */
    public function getHtml(): bool
    {
        return (bool) $this->data->get('html', false);
    }


    /**
     * Returns the positioning the tooltip - auto | top | bottom | left | right.
     *
     * @return EnumTooltipPlacementInterface
     */
    public function getPlacement(): EnumTooltipPlacementInterface
    {
        return EnumTooltipPlacement::from($this->data->get('placement', false));
    }


    /**
     * Returns the positioning the tooltip - auto | top | bottom | left | right.
     *
     * @return EnumTooltipPlacementInterface
     */
    public function getFallbackPlacements(): EnumTooltipPlacementInterface
    {
        return EnumTooltipPlacement::from($this->data->get('fallbackPlacements', false));
    }


    /**
     * Sets the positioning the tooltip - auto | top | bottom | left | right.
     *
     * @param EnumTooltipPlacementInterface $placement
     *
     * @return static
     */
    public function setFallbackPlacements(EnumTooltipPlacementInterface $placement): static
    {
        $this->data->set($placement->value, 'fallbackPlacements');

        return $this;
    }


    /**
     * Returns the Base HTML to use when creating the tooltip
     *
     * Defaults to <div class="tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>
     *
     * @return string
     */
    public function getTemplate(): string
    {
        return $this->data->get('template', false) ?? '<div class="tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>';
    }


    /**
     * Sets the Base HTML to use when creating the tooltip
     *
     * @param string $template
     *
     * @return static
     */
    public function setTemplate(string $template): static
    {
        $this->data->set($template, 'template');

        return $this;
    }


    /**
     * Returns the offset of the tooltip relative to its target
     *
     * @return int|null
     */
    public function getOffset(): ?int
    {
        return $this->data->get('offset', false);
    }


    /**
     * Sets the offset of the tooltip relative to its target
     *
     * @param int|null $delay
     *
     * @return static
     */
    public function setOffset(?int $delay): static
    {
        $this->data->set($delay, 'offset');

        return $this;
    }


    /**
     * Returns the overflow constraint boundary of the tooltip
     *
     * @return EnumTooltipBoundaryInterface|string
     */
    public function getBoundary(): EnumTooltipBoundaryInterface|string
    {
        $boundary = $this->data->get('boundary', false);
        if (!$boundary) {
            return EnumTooltipBoundary::scrollParent;
        }

        return EnumTooltipBoundary::from($boundary) ?? $boundary;
    }


    /**
     * Sets the overflow constraint boundary of the tooltip
     *
     * @param EnumTooltipBoundaryInterface|string $boundary
     *
     * @return static
     */
    public function setBoundary(EnumTooltipBoundaryInterface|string $boundary): static
    {
        if ($boundary instanceof EnumTooltipBoundaryInterface) {
            $boundary = $boundary->value;
        }
        $this->data->set($boundary, 'boundary');

        return $this;
    }


    /**
     * "Renders" the tooltip by injecting data-tooltip
     *
     * @param string|null $render
     *
     * @return string|null
     */
    public function render(?string $render = null): ?string
    {
        $this->data->set('tooltip', 'tooltip');
        $return = '';
        if (!static::$javascript_sent) {
            static::$javascript_sent = true;
            $return = Script::new()
                            ->setJavascriptWrapper(EnumJavascriptWrappers::window)
                            ->setContent('$(function () {
                $(\'[data-tooltip="tooltip"]\').tooltip();
            })')
                            ->render();
        }
        if ($this->use_icon) {
            // Tooltip should use a separate icon
            $return .= $this->renderIcon();

        } else {
            // Tooltip is to be added onto the element directly. Merge tooltip data over element data
            if (empty($this->source_element)) {
                throw new OutOfBoundsException(tr('Cannot render tooltip, neither "use icon" nor a source element were specified, where one of either is required'));
            }
            $this->source_element->getData()
                                 ->merge($this->data);
        }
        if ($this->render_before) {
            return $render . $return;
        }

        return $return . $render;
    }


    /**
     * Renders the tooltip icon
     *
     * @return string
     */
    protected function renderIcon(): string
    {
        return str_replace(':data', $this->renderData(), $this->getIconHtml());
    }


    /**
     * Render the tooltip data-* entries
     *
     * @return string
     */
    protected function renderData(): string
    {
        $return = [];
        foreach ($this->data as $key => $value) {
            // Build data string
            if (is_array($value)) {
                $value = '"' . implode(' ', $value) . '"';

            } elseif (is_bool($value)) {
                $value = Strings::fromBoolean($value);

            } else {
                $value = '"' . htmlentities($value) . '"';
            }
            $return[] = 'data-' . $key . '=' . $value;
        }

        return implode(' ', $return);
    }


    /**
     * Returns the HTML for the tooltip icon, if used
     *
     * @return string|null
     */
    public function getIconHtml(): ?string
    {
        return $this->icon_html ?? '<div class="tooltip-icon circle" :data>?</div>';
    }


    /**
     * Sets the HTML for the tooltip icon, if used
     *
     * @param string|null $icon_html
     *
     * @return static
     */
    public function setIconHtml(?string $icon_html): static
    {
        $this->icon_html = $icon_html;

        return $this;
    }
}
