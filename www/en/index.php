<h1>SUCCESS<h1>
<?php

use Phoundation\Web\Http\Html\Elements;

$img = Elements::img()
    ->setName('test')
    ->render();
echo $img;

//$select = Elements::select()
//    ->setName('test')
//    ->render();
//
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
