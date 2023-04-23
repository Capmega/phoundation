<?php

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Button;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Enums\ButtonType;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;
use Plugins\AgnesAzzolino\Mathnstuff\Quizzes\Quizzes;
use Plugins\JavaScriptCopy\JavaScriptCopy;

// Validate POST and submit
if (Page::isPostRequestMethod()) {
    try {
        PostValidator::new();

        // Update hawb
        $quiz = Hawb::get($_GET['id']);
        $quiz->modify($_POST);
        $quiz->save();

        // Go back to where we came from
// TODO Implement timers
//showdie(Timers::get('query'));

        Page::getFlashMessages()->add(tr('Success'), tr('Hawb ":hawb" has been updated', [':hawb' => $quiz->getCode()]), 'success');
        Page::redirect('referer');

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->add($e);
        $quiz->modify($_POST);
    }
}


// Build the roles list management section
$quizzes = Quizzes::new();

if ($quizzes->getCount()) {
    $copy         = '';
    $quizzes_card = '';

    foreach ($quizzes as $quiz) {
        $copy .= JavaScriptCopy::new()
            ->setTarget('.content-' . $quiz->getId())
            ->setSelector('.copy-' . $quiz->getId())
            ->render();

        $quizzes_card .= Card::new()
            ->setMode(DisplayMode::info)
            ->setHasCollapseSwitch(true)
            ->setTitle(tr('Quiz results from ":email"', [':email' => $quiz->getEmail()]))
            ->setContent($quiz->getHtmlForm())
            ->setButtons()
            ->render();
    }
}


// Build relevant links
$relevant = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Relevant links'))
    ->setHasCollapseSwitch(true)
    ->setContent('<a href="' . UrlBuilder::getWww('/business/customers.html') . '">' . tr('Customers management') . '</a><br>
                         <a href="' . UrlBuilder::getWww('/business/providers.html') . '">' . tr('Providers management') . '</a><br>');


// Build documentation
$documentation = Card::new()
    ->setMode(DisplayMode::info)
    ->setTitle(tr('Documentation'))
    ->setHasCollapseSwitch(true)
    ->setContent('<p>Soluta a rerum quia est blanditiis ipsam ut libero. Pariatur est ut qui itaque dolor nihil illo quae. Asperiores ut corporis et explicabo et. Velit perspiciatis sunt dicta maxime id nam aliquid repudiandae. Et id quod tempore.</p>
                         <p>Debitis pariatur tempora quia dolores minus sint repellendus accusantium. Ipsam hic molestiae vel beatae modi et. Voluptate suscipit nisi fugit vel. Animi suscipit suscipit est excepturi est eos.</p>
                         <p>Et molestias aut vitae et autem distinctio. Molestiae quod ullam a. Fugiat veniam dignissimos rem repudiandae consequuntur voluptatem. Enim dolores sunt unde sit dicta animi quod. Nesciunt nisi non ea sequi aut. Suscipit aperiam amet fugit facere dolorem qui deserunt.</p>');


// Build and render the grid
// Build the grid column with a form containing the hawb and roles cards
$column = GridColumn::new()
    ->addContent($quizzes_card, 9)
    ->useForm(true);

$grid = Grid::new()
    ->addColumn($column)
    ->addColumn($relevant->render() . $documentation->render(), 3);

echo $grid->render();

// Set page meta data
Page::setHeaderTitle(tr('Quizzes'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/' => tr('Home'),
    ''  => tr('Quizzes')
]));
