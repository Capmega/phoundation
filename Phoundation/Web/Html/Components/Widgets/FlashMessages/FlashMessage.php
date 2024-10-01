<?php

/**
 * Class FlashMessage
 *
 * This class contains a single Flash message and can render it to HTML
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\FlashMessages;

use PDOStatement;
use Phoundation\Content\Images\ImageFile;
use Phoundation\Content\Images\Interfaces\ImageFileInterface;
use Phoundation\Core\Sessions\SessionConfig;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataTitle;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Config;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\ElementsBlock;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces\FlashMessageInterface;
use Phoundation\Web\Html\Traits\TraitMode;


class FlashMessage extends ElementsBlock implements FlashMessageInterface
{
    use TraitMode;
    use TraitDataTitle;


    /**
     * Subtitle of the flash message
     *
     * @var string|null $sub_title
     */
    protected ?string $sub_title = null;

    /**
     * If specified, the user can manually close the flash message
     *
     * @var bool $can_close
     */
    protected bool $can_close = true;

    /**
     * If specified, will auto close the flash message in $auto_close milliseconds
     *
     * @var int|null $auto_close
     */
    protected ?int $auto_close = null;

    /**
     * If specified, will display an icon in the flash message
     *
     * @var string|null $icon
     */
    protected ?string $icon = null;

    /**
     * Image to show with the flash message
     *
     * @var ImageFileInterface|null $image
     */
    protected ?ImageFileInterface $image = null;

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
     * Tracks the library that handles flash messages
     *
     * @var string $flash_handler
     */
    protected string $flash_handler = 'toast';


    /**
     * FlashMessage class constructor
     *
     * @param IteratorInterface|array|string|PDOStatement|null $source
     */
    public function __construct(IteratorInterface|array|string|PDOStatement|null $source = null)
    {
        parent::__construct($source);

        // Set default auto close for flash messages
        $this->setAutoClose(get_null(SessionConfig::getInteger('web.feedback.messages.auto-close', 15000)));
    }


    /**
     * Returns the library that handles flash messages
     *
     * @return string
     */
    public function getFlashHandler(): string
    {
        return $this->flash_handler;
    }


    /**
     * Sets the library that handles flash messages
     *
     * @param string $flash_handler
     *
     * @return static
     */
    public function setFlashHandler(string $flash_handler): static
    {
        $this->flash_handler = $flash_handler;

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
     *
     * @return static
     */
    public function setMessage(string $message): static
    {
        $this->content = $message;

        return $this;
    }


    /**
     * Returns the flash message subtitle
     *
     * @return string|null
     */
    public function getSubTitle(): ?string
    {
        return $this->sub_title;
    }


    /**
     * Sets the flash message subtitle
     *
     * @param string $sub_title
     *
     * @return static
     */
    public function setSubTitle(string $sub_title): static
    {
        $this->sub_title = $sub_title;

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
     *
     * @return static
     */
    public function setIcon(?string $icon): static
    {
        if ($icon) {
            if ($this->image) {
                throw new OutOfBoundsException(tr('Cannot specify icon for flash message, an image was already set'));
            }
        }

        $this->icon = get_null($icon);

        return $this;
    }


    /**
     * Returns the flash image contents
     *
     * @return ImageFileInterface|null
     */
    public function getImage(): ?ImageFileInterface
    {
        return $this->image;
    }


    /**
     * Sets the flash image contents
     *
     * @param ImageFileInterface|string|null $image
     * @param string|null                    $alt
     *
     * @return static
     */
    public function setImage(ImageFileInterface|string|null $image, ?string $alt = null): static
    {
        if ($image) {
            if ($this->icon) {
                throw new OutOfBoundsException(tr('Cannot specify image for flash message, an icon was already set'));
            }

            if (is_string($image)) {
                // image was specified as a string, make an image object
                $image = ImageFile::new($image)->setDescription($image);
            }
        }

        $this->image = get_null($image);

        return $this;
    }


    /**
     * Returns if the flash message is shown on the left side of the screen
     *
     * @return bool
     */
    public function getLeft(): bool
    {
        return $this->left;
    }


    /**
     * Sets if the flash message is shown on the right side of the screen
     *
     * @param bool $left
     *
     * @return static
     */
    public function setLeft(bool $left): static
    {
        $this->left = $left;

        return $this;
    }


    /**
     * Returns if the flash message is shown at the top of the screen
     *
     * @return bool
     */
    public function getTop(): bool
    {
        return $this->top;
    }


    /**
     * Sets if the flash message is shown at the top of the screen
     *
     * @param bool $top
     *
     * @return static
     */
    public function setTop(bool $top): static
    {
        $this->top = $top;

        return $this;
    }


    /**
     * Returns if the flash message closes automatically after N milliseconds
     *
     * @return int|null
     */
    public function getAutoClose(): ?int
    {
        return $this->auto_close;
    }


    /**
     * Sets if the flash message closes automatically after N milliseconds
     *
     * @param int|null $auto_close
     *
     * @return static
     */
    public function setAutoClose(?int $auto_close): static
    {
        $auto_close = get_null($auto_close);

        if ($auto_close) {
            if ($auto_close < 500) {
                // Assume that time was specified in seconds instead of milliseconds, automatically correct this issue
                $auto_close *= 1000;
            }
        }

        $this->auto_close = $auto_close;

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
     *
     * @return static
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
        $this->render = Script::new()
                              ->setContent($this->renderBare())
                              ->render();

        return parent::render();
    }


    /**
     * Renders and returns the javascript for this flash message without javascript tags
     *
     * @return string|null
     */
    public function renderBare(): ?string
    {
        return match ($this->flash_handler) {
            'toast' => Toast::new($this)->render(),
        };
    }


    /**
     * Renders and returns the configuration for this flash message without javascript tags or calls
     *
     * @return string|null
     */
    public function renderJson(): ?string
    {
        return match ($this->flash_handler) {
            'toast' => Toast::new($this)->renderConfiguration(),
        };
    }


    /**
     * Import the flash message object data from the specified array
     *
     * @param array $source
     *
     * @return static
     */
    public function import(array $source): static
    {
        foreach ($source as $key => $value) {
            match ($key) {
                'top'        => $this->top        = $value,
                'left'       => $this->left       = $value,
                'mode'       => $this->mode       = $this->mode::from($value),
                'icon'       => $this->icon       = $value,
                'image'      => $this->image      = $value,
                'title'      => $this->title      = $value,
                'message'    => $this->content    = $value,
                'can_close'  => $this->can_close  = $value,
                'auto_close' => $this->auto_close = $value,
            };
        }

        return $this;
    }


    /**
     * Export this flash message object to an array
     *
     * @return array
     */
    public function export(): array
    {
        return [
            'top'        => $this->top,
            'left'       => $this->left,
            'mode'       => $this->mode->value,
            'icon'       => $this->icon,
            'image'      => $this->image,
            'title'      => $this->title,
            'message'    => $this->content,
            'can_close'  => $this->can_close,
            'auto_close' => $this->auto_close,
        ];
    }

}
