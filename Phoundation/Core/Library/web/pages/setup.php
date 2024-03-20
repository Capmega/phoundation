<?php

use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;
use Phoundation\Data\Validator\Validator;
use Phoundation\Databases\Sql\Exception\SqlAccessDeniedException;
use Phoundation\Developer\Project\Exception\EnvironmentExists;
use Phoundation\Developer\Project\Project;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Response;

throw new \Phoundation\Exception\UnderConstructionException();
// ONLY HERE do we allow disabling password validation in web
$get = GetValidator::new()
    ->select('no_password_validation')->isOptional(false)->isBoolean()
    ->validate();

if (isset_get($get['no_password_validation'])) {
    Validator::disablePasswords();
}



// Validate setup data, create project and run setup
if (Request::isPostRequestMethod()) {
    try {
        $post = Project::validate(PostValidator::new());
        Project::create($post['project']);
        Project::setEnvironment($post['environment']);

        $configuration = Project::getEnvironment()->getConfiguration();
        $configuration->setProject($post['project']);
        $configuration->setDomain($post['domain']);
        $configuration->setEmail($post['admin_email']);
        $configuration->setPassword($post['admin_pass1']);
        $configuration->getDatabase()->setHost($post['database_host']);
        $configuration->getDatabase()->setName($post['database_name']);
        $configuration->getDatabase()->setUser($post['database_user']);
        $configuration->getDatabase()->setPass($post['database_pass1']);

        Project::setup();

        Response::setBuildBody(false);
        ?>
        <?= Response::getFlashMessages()->render() ?>
        <body class="hold-transition register-page" style="height: auto;">
        <div class="register-box">
            <div class="register-logo">
                <a href="https://phoundation.org/"><b>Ph</b>oundation</a>
            </div>
            <div class="card">
                <div class="card-body register-card-body">
                    <p class="login-box-msg">Phoundation system setup completed</p>
                    <hr>
                    <p class="login-box-msg">The setup of your Phoundation system has now finished. Please click below to start using it!</p>
                    <hr>
                    <div class="mb-3">
                        <a class="form-control btn btn-primary" href="<?= UrlBuilder::getCurrentDomainRootUrl() ?>">Start using Phoundation!</a>
                    </div>
                </div>
                <!-- /.form-box -->
            </div><!-- /.card -->
        </div>
        </body>
        <!-- /.register-box -->
        <?php

        // Set page meta data
        Response::setPageTitle(tr('Phoundation setup finished'));
        return;

    } catch (EnvironmentExists|ValidationFailedException|SqlAccessDeniedException $e) {
        Response::getFlashMessages()->addMessage($e);
    }
}

// This page will build its own body
Response::setBuildBody(false);
?>
<?= Response::getFlashMessages()->render() ?>
<body class="hold-transition register-page" style="height: auto;">
    <div class="register-box">
      <div class="register-logo">
        <a href="https://phoundation.org/"><b>Ph</b>oundation</a>
      </div>
      <div class="card">
        <div class="card-body register-card-body">
          <p class="login-box-msg">Setup your new Phoundation system</p>
          <hr>
          <p class="login-box-msg">Phoundation requires a simple setup process to get it running. Please fill out the form below and the system will initialize itself. To help you out, some of the fields have been filled with default values.</p>
          <form action="<?= Request::getUrl() ?>" method="post">
              <div class="mb-3">
                  <label for="admin_email">Administrator email address</label>
                  <input name="admin_email" type="email" class="form-control" placeholder="Administrator email address" value="<?= isset_get($post['admin_email']) ?>">
              </div>
              <div class="mb-3">
                  <input name="admin_pass1" type="password" class="form-control" placeholder="Administrator password">
              </div>
              <div class="mb-3">
                  <input name="admin_pass2" type="password" class="form-control" placeholder="Verify administrator password">
              </div>
              <div class="mb-3">
                  <input name="project" type="text" class="form-control" placeholder="Project name"  value="<?= isset_get($post['project'], 'phoundation') ?>">
              </div>
              <div class="mb-3">
                  <select name="environment" type="text" class="form-control">
                      <option value="">Select an environment</option>
                      <option<?= (isset_get($post['environment']) ? ' selected' : null) ?> value="local">Local</option>
                      <option<?= (isset_get($post['environment']) ? ' selected' : null) ?> value="public_trial">Public trial</option>
                      <option<?= (isset_get($post['environment']) ? ' selected' : null) ?> value="private_trial">Private trial</option>
                      <option<?= (isset_get($post['environment']) ? ' selected' : null) ?> value="Production">Production</option>
                  </select>
              </div>
              <div class="mb-3">
                  <input name="domain" type="text" class="form-control" placeholder="Domain name" value="<?= isset_get($post['domain'], Request::getDomain()) ?>">
              </div>
              <div class="mb-3">
                  <input name="database_host" type="text" class="form-control" placeholder="Database host" value="<?= isset_get($post['database_host'], 'localhost') ?>">
              </div>
              <div class="mb-3">
                  <input name="database_name" type="text" class="form-control" placeholder="Database name" value="<?= isset_get($post['database_name'], 'phoundation') ?>">
              </div>
              <div class="mb-3">
                  <input name="database_user" type="text" class="form-control" placeholder="Database user" value="<?= isset_get($post['database_user'], 'phoundation') ?>">
              </div>
              <div class="mb-3">
                  <input name="database_pass1" type="password" class="form-control" placeholder="Database user password">
              </div>
              <div class="mb-3">
                  <input name="database_pass2" type="password" class="form-control" placeholder="Verify database user password">
              </div>
              <hr>
              <p class="login-box-msg"><b>NOTE:</b> The setup may take various seconds to minutes. Please wait patiently and check Phoundation logs for more information during setup.</p>
              <hr>
              <div class="mb-3">
                <input name="submit" type="submit" class="form-control button btn-primary" value="Run setup">
              </div>
          </form>
        </div>
        <!-- /.form-box -->
      </div><!-- /.card -->
    </div>
</body>
<!-- /.register-box -->
<?php

// Set page meta data
Response::setPageTitle(tr('Setup Phoundation'));

