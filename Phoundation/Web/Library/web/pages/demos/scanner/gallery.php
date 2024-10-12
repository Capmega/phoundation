<?php

use Phoundation\Web\Html\Components\Script;use Phoundation\Web\Html\Enums\JavascriptWrappers;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;


/**
 * Scanner gallery page
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */


// Load required javascript libraries
Page::loadJavascript('adminlte/plugins/ekko-lightbox/ekko-lightbox');
Page::loadJavascript('adminlte/plugins/filterizr/jquery.filterizr');


// Load required CSS libraries
Page::loadCss('adminlte/plugins/ekko-lightbox/ekko-lightbox');


// Load specific test script
echo Script::new()
    ->setJavascriptWrapper(JavascriptWrappers::function)
    ->setContent('
        $(document).on("click", \'[data-toggle="lightbox"]\', function(event) {
            event.preventDefault();
            $(this).ekkoLightbox({
                alwaysShowClose: true
            });
        });

        $(".filter-container").filterizr({gutterPixels: 3});
        $(".btn[data-filter]").on("click", function() {
            $(".btn[data-filter]").removeClass("active");
            $(this).addClass("active");
        });
    ')
    ->render();
?>
<!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-primary">
                    <div class="card-header">
                        <h4 class="card-title">Scanned documents for package "346354"</h4>
                    </div>
                    <div class="card-body">
                        <div>
                            <div class="btn-group w-100 mb-2">
                                <a class="btn btn-info active" href="javascript:void(0)" data-filter="all"> All items </a>
                                <a class="btn btn-info" href="javascript:void(0)" data-filter="1"> Category 1 (WHITE) </a>
                                <a class="btn btn-info" href="javascript:void(0)" data-filter="2"> Category 2 (BLACK) </a>
                                <a class="btn btn-info" href="javascript:void(0)" data-filter="3"> Category 3 (COLORED) </a>
                                <a class="btn btn-info" href="javascript:void(0)" data-filter="4"> Category 4 (COLORED, BLACK) </a>
                            </div>
                            <div class="mb-2">
                                <a class="btn btn-secondary" href="javascript:void(0)" data-shuffle> Shuffle items </a>
                                <div class="float-right">
                                    <select class="custom-select" style="width: auto;" data-sortOrder>
                                        <option value="index"> Sort by Position </option>
                                        <option value="sortData"> Sort by Custom Data </option>
                                    </select>
                                    <div class="btn-group">
                                        <a class="btn btn-default" href="javascript:void(0)" data-sortAsc> Ascending </a>
                                        <a class="btn btn-default" href="javascript:void(0)" data-sortDesc> Descending </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="filter-container p-0 row">
                                <?php
                                    $max = 20;

                                    for ($i = 0; $i < $max; $i++) {
                                        $colors = [1 => 'black', 'red', 'white', 'red and black'];
                                        $color  = random_int(1, 4);
                                        $number = (random_int(1, 14) * 2);

                                        echo '  <div class="filtr-item col-sm-2" data-category="' . $color . '" data-sort="' . $colors[$color] . ' sample">
                                                    <a href="' . UrlBuilder::getImg('scanner/output' . $number . '.jpg') . '" data-toggle="lightbox" data-title="sample 1 - ' . $colors[$color] . '">
                                                        <img src="' . UrlBuilder::getImg('scanner/output' . $number . '.jpg') . '" class="img-fluid mb-2" alt="' . $colors[$color] . ' sample"/>
                                                    </a>
                                                </div>';
                                    }
                                ?>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.container-fluid -->
</section>
