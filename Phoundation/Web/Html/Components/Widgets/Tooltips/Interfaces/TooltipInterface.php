<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Tooltips\Interfaces;

use Phoundation\Web\Html\Components\Interfaces\ElementInterface;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Enums\Interfaces\EnumTooltipBoundaryInterface;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Enums\Interfaces\EnumTooltipPlacementInterface;
use Phoundation\Web\Html\Components\Widgets\Tooltips\Enums\Interfaces\EnumTooltipTriggerInterface;

interface TooltipInterface extends ElementInterface
{
    /**
     * Returns the tooltip trigger for this element
     *
     * @return array
     */
    public function getTriggers(): array;


    /**
     * Sets the tooltip trigger for this element
     *
     * @param EnumTooltipTriggerInterface ...$triggers
     *
     * @return static
     */
    public function setTriggers(EnumTooltipTriggerInterface ...$triggers): static;


    /**
     * Returns the tooltip title for this element
     *
     * @return string|null
     */
    public function getTitle(): ?string;


    /**
     * Sets the tooltip title for this element
     *
     * @param string|null $title
     *
     * @return static
     */
    public function setTitle(?string $title): static;


    /**
     * Returns if CSS fade transition will be applied to the tooltip
     *
     * @return bool
     */
    public function getAnimation(): bool;


    /**
     * Sets if CSS fade transition will be applied to the tooltip
     *
     * @param bool $animation
     *
     * @return static
     */
    public function setAnimation(bool $animation): static;


    /**
     * Returns if tooltip should be added on a separate icon
     *
     * @return bool
     */
    public function getUseIcon(): bool;


    /**
     * Sets if tooltip should be added on a separate icon
     *
     * @param bool $use_icon
     *
     * @return static
     */
    public function setUseIcon(bool $use_icon): static;


    /**
     * Returns if tooltip should be added on a separate icon
     *
     * @return bool
     */
    public function getRenderBefore(): bool;


    /**
     * Sets if tooltip should be added on a separate icon
     *
     * @param bool $render_before
     *
     * @return static
     */
    public function setRenderBefore(bool $render_before): static;


    /**
     * Returns the HTML for the tooltip icon, if used
     *
     * @return string|null
     */
    public function getIconHtml(): ?string;


    /**
     * Sets the HTML for the tooltip icon, if used
     *
     * @param string|null $icon_html
     *
     * @return static
     */
    public function setIconHtml(?string $icon_html): static;


    /**
     * Returns if the tooltip should be appended to the specified element
     *
     * @return string|null
     */
    public function getContainer(): ?string;


    /**
     * Sets if the tooltip should be appended to the specified element
     *
     * @param string|null $container
     *
     * @return static
     */
    public function setContainer(?string $container): static;


    /**
     * Returns the delay showing and hiding the tooltip (ms)
     *
     * @note Does not apply to manual trigger type
     * @return int|null
     */
    public function getDelay(): ?int;


    /**
     * Sets the delay showing and hiding the tooltip (ms)
     *
     * @note Does not apply to manual trigger type
     *
     * @param int|null $delay
     *
     * @return static
     */
    public function setDelay(?int $delay): static;


    /**
     * Returns if the tooltip may contain HTML or plain text
     *
     * @return bool
     */
    public function getHtml(): bool;


    /**
     * Sets if the tooltip may contain HTML or plain text
     *
     * @param bool $html
     *
     * @return static
     */
    public function setHtml(bool $html): static;


    /**
     * Returns the positioning the tooltip - auto | top | bottom | left | right.
     *
     * @note Does not apply to manual trigger type
     * @return EnumTooltipPlacementInterface
     */
    public function getPlacement(): EnumTooltipPlacementInterface;


    /**
     * Sets the positioning the tooltip - auto | top | bottom | left | right.
     *
     * @note Does not apply to manual trigger type
     *
     * @param EnumTooltipPlacementInterface $placement
     *
     * @return static
     */
    public function setPlacement(EnumTooltipPlacementInterface $placement): static;


    /**
     * Returns the Base HTML to use when creating the tooltip
     *
     * Defaults to <div class="tooltip" role="tooltip"><div class="arrow"></div><div class="tooltip-inner"></div></div>
     *
     * @return string
     */
    public function getTemplate(): string;


    /**
     * Sets the Base HTML to use when creating the tooltip
     *
     * @param string $template
     *
     * @return static
     */
    public function setTemplate(string $template): static;


    /**
     * Returns the offset of the tooltip relative to its target
     *
     * @return int|null
     */
    public function getOffset(): ?int;


    /**
     * Sets the offset of the tooltip relative to its target
     *
     * @param int|null $delay
     *
     * @return static
     */
    public function setOffset(?int $delay): static;


    /**
     * Returns the overflow constraint boundary of the tooltip
     *
     * @note Does not apply to manual trigger type
     * @return EnumTooltipBoundaryInterface|string
     */
    public function getBoundary(): EnumTooltipBoundaryInterface|string;


    /**
     * Sets the overflow constraint boundary of the tooltip
     *
     * @note Does not apply to manual trigger type
     *
     * @param EnumTooltipBoundaryInterface|string $boundary
     *
     * @return static
     */
    public function setBoundary(EnumTooltipBoundaryInterface|string $boundary): static;


    /**
     * "Renders" the tooltip by injecting data-tooltip
     *
     * @param string|null $render
     *
     * @return string|null
     */
    public function render(?string $render = null): ?string;


    /**
     * Sets the source element to which this tooltip is bound, if any
     *
     * @param ElementInterface|null $source_element
     *
     * @return static
     */
    public function setSourceElement(?ElementInterface $source_element): static;
}
