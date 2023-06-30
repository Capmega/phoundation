<?php

use Phoundation\Web\Http\Html\Components\Input\InputAutoSuggest;
use Phoundation\Web\Http\UrlBuilder;

$suggest = InputAutoSuggest::new()
    ->setId('test')
    ->addClass('form-control')
    ->setSourceUrl(UrlBuilder::getWww('/ajax/medinet/sources/doctors/autosuggest.json'));

echo $suggest->render();