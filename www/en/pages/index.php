<?php

use Phoundation\Web\Http\Html\Elements\Img;
use Plugins\Mdb\Components\Table;

?>
<div class="container">
    <div class="d-flex justify-content-center align-items-center">
        <div class="text-center">
            <?php
            echo Img::new()
                ->setClass('mb-4')
                ->setHeight('200')
                ->setAlt('Phoundation logo')
                ->setSrc('logos/phoundation/phoundation.png')
                ->render();

            echo Table::new()
                ->setSourceQuery('SELECT * FROM `versions`')
                ->render();

            ?>
        </div>
    </div>
</div>
