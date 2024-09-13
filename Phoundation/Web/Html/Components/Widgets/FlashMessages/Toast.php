<?php

/**
 * Class Toast
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\FlashMessages;

use Phoundation\Data\Traits\TraitMethodHasRendered;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;

class Toast implements RenderInterface
{
    use TraitMethodHasRendered;


    /**
     * Tracks the flash message data
     *
     * @var FlashMessage $message
     */
    protected FlashMessage $message;


    /**
     * Toast class constructor
     *
     * @param FlashMessage $message
     */
    public function __construct(FlashMessage $message)
    {
        $this->message = $message;
    }


    /**
     * Returns a new Toast object
     *
     * @param FlashMessage $message
     *
     * @return static
     */
    public static function new(FlashMessage $message): static
    {
        return new static($message);
    }


    /**
     * Renders and returns the HTML and javascript to display a toast
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return '
            $(document).Toasts("create", ' . $this->renderConfiguration() . ');';
    }


    /**
     * Renders and returns the HTML and javascript to display a toast
     *
     * @return string|null
     */
    public function renderConfiguration(): ?string
    {
        $message = $this->message;
        $image   = $message->getImage()?->getImgObject();

        if ($message->getTop()) {
            if ($message->getLeft()) {
                $position = 'topLeft';

            } else {
                $position = 'topRight';
            }

        } else {
            if ($message->getLeft()) {
                $position = 'bottomLeft';

            } else {
                $position = 'bottomRight';
            }
        }

        $return = [
            'class'    => 'bg-' . $message->getMode()->value,
            'title'    => Strings::escape($message->getTitle()),
            'subtitle' => Strings::escape($message->getSubTitle()),
            'position' => $position,
            'body'     => Strings::escape($message->getContent())
        ];

        if ($image) {
            $return['image']     = Strings::escape($image->getSrc());
            $return['image-alt'] = Strings::escape($image->getAlt());
        }

        if ($message->getIcon()) {
            $return['icon'] = 'fas fa-' . Strings::escape($message->getIcon()) . ' fa-lg';
        }

        if ($message->getAutoClose()) {
            $return['autohide'] = true;
            $return['delay']    = $message->getAutoClose();
        }

        return Json::encode($return);
    }
}
