<?php

namespace Phoundation\Web\Http\Html\Components\FlashMessages;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\ElementsBlock;



/**
 * Class FlashMessage
 *
 * This class contains a single Flash message and can render it to HTML
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class FlashMessage extends ElementsBlock
{
    /**
     * The type of flash message
     *
     * @var string
     */
    #[ExpectedValues(values: ['info', 'success', 'warning', 'danger'])]
    protected string $type;

    /**
     * If specified, the user can manually close the flash message
     *
     * @var bool $can_close
     */
    protected bool $can_close = true;

    /**
     * If specified will auto close the flash message in $auto_close seconds
     *
     * @var int|null $auto_close
     */
    protected ?int $auto_close = null;

    /**
     * If specified will display an icon in the flash message
     *
     * @var string|null $icon
     */
    protected ?string $icon = null;

    /**
     * Image to show with the flash message
     *
     * @var string|null $image
     */
    protected ?string $image = null;

    /**
     * If true, show the flash message on the top, else on the bottom
     *
     * @var bool $top
     */
    protected bool $top = true;

    /**
     * If true, show the flash message on the left, else on the right
     *
     * @var bool $left
     */
    protected bool $left = false;



    /**
     * Returns the flash message type
     *
     * @return string
     */
    #[ExpectedValues(values: ['info', 'success', 'warning', 'danger'])]
    public function getType(): string
    {
        return $this->type;
    }



    /**
     * Sets the flash message type
     *
     * @param string $type
     * @return $this
     */
    public function setType(#[ExpectedValues(values: ['info', 'information', 'success', 'warning', 'danger', 'error', 'exception', 'blue', 'green', 'yellow', 'red'])] string $type): static
    {
        switch ($type) {
            case 'blue':
                // no break
            case 'info':
                // no break
            case 'information':
                $type = 'info';
                break;

            case 'green':
                // no break
            case 'success':
                $type = 'success';
                break;

            case 'yellow':
                // no break
            case 'warning':
                $type = 'warning';
                break;

            case 'red':
                // no break
            case 'error':
                // no break
            case 'exception':
                // no break
            case 'danger':
                $type = 'danger';
                break;

            default:
                throw new OutOfBoundsException(tr('Unknown flash message type ":type" specified', [
                    ':type' => $type
                ]));
        }

        $this->type = $type;

        return $this;
    }



    /**
     * Returns the flash message contents
     *
     * @return string
     */
    public function getMessage(): string
    {
        return $this->content;
    }



    /**
     * Sets the flash message contents
     *
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): static
    {
        $this->content = $message;
        return $this;
    }



    /**
     * Returns the flash icon contents
     *
     * @return string|null
     */
    public function getIcon(): ?string
    {
        return $this->icon;
    }



    /**
     * Sets the flash icon contents
     *
     * @param string|null $icon
     * @return $this
     */
    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }



    /**
     * Returns the flash image contents
     *
     * @return string
     */
    public function getImage(): string
    {
        return $this->image;
    }



    /**
     * Sets the flash image contents
     *
     * @param string $image
     * @return $this
     */
    public function setImage(string $image): static
    {
        $this->image = $image;
        return $this;
    }



    /**
     * Returns if the flash message is shown on the left side of the screen
     *
     * @return string
     */
    public function getLeft(): string
    {
        return $this->left;
    }



    /**
     * Sets if the flash message is shown on the right side of the screen
     *
     * @param string $left
     * @return $this
     */
    public function setLeft(string $left): static
    {
        $this->left = $left;
        return $this;
    }



    /**
     * Returns if the flash message is shown at the top of the screen
     *
     * @return string
     */
    public function getTop(): string
    {
        return $this->top;
    }



    /**
     * Sets if the flash message is shown at the top of the screen
     *
     * @param string $top
     * @return $this
     */
    public function setTop(string $top): static
    {
        $this->top = $top;
        return $this;
    }



    /**
     * Returns if the flash message will close automatically after N seconds
     *
     * @return int|null
     */
    public function getAutoClose(): ?int
    {
        return $this->auto_close;
    }



    /**
     * Sets if the flash message will close automatically after N seconds
     *
     * @param int|null $auto_close
     * @return $this
     */
    public function setAutoClose(?int $auto_close): static
    {
        $this->auto_close = get_null($auto_close);
        return $this;
    }



    /**
     * Returns if the flash message can be closed
     *
     * @return bool
     */
    public function getCanClose(): bool
    {
        return $this->can_close;
    }



    /**
     * Sets if the flash message can be closed
     *
     * @param bool $can_close
     * @return $this
     */
    public function setCanClose(bool $can_close): static
    {
        $this->can_close = $can_close;
        return $this;
    }



    /**
     * Renders and returns the HTML for this flash message
     *
     * @return string|null
     */
    public function render(): ?string
    {

    }
}