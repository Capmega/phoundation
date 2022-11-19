<?php

namespace Plugins\Mdb\Components;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\ElementsBlock;
use Plugins\Mdb\Elements\Buttons;



/**
 * MDB Plugin Modal class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Plugins\Mdb
 */
class Modal extends ElementsBlock
{
    /**
     * The Modal identifier
     *
     * @var string|null
     */
    protected ?string $id = null;

    /**
     * The text in the modal title bar
     *
     * @var string|null
     */
    protected ?string $title = null;

    /**
     * The bottom buttons content for this modal
     *
     * @var Buttons|null
     */
    protected ?Buttons $buttons = null;



    /**
     * Returns the modal identifier
     *
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }


    /**
     * Sets the modal identifier
     *
     * @param string|null $id
     * @return $this
     */
    public function setId(?string $id): static
    {
        $this->id = $id;
        return $this;
    }



    /**
     * Returns the modal title
     *
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }



    /**
     * Sets the modal title
     *
     * @param string|null $title
     * @return $this
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }



    /**
     * Returns the modal buttons
     *
     * @return Buttons|null
     */
    public function getButtons(): ?Buttons
    {
        return $this->buttons;
    }



    /**
     * Sets the modal buttons
     *
     * @param Buttons|null $buttons
     * @return $this
     */
    public function setButtons(?Buttons $buttons): static
    {
        $this->buttons = $buttons;
        return $this;
    }



    /**
     * Render the modal
     *
     * @return string
     */
    public function render(): string
    {
        if (!$this->id) {
            throw new OutOfBoundsException(tr('Cannot render modal, no "id" specified'));
        }

        return  '<div class="modal fade" id="' . $this->id . '" tabindex="' . $this->getTabIndex() . '" aria-labelledby="' . $this->id . 'Label" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="' . $this->id . 'Label">' . $this->title . '</h5>
                                <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close"></button>
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