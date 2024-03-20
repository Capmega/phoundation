<?php

declare(strict_types=1);

use Phoundation\Core\Plugins\FilterForm;
use Phoundation\Core\Plugins\Plugins;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Html\Components\Buttons\Buttons;
use Phoundation\Web\Html\Components\Widgets\BreadCrumbs;
use Phoundation\Web\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumDisplaySize;
use Phoundation\Web\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Response;


// Button clicked?
if (Request::isPostRequestMethod()) {
    try {
        // Process buttons
        switch (PostValidator::getSubmitButton()) {
            case tr('Scan'):
                $count = Plugins::scan();
                Response::getFlashMessages()->addSuccessMessage(tr('Finished scanning for libraries, found and registered ":count" new libraries', [':count' => $count]));
                Response::redirect('this');
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Response::getFlashMessages()->addMessage($e);
    }
}


// Build the page content


// Build plugins filter card
$filters_content = FilterForm::new();

$filters = Card::new()
    ->setCollapseSwitch(true)
    ->setTitle('Plugins filters')
    ->setContent($filters_content->render())
    ->useForm(true);


// Build plugins table
$table = Plugins::new()->getHtmlDataTable()
    ->setRowUrl('/development/plugin+:ROW.html');

$plugins = Card::new()
    ->setTitle('Available plugins')
    ->setSwitches('reload')
    ->setContent($table->render())
    ->useForm(true)
    ->setButtons(Buttons::new()->addButton(tr('Scan')));

$plugins->getForm()
        ->setAction(UrlBuilder::getCurrent())
        ->setMethod('POST');


// Build relevant links
$relevant = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/development/slow-pages.html') . '">' . tr('Slow pages') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/security/security.html') . '">' . tr('Security management') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(EnumDisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the page grid
$grid = Grid::new()
    ->addColumn($filters->render() . $plugins->render(), EnumDisplaySize::nine)
    ->addColumn($relevant->render() . $documentation->render(), EnumDisplaySize::three);

echo $grid->render();


// Set page meta data
Response::setHeaderTitle(tr('Plugins'));
Response::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Plugins')
]));
