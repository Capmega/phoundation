<?php

use Phoundation\Core\Strings;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Widgets\Cards\Card;
use Phoundation\Web\Http\Html\Enums\DisplayMode;
use Phoundation\Web\Http\Html\Layouts\Grid;
use Phoundation\Web\Http\Html\Layouts\GridColumn;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;
use Plugins\Aerostream\AerostreamFes\Hawbs\Hawb;


// Validate GET
GetValidator::new()
    ->select('id')->isOptional()->isId()
    ->validate();

$meta = [];
$hawb = Hawb::get($_GET['id']);
$mawb = $hawb->getMawb();



// Validate POST and submit
if (Page::isPostRequestMethod()) {
    try {
        // Update hawb
        $hawb = Hawb::get($_GET['id']);
        $hawb->modify();
        $hawb->save();

        // Go back to where we came from
// TODO Implement timers
//showdie(Timers::get('query'));

        Page::getFlashMessages()->add(tr('Success'), tr('Hawb ":hawb" has been updated', [':hawb' => $hawb->getCode()]), DisplayMode::success);
        Page::redirect('referer');

    } catch (ValidationFailedException $e) {
        // Oops! Show validation errors and remain on page
        Page::getFlashMessages()->add($e);
        $hawb->modify($_POST);
    }
}



// Build the roles list management section
$hawbs  = $mawb->getHawbs();
$meta[] = $mawb->getMeta()->getId();

if ($hawbs->getCount()) {
    $hawbs_card = '';

    foreach ($hawbs as $hawb) {
        $meta[] = $hawb->getMeta()->getId();
        $hawbs_card .= Card::new()
            ->setMode(DisplayMode::info)
            ->setHasCollapseSwitch(true)
            ->setTitle(tr('House Airway bill ":id"', [':id' => $hawb->getCode()]))
            ->setContent($hawb->getHtmlForm())
            ->render();
    }

    $hawbs_card = Card::new()
        ->setContent($hawbs_card)
        ->render();
}



// Build the mawb form
$mawb_card = Card::new()
    ->setMode(DisplayMode::success)
    ->setHasCollapseSwitch(true)
    ->setTitle(tr('Master Airway Bill :code', [':code' => $mawb->getCode()]))
    ->setContent($mawb->getHtmlForm()->render())
    ->setButtons(Buttons::new()
        ->addButton(tr('Update'))
        ->addButton(tr('Back'), DisplayMode::secondary, '/aerostream/fes/mawbs/all.html', true)
        ->addButton(tr('Audit'), DisplayMode::info, '/audit/meta-' . Strings::force($meta, '-') . '.html', false, true));



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
    ->addContent($mawb_card->render() . ($hawbs_card ?? ''), 9)
    ->useForm(true);

$grid = Grid::new()
    ->addColumn($column)
    ->addColumn($relevant->render() . $documentation->render(), 3);

echo $grid->render();


// Set page meta data
Page::setHeaderTitle(tr('House Airway Bills'));
Page::setHeaderSubTitle($hawb->getCode());
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'                              => tr('Home'),
    '/aerostream/fes/mawbs/all.html' => tr('Master Airway Bills'),
    ''                               => tr('House Airway Bill :id', [':id' => $hawb->getCode()])
]));
