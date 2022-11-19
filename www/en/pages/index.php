<?php

use Phoundation\Web\Http\Html\Elements\Img;
use Plugins\Mdb\Components\Table;

?>
<!-- Button trigger modal -->
<button type="button" class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#exampleModal">
    Launch demo modal
</button>

<div class="container">
    <div class="d-flex justify-content-center align-items-center" style="height: 100vh">
        <div class="text-center">
            <?php
            echo Table::new()
                ->setSourceQuery('SELECT * FROM `versions`')
                ->render();

            echo Img::new()
                ->setClass('mb-4')
                ->setAlt('MDB')
                ->setSrc('phoundation.jpg')
                ->render();
            ?>
            <a
                    class="btn btn-primary btn-lg"
                    href="https://mdbootstrap.com/docs/standard/getting-started/"
                    target="_blank"
                    role="button"
            >Start MDB tutorial</a
            >
        </div>
    </div>
</div>
