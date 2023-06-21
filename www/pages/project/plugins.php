<?php

declare(strict_types=1);


use Phoundation\Core\Plugins\Plugins;
use Phoundation\Core\Plugins\FilterForm;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Enums\DisplaySize;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


// Button clicked?
if (Page::isPostRequestMethod()) {
    try {
        // Process buttons
        switch (PostValidator::getSubmitButton()) {
            case tr('Scan'):
                $count = Plugins::scan();
                Page::getFlashMessages()->addMessage(tr('Success'), tr('Finished scanning for libraries, found and registered ":count" new libraries', [':count' => $count]), DisplayMode::success);
                Page::redirect('this');
        }

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->addMessage($e);
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
    ->setRowUrl('/project/plugin-:ROW.html');

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
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setContent('<a href="' . UrlBuilder::getWww('/development/slow-pages.html') . '">' . tr('Slow pages') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/security/security.html') . '">' . tr('Security management') . '</a>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.');


// Build and render the grid
$grid = Grid::new()
    ->addColumn($filters->render() . $plugins->render(), DisplaySize::nine)
    ->addColumn($relevant->render() . $documentation->render(), DisplaySize::three);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('Development plugins'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Plugins')
]));
