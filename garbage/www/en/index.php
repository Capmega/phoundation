<h1>SUCCESS<h1>
<?php

use Phoundation\Web\Http\Html\Elements;
use Phoundation\Web\Page;

$img = Elements::img()
    ->setName('test')
    ->setAlt('This is a test')
    ->setSrc('https://i.redd.it/yb2hp7qudd151.png')
    ->render();
echo $img;

$select = Elements::select()
    ->setName('test')
    ->setMultiple(true)
    ->setSelected(['aaaaaaaaa', 'BBBBBBBBB'])
    ->setSource([
        'aaaaaaaaa' => 'Alpha',
        'BBBBBBBBB' => 'Beta',
        'ccccccccc' => 'Gamma'
    ])
    ->render();

echo $select;

$table = Elements::table()
    ->setName('test2')
    ->setHeaders(['Name'])
    ->setSource([
        'aaaaaaaaa' => ['Alpha'],
        'BBBBBBBBB' => ['Beta'],
        'ccccccccc' => ['Gamma']
    ])
    ->render();
echo $table;

show($select);
show($table);

Page::execute('secondary.php');

showdie('Done!');

//$html = Html::select()
//    ->setSource()
//    ->setSourceQuery()
//    ->render();
//
//Page::addHtml($html);
