<?php

use Phoundation\Web\Http\Html\Elements\Img;
use Plugins\Mdb\Components\Table;
use Plugins\Mdb\Elements\CheckBox;

?>
<div class="container">
    <div class="d-flex justify-content-center align-items-center">
        <div class="">
            <?php
//            echo Img::new()
//                ->setClass('mb-4')
//                ->setHeight('200')
//                ->setAlt('Phoundation logo')
//                ->setSrc('logos/phoundation/phoundation.png')
//                ->render();

//            $box = CheckBox::new()
//                ->setId('testId')
//                ->setValue('aaaaaaa')
//                ->setLabel('TEST!')
//                ->render();
//
//            show($box);
//            echo $box;

            $table = Table::new()
                ->setTitle('Libraries')
                ->setHeaderText('Browser default checkboxes and radios are replaced with the help of .form-check, a series of classes for both input types that improves the layout and behavior of their HTML elements, that provide greater customization and cross browser consistency. Checkboxes are for selecting one or several options in a list, while radios are for selecting one option from many.

Structurally, our <input>s and <label>s are sibling elements as opposed to an <input> within a <label>. This is slightly more verbose as you must specify id and for attributes to relate the <input> and <label>.

We use the sibling selector (~) for all our <input> states, like :checked or :disabled. When combined with the .form-check-label class, we can easily style the text for each item based on the <input>\'s state.')
                ->setSourceQuery('SELECT * FROM `versions`')
                ->setRowUrl('/users/:ROW')
                ->addConvertColumn('id', function ($value) {
                    return CheckBox::new()
                        ->setValue($value)
                        ->render();
                })
                ->render();

            echo $table;

            ?>
        </div>
    </div>
</div>


