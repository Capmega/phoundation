<?php
use Phoundation\Web\WebPage;

?>

<div class="error-page">
    <h2 class="headline text-warning"> 404</h2>

    <div class="error-content">
        <h3><i class="fas fa-exclamation-triangle text-warning"></i> Oops! Page not found.</h3>

        <p>
            We could not find the page you were looking for.
            Meanwhile, you may <a href="../../index.html">return to dashboard</a> or try using the search form.
        </p>

        <form class="search-form">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search">

                <div class="input-group-append">
                    <button type="submit" name="submit" class="btn btn-warning"><i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
            <!-- /.input-group -->
        </form>
    </div>
    <!-- /.error-content -->
</div>

<?php
//<meta charset="utf-8">
//<meta name="viewport" content="width=device-width, initial-scale=1">
//<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
//<link rel="stylesheet" href="../../plugins/fontawesome-free/css/all.min.css">
//<link rel="stylesheet" href="../../dist/css/adminlte.min.css">




//<script src="../../plugins/jquery/jquery.min.js"></script>
//<script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
//<script src="../../dist/js/adminlte.min.js"></script>

// Set page meta data
WebPage::setPageTitle(tr('Oops, that was was not found!'));
WebPage::setHeaderTitle(tr('Oops, that was was not found!'));
WebPage::setHeaderSubTitle(tr('(Error page)'));
WebPage::setDescription(tr(''));
WebPage::setBreadCrumbs();






