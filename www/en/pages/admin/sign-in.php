<?php

use Phoundation\Core\Session;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;



// Only show sign-in page if we're a guest user
if (!Session::getUser()->isGuest()) {
    Page::redirect('prev');
}



// Validate sign in data and sign in
if (Page::isRequestMethod('post')) {
    Session::validateSignIn();
    Session::signIn();
    Page::redirect('/');
}



// This page will build its own body
Page::setBuildBody(false);
?>
<?= Page::getFlashMessages()->render() ?>
<body class="hold-transition login-page">
    <div class="login-box">
      <!-- /.login-logo -->
      <div class="card card-outline card-primary">
        <div class="card-header text-center">
          <a href="http://phoundation.org" class="h1"><b>Ph</b>oundation</a>
        </div>
        <div class="card-body">
          <p class="login-box-msg"><?= tr('Please sign in to start your session') ?></p>

          <form action="<?= UrlBuilder::www() ?>" method="post">
            <?php
                if (Session::supports('email')) {
                    ?>
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" placeholder="<?= tr('Email') ?>">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-envelope"></span>
                            </div>
                        </div>
                    </div>
                    <div class="input-group mb-3">
                        <input type="password" class="form-control" placeholder="<?= tr('Password') ?>">
                        <div class="input-group-append">
                            <div class="input-group-text">
                                <span class="fas fa-lock"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-8">
                            <div class="icheck-primary">
                                <input type="checkbox" id="remember">
                                <label for="remember">
                                    <?= tr('Remember Me') ?>
                                </label>
                            </div>
                        </div>
                        <!-- /.col -->
                        <div class="col-4">
                            <button type="submit" class="btn btn-primary btn-block"><?= tr('Sign In') ?></button>
                        </div>
                        <!-- /.col -->
                    </div>
                    <?php
                }
            ?>
          </form>
          <?php
                $html = '';

                if (Session::supports('facebook')) {
                    $html .= '  <a href="#" class="btn btn-block btn-primary">
                                    <i class="fab fa-facebook mr-2"></i>' . tr('Sign in using Facebook') . ' 
                                </a>';
                }

                if (Session::supports('google')) {
                    $html .= '  <a href="#" class="btn btn-block btn-danger">
                                    <i class="fab fa-google-plus mr-2"></i>' . tr('Sign in using Google') . '
                                </a>';
                }

                if ($html) {
                    echo  ' <div class="social-auth-links text-center mt-2 mb-3">
                                ' . $html . '
                            </div>';
                }

                if (Session::supports('register')) {
                    echo '  <p class="mb-1">
                                <a href="' . UrlBuilder::www('/lost-password.html') . '">' . tr('I forgot my password') . '</a>
                            </p>';
                }

                if (Session::supports('register')) {
                    echo '  <p class="mb-0">
                                <a href="' . UrlBuilder::www('/sign-in.html') . '" class="text-center">' . tr('Register a new membership') . '</a>
                            </p>';
                }
        ?>
        </div>
        <!-- /.card-body -->
      </div>
      <!-- /.card -->
    </div>
</body>
<?php

// Set page meta data
Page::setPageTitle(tr('Setup Phoundation'));

