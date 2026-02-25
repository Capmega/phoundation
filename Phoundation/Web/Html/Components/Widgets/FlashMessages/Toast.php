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

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataFlashMessageObject;
use Phoundation\Data\Traits\TraitMethodHasRendered;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces\FlashMessageInterface;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces\ToastInterface;
use Phoundation\Web\Html\Components\Widgets\WidgetCore;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Requests\Request;


class Toast extends WidgetCore implements ToastInterface
{
    use TraitMethodHasRendered;
    use TraitDataFlashMessageObject;


    /**
     * Toast class constructor
     *
     * @param FlashMessageInterface $_message
     */
    public function __construct(FlashMessageInterface $_message)
    {
        parent::__construct();
        $this->_message = $_message;
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
     * @param FlashMessageInterface $_message
     *
     * @return static
     */
    public static function new(FlashMessageInterface $_message): static
    {
        return new static($_message);
    }


    /**
     * Renders and returns the HTML and JavaScript to display a toast
     *
     * @todo renderArray functions should also be handled by Template libraries as they, well, render the object for use in a Template! There are template specific items in this method already!
     * @return array
     */
    public function renderArray(): array
    {
        if (empty($this->_message)) {
            throw new OutOfBoundsException(tr('Cannot render Toast object, no FlashMessage object specified'));
        }

        $_message = $this->_message;
        $image     = $_message->getImage()?->getImgObject();

        if ($_message->getTop()) {
            if ($_message->getLeft()) {
                $position = 'topLeft';

            } else {
                $position = 'topRight';
            }

        } else {
            if ($_message->getLeft()) {
                $position = 'bottomLeft';

            } else {
                $position = 'bottomRight';
            }
        }

        // This is template-specific handling, should be in a Template library
        switch (Request::getTemplateObject()->getSeoName()) {
            case 'mdb':
                $return = [
                    'class'    => $_message->getMode()->value,
                    'title'    => Html::safe($_message->getTitle()),
                    'subtitle' => Html::safe($_message->getSubTitle()),
                    'position' => Strings::fromCamelcaseToCharacterSeparated($position, '-'),
                    'body'     => Html::safe($_message->getContent()),
                    'template' => Request::getTemplateObject()->getSeoName(),
                ];

                break;

            case 'adminlte':
                $return = [
                    'class'    => 'bg-' . $_message->getMode()->value,
                    'title'    => Html::safe($_message->getTitle()),
                    'subtitle' => Html::safe($_message->getSubTitle()),
                    'position' => $position,
                    'body'     => Html::safe($_message->getContent()),
                    'template' => Request::getTemplateObject()->getSeoName(),
                ];

                break;

            default:
                throw new OutOfBoundsException(tr('Cannot render Toast object, unsupported template ":template"', [
                    ':template' => Request::getTemplateObject()->getSeoName()
                ]));
    }

        if ($image) {
            $return['image']     = Html::safe($image->getSrc());
            $return['image-alt'] = Html::safe($image->getAlt());
        }

        if ($_message->getIcon()) {
            $return['icon'] = 'fas fa-' . Html::safe($_message->getIcon()) . ' fa-lg';
        }

        if ($_message->getAutoClose()) {
            $return['autohide'] = true;
            $return['delay']    = $_message->getAutoClose();
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
