<h1>SUCCESS<h1>
<?php

use Phoundation\Web\Http\Html\Html;

sql()->get('');


$html = Html::select()
    ->setSource()
    ->setSourceQuery()
    ->render();

Page::addHtml($html);
