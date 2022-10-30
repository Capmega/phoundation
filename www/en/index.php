<h1>SUCCESS<h1>
<?php

use Phoundation\Web\Http\Html\Elements;

$img = Elements::img()
    ->setName('test')
    ->setAlt('fuck off')
    ->setSrc('https://i.redd.it/yb2hp7qudd151.png')
    ->render();
echo $img;

$select = Elements::select()
    ->setName('test')
    ->render();

echo $select;
showdie($select);

//$table = Elements::table()
//    ->setName('test')
//    ->render();
//echo $table;

//$html = Html::select()
//    ->setSource()
//    ->setSourceQuery()
//    ->render();
//
//Page::addHtml($html);
