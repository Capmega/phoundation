<?php

use Phoundation\Web\Http\Html\Img;
use Phoundation\Web\Http\Url;

?>
<!-- Button trigger modal -->
<button type="button" class="btn btn-primary" data-mdb-toggle="modal" data-mdb-target="#exampleModal">
    Launch demo modal
</button>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                <button type="button" class="btn-close" data-mdb-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">...</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-mdb-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>
<div class="container">
    <div class="d-flex justify-content-center align-items-center" style="height: 100vh">
        <div class="text-center">
            <?php
            echo Img::new()
                ->setClass('mb-4')
                ->setAlt('MDB')
                ->setSrc('phoundation.jpg')
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
</div>

