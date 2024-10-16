<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces;

use Phoundation\Content\Images\Interfaces\ImageFileInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\Toast;
use Phoundation\Web\Html\Enums\EnumAttachJavascript;


interface FlashMessageInterface extends ElementsBlockInterface
{
    /**
     * Returns the flash message contents
     *
     * @return string
     */
    public function getMessage(): string;


    /**
     * Sets the flash message contents
     *
     * @param string $message
     *
     * @return static
     */
    public function setMessage(string $message): static;


    /**
     * Returns the flash message title
     *
     * @return string|null
     */
    public function getTitle(): ?string;


    /**
     * Sets the flash message title
     *
     * @param string|null $title
     * @param bool        $make_safe
     *
     * @return static
     */
    public function setTitle(?string $title, bool $make_safe = true): static;


    /**
     * Returns the flash message subtitle
     *
     * @return string|null
     */
    public function getSubTitle(): ?string;


    /**
     * Sets the flash message subtitle
     *
     * @param string $sub_title
     *
     * @return static
     */
    public function setSubTitle(string $sub_title): static;


    /**
     * Returns the flash icon contents
     *
     * @return string|null
     */
    public function getIcon(): ?string;


    /**
     * Sets the flash icon contents
     *
     * @param string|null $icon
     *
     * @return static
     */
    public function setIcon(?string $icon): static;


    /**
     * Returns the flash image contents
     *
     * @return ImageFileInterface|null
     */
    public function getImage(): ?ImageFileInterface;


    /**
     * Sets the flash image contents
     *
     * @param ImageFileInterface|string|null $image
     * @param string|null                    $alt
     *
     * @return static
     */
    public function setImage(ImageFileInterface|string|null $image, ?string $alt = null): static;


    /**
     * Returns if the flash message is shown on the left side of the screen
     *
     * @return bool
     */
    public function getLeft(): bool;


    /**
     * Sets if the flash message is shown on the right side of the screen
     *
     * @param bool $left
     *
     * @return static
     */
    public function setLeft(bool $left): static;


    /**
     * Returns if the flash message is shown at the top of the screen
     *
     * @return bool
     */
    public function getTop(): bool;


    /**
     * Sets if the flash message is shown at the top of the screen
     *
     * @param bool $top
     *
     * @return static
     */
    public function setTop(bool $top): static;


    /**
     * Returns if the flash message will close automatically after N milliseconds
     *
     * @return int|null
     */
    public function getAutoClose(): ?int;


    /**
     * Sets if the flash message will close automatically after N milliseconds
     *
     * @param int|null $auto_close
     *
     * @return static
     */
    public function setAutoClose(?int $auto_close): static;


    /**
     * Returns if the flash message can be closed
     *
     * @return bool
     */
    public function getCanClose(): bool;


    /**
     * Sets if the flash message can be closed
     *
     * @param bool $can_close
     *
     * @return static
     */
    public function setCanClose(bool $can_close): static;


    /**
     * Renders and returns the HTML for this flash message
     *
     * @param EnumAttachJavascript $attach_javascript Specified where to attach the data, either in the HTML header,
     *                                                HTML footer, or "here" which will return the rendered string
     *
     * @return string|null
     */
    public function render(EnumAttachJavascript $attach_javascript = EnumAttachJavascript::footer): ?string;


    /**
     * Renders and returns the configuration for this flash message without JavaScript tags or calls
     *
     * @return string|null
     */
    public function renderJson(): ?string;


    /**
     * Renders and returns the configuration for this flash message without JavaScript tags or calls
     *
     * @return array
     */
    public function renderArray(): array;


    /**
     * Import the flash message object data from the specified array
     *
     * @param array $source
     *
     * @return static
     */
    public function import(array $source): static;


    /**
     * Export this flash message object to an array
     *
     * @return array
     */
    public function export(): array;
}
