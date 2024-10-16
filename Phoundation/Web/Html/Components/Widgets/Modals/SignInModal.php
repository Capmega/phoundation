<?php

/**
 * SignInModal class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Modals;

use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Forms\SignInForm;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Html\Layouts\GridColumn;
use Phoundation\Web\Html\Layouts\GridRow;
use Phoundation\Web\Http\Url;


class SignInModal extends Modal
{
    /**
     * SignInModal class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        $this->setContent(SignInForm::new());
        parent::__construct($content);
    }


    /**
     * Render the HTML for this sign-in modal
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // Build the form
        $form = $this->form->render();

        // Build the layout
        $layout = Grid::new()
                      ->addGridRow(GridRow::new()
                                      ->addGridColumn(GridColumn::new()
                                                                ->setSize(EnumDisplaySize::three))
                                      ->addGridColumn(GridColumn::new()
                                                                ->setSize(EnumDisplaySize::six)
                                                                ->setContent($form))
                                      ->addGridColumn(GridColumn::new()
                                                                ->setSize(EnumDisplaySize::three)));

        // Set defaults
        $this->setId('signinModal')
             ->setSize('lg')
             ->setTitle(tr('Sign in'))
             ->setContent($layout);

        // Render the sign in modal.
        return parent::render() . Script::new()
                ->setContent('
                    $("form#form-sign-in").submit(function(e) {
                        e.stopPropagation();
                        
                        $.post("' . Url::getAjax('sign-in') . '", $(this).serialize())
                            .done(function (data, textStatus, jqXHR) {
                                $(".image-menu").replaceWith(data.html);
                                $("#signinModal").modal("hide");                     
                            });
                            
                        return false;
                    })')
                ->render();
    }
}
