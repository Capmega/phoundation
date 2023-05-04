<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Modals;

use Phoundation\Core\Config;
use Phoundation\Web\Http\Html\Components\Modal;
use Phoundation\Web\Http\Html\Components\Script;
use Phoundation\Web\Http\Html\Forms\SignInForm;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Html\Layouts\GridColumn;
use Phoundation\Web\Http\Html\Layouts\GridRow;
use Phoundation\Web\Http\UrlBuilder;


/**
 * MetaModal class
 *
 * 
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class MetaModal extends Modal
{
    /**
     * SignInModal class constructor
     */
    public function __construct()
    {
        $this->setContent('Hello!');
        parent::__construct();
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
            ->addRow(GridRow::new()
                ->addColumn(GridColumn::new()->setSize(3))
                ->addColumn(GridColumn::new()->setSize(6)->setContent($form))
                ->addColumn(GridColumn::new()->setSize(3))
            );

        // Set defaults
        $this->setId('MetaModal')
            ->setSize('lg')
            ->setTitle(tr('Audit information'))
            ->setContent($layout->render());

        // Render the sign in modal.
        return parent::render() . Script::new()
            ->setContent('
            $("table.showmeta").click(function(e) {
                e.stopPropagation();

                $.get("' . UrlBuilder::getAjax('system/meta/') . '" + id + ".html")
                    .done(function (data, textStatus, jqXHR) {
                        $("#MetaModal").find("").innerHtml(data.html);                     
                    });
                // Load the meta information here                
                    
                return false;
            })')->render();
    }
}