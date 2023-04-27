<?php

declare(strict_types=1);


namespace Templates\Mdb\Html\Components;

use Phoundation\Core\Strings;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Html;
use Phoundation\Web\Http\Html\Renderer;


/**
 * MDB Plugin Modal class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
 */
class Modal extends Renderer
{
    /**
     * Modal class constructor
     */
    public function __construct(\Phoundation\Web\Http\Html\Components\Modal $element)
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
        if (!$this->element->getId()) {
            throw new OutOfBoundsException(tr('Cannot render modal, no "id" specified'));
        }

        $this->render = '   <div class="modal' . ($this->element->getFade() ? ' fade' : null) . '" id="' . Html::safe($this->element->getId()) . '" tabindex="' . Html::safe($this->element->getTabIndex()) . '" aria-labelledby="' . Html::safe($this->element->getId()) . 'Label" aria-hidden="true" data-mdb-keyboard="' . Strings::boolean($this->element->getEscape()) . '" data-mdb-backdrop="' . ($this->element->getBackdrop() === null ? 'static' : Strings::boolean($this->element->getBackdrop())) . '">
                                <div class="modal-dialog' . ($this->element->getTier()->value ? ' modal-' . Html::safe($this->element->getTier()->value) : null) . ($this->element->getVerticalCenter() ? ' modal-dialog-centered' : null) . '">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="' . Html::safe($this->element->getId()) . 'Label">' . Html::safe($this->element->getTitle()) . '</h5>
                                            <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="' . tr('Close') . '"></button>
                                        </div>
                                        <div class="modal-body">' . $this->element->getContent() . '</div>
                                        <div class="modal-footer">
                                            ' . $this->element->getButtons()->render() .  '
                                        </div>
                                    </div>
                                </div>
                            </div>';

        return parent::render();
    }
}