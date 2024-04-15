<?php

use Phoundation\Web\Html\Components\Input\InputSelect2;
use Phoundation\Web\Http\UrlBuilder;

$suggest = InputSelect2::new()
                       ->setId('test')
                       ->addClasses('form-control')
                       ->setSourceUrl(UrlBuilder::getWww('/ajax/test/autosuggest.json'));

echo $suggest->render();
