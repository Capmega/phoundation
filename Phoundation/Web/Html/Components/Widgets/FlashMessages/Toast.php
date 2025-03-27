<?php

/**
 * Class Toast
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\FlashMessages;

use Phoundation\Data\Traits\TraitDataFlashMessageObject;
use Phoundation\Data\Traits\TraitMethodHasRendered;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces\FlashMessageInterface;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces\ToastInterface;
use Phoundation\Web\Html\Components\Widgets\WidgetCore;
use Phoundation\Web\Html\Html;


class Toast extends WidgetCore implements ToastInterface
{
    use TraitMethodHasRendered;
    use TraitDataFlashMessageObject;


    /**
     * Toast class constructor
     *
     * @param FlashMessageInterface $o_message
     */
    public function __construct(FlashMessageInterface $o_message)
    {
        parent::__construct();
        $this->o_message = $o_message;
    }


    /**
     * Returns the rendered version of this object in a string
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->render();
    }


    /**
     * Returns a new Toast object
     *
     * @param FlashMessageInterface $o_message
     *
     * @return static
     */
    public static function new(FlashMessageInterface $o_message): static
    {
        return new static($o_message);
    }


    /**
     * Renders and returns the HTML and JavaScript to display a toast
     *
     * @return string|null
     */
    public function render(): ?string
    {
        return parent::render();
    }


    /**
     * Renders and returns the HTML and JavaScript to display a toast
     *
     * @return array
     */
    public function renderArray(): array
    {
        if (empty($this->o_message)) {
            throw new OutOfBoundsException(tr('Cannot render Toast object, no FlashMessage object specified'));
        }

        $o_message = $this->o_message;
        $image     = $o_message->getImage()?->getImgObject();

        if ($o_message->getTop()) {
            if ($o_message->getLeft()) {
                $position = 'topLeft';

            } else {
                $position = 'topRight';
            }

        } else {
            if ($o_message->getLeft()) {
                $position = 'bottomLeft';

            } else {
                $position = 'bottomRight';
            }
        }

        $return = [
            'class'    => 'bg-' . $o_message->getMode()->value,
            'title'    => Html::safe($o_message->getTitle()),
            'subtitle' => Html::safe($o_message->getSubTitle()),
            'position' => $position,
            'body'     => Html::safe($o_message->getContent())
        ];

        if ($image) {
            $return['image']     = Html::safe($image->getSrc());
            $return['image-alt'] = Html::safe($image->getAlt());
        }

        if ($o_message->getIcon()) {
            $return['icon'] = 'fas fa-' . Html::safe($o_message->getIcon()) . ' fa-lg';
        }

        if ($o_message->getAutoClose()) {
            $return['autohide'] = true;
            $return['delay']    = $o_message->getAutoClose();
        }

        return $return;
    }


    /**
     * Renders and returns the HTML and JavaScript to display a toast
     *
     * @return string|null
     */
    public function renderJson(): ?string
    {
        return Json::encode($this->renderArray());
    }
}
