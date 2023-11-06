<?php

declare(strict_types=1);


namespace Templates\AdminLte\Html\Components;

use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Html\Renderer;


/**
 * AdminLte Plugin Modal class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class Modal extends Renderer
{
    /**
     * Modal class constructor
     */
    public function __construct(\Phoundation\Web\Html\Components\Modals\Modal $element)
    {
        parent::__construct($element);
    }


    /**
     * Render the modal
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->render_object->getId()) {
            throw new OutOfBoundsException(tr('Cannot render modal, no "id" specified'));
        }

        $this->render =  '  <div class="modal' . ($this->render_object->getFade() ? ' fade' : null) . '" id="' . Html::safe($this->render_object->getId()) . '" tabindex="' . Html::safe($this->render_object->getTabIndex()) . '" aria-labelledby="' . Html::safe($this->render_object->getId()) . 'Label" aria-hidden="true" data-mdb-keyboard="' . ($this->render_object->getEscape() ? 'false' : 'true') . '" data-mdb-backdrop="' . ($this->render_object->getBackdrop() === null ? 'static' : Strings::fromBoolean($this->render_object->getBackdrop())) . '">
                                <div class="modal-dialog' . ($this->render_object->getTier()->value ? ' modal-' . Html::safe($this->render_object->getTier()->value) : null) . ($this->render_object->getVerticalCenter() ? ' modal-dialog-centered' : null) . '">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="' . Html::safe($this->render_object->getId()) . 'Label">' . Html::safe($this->render_object->getTitle()) . '</h5>
                                            <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="' . tr('Close') . '"></button>
                                        </div>
                                        <div class="modal-body">' . $this->render_object->getContent() . '</div>
                                        <div class="modal-footer">
                                            ' . $this->render_object->getButtons()?->render() .  '
                                        </div>
                                    </div>
                                </div>
                            </div>';

        return parent::render();
    }
}