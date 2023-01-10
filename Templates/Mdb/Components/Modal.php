<?php

namespace Templates\Mdb\Components;

use Phoundation\Core\Strings;
use Phoundation\Developer\Debug;
use Phoundation\Exception\OutOfBoundsException;



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
class Modal extends \Phoundation\Web\Http\Html\Components\Modal
{
   /**
     * Render the modal
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->id) {
            throw new OutOfBoundsException(tr('Cannot render modal, no "id" specified'));
        }

        return '<div class="modal' . ($this->fade ? ' fade' : null) . '" id="' . $this->id . '" tabindex="' . $this->getTabIndex() . '" aria-labelledby="' . $this->id . 'Label" aria-hidden="true" data-mdb-keyboard="' . ($this->escape ? 'false' : 'true') . '" data-mdb-backdrop="' . ($this->backdrop === null ? 'static' : Strings::boolean($this->backdrop)) . '">
                    <div class="modal-dialog' . ($this->size ? ' modal-' . $this->size : null) . ($this->vertical_center ? ' modal-dialog-centered' : null) . '">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="' . $this->id . 'Label">' . $this->title . '</h5>
                                <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="' . tr('Close') . '"></button>
                            </div>
                            <div class="modal-body">' . $this->content . '</div>
                            <div class="modal-footer">
                                ' . $this->buttons?->render() .  '
                            </div>
                        </div>
                    </div>
                </div>';
        }
}