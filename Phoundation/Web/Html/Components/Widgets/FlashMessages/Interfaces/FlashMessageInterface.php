<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces;

use Phoundation\Content\Images\Interfaces\ImageInterface;
use Phoundation\Web\Html\Components\Interfaces\ElementsBlockInterface;


/**
 * interface FlashMessageInterface
 *
 * This class contains a single Flash message and can render it to HTML
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
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
     * @return $this
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
     * @return $this
     */
    public function setTitle(?string $title, bool $make_safe = true): static;

    /**
     * Returns the flash message subtitle
     *
     * @return string
     */
    public function getSubTitle(): string;

    /**
     * Sets the flash message subtitle
     *
     * @param string $sub_title
     *
     * @return $this
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
     * @return $this
     */
    public function setIcon(?string $icon): static;

    /**
     * Returns the flash image contents
     *
     * @return ImageInterface
     */
    public function getImage(): ImageInterface;

    /**
     * Sets the flash image contents
     *
     * @param ImageInterface|string|null $image
     * @param string|null                $alt
     *
     * @return $this
     */
    public function setImage(ImageInterface|string|null $image, ?string $alt = null): static;

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
     * @return $this
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
     * @return $this
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
     * @return $this
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
     * @return $this
     */
    public function setCanClose(bool $can_close): static;

    /**
     * Renders and returns the HTML for this flash message
     *
     * @return string|null
     */
    public function render(): ?string;

    /**
     * Renders and returns the HTML for this flash message without javascript tags
     *
     * @return string|null
     */
    public function renderBare(): ?string;

    /**
     * Import the flash message object data from the specified array
     *
     * @param array $source
     *
     * @return $this
     */
    public function import(array $source): static;

    /**
     * Export this flash message object to an array
     *
     * @return array
     */
    public function export(): array;
}
