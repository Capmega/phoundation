<?php

use Phoundation\Web\WebPage;
use Templates\AdminLte\Components\BreadCrumbs;
use Templates\AdminLte\Components\Widgets\Cards\Card;
use Templates\AdminLte\Layouts\Grid;
use Templates\AdminLte\Layouts\GridRow;
use Templates\AdminLte\Layouts\GridColumn;



// Build the page
$card = Card::new()
    ->setTitle(tr('This is a test!'))
    ->setContent('Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nullam leo nisl, iaculis nec est quis, dapibus commodo mi. Nunc dui metus, ultricies ac vestibulum sit amet, rutrum tristique est. Aenean et consectetur sem. Mauris non scelerisque urna, in efficitur nibh. Nulla facilisi. Ut tempor ligula fringilla nibh commodo, sed scelerisque erat posuere. Aenean lobortis volutpat sem, eu tincidunt neque hendrerit non. Nunc maximus ante et arcu maximus maximus. Ut vitae leo et arcu condimentum pellentesque sed et diam. Mauris ut sapien porttitor, pharetra erat quis, suscipit leo. Vestibulum a libero vitae quam tempor aliquam. Proin ultrices nisl in ante aliquam, at posuere arcu luctus. Nulla iaculis porttitor sem eu dignissim.');

$column = GridColumn::new()
    ->setSize(3)
    ->setTier('xl')
    ->setContent($card->render());
$row    = GridRow::new()
    ->addColumn($column)
    ->addColumn($column)
    ->addColumn($column)
    ->addColumn($column);
$layout = Grid::new()
    ->setType('xxl')
    ->addRow($row);

echo $layout->render();



// Set page meta data
WebPage::setPageTitle(tr('Dashboard (under development)'));
WebPage::setHeaderTitle(tr('Dashboard'));
WebPage::setHeaderSubTitle(tr('(under development)'));
WebPage::setDescription(tr(''));
WebPage::setBreadCrumbs(BreadCrumbs::new([
    '/' => tr('Home'),
    ''  => tr('Dashboard')
]));
