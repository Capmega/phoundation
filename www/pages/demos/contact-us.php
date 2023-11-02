<?php

use Phoundation\Web\Http\Html\Components\BreadCrumbs;
use Phoundation\Web\Http\Html\Components\Script;
use Phoundation\Web\Http\Html\Enums\JavascriptWrappers;
use Phoundation\Web\Page;


/**
 * Contact us page
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */

// Set page meta data
Page::setHeaderTitle(tr('Contact us'));
Page::setHeaderSubTitle(tr('Demo'));
Page::setBreadCrumbs(BreadCrumbs::new()->setSource([
    '/'           => tr('Home'),
    '/demos.html' => tr('Demos'),
    ''            => tr('Contact us')
]));

?>
<!-- Main content -->
<section class="content">

  <!-- Default box -->
  <div class="card">
    <div class="card-body row">
      <div class="col-5 text-center d-flex align-items-center justify-content-center">
        <div class="">
          <h2>Admin<strong>LTE</strong></h2>
          <p class="lead mb-5">123 Testing Ave, Testtown, 9876 NA<br>
            Phone: +1 234 56789012
          </p>
        </div>
      </div>
      <div class="col-7">
        <div class="form-group">
          <label for="inputName">Name</label>
          <input type="text" id="inputName" class="form-control" />
        </div>
        <div class="form-group">
          <label for="inputEmail">E-Mail</label>
          <input type="email" id="inputEmail" class="form-control" />
        </div>
        <div class="form-group">
          <label for="inputSubject">Subject</label>
          <input type="text" id="inputSubject" class="form-control" />
        </div>
        <div class="form-group">
          <label for="inputMessage">Message</label>
          <textarea id="inputMessage" class="form-control" rows="4"></textarea>
        </div>
        <div class="form-group">
          <input type="submit" class="btn btn-primary" value="Send message">
        </div>
      </div>
    </div>
  </div>

</section>
<!-- /.content -->
