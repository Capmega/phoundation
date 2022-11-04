<?php

use Phoundation\Web\Http\Html\Img;

?>
<div class="d-flex justify-content-center align-items-center" style="height: 100vh">
    <div class="text-center">
        <?php
        echo Img::new()
            ->setClass('mb-4')
            ->setAlt('MDB')
            ->setSrc('https://mdbootstrap.com/img/logo/mdb-transparent-250px.png')
            ->render();
        ?>
        <h5 class="mb-3">Thank you for using our product. We're glad you're with us.</h5>
        <p class="mb-3">MDB Team</p>
        <a
                class="btn btn-primary btn-lg"
                href="https://mdbootstrap.com/docs/standard/getting-started/"
                target="_blank"
                role="button"
        >Start MDB tutorial</a
        >
    </div>
</div>

