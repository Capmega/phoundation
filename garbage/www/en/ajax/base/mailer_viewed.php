<?php
    
    try {
        if (empty($_GET['code'])) {
            throw new CoreException('ajax/base/mailer_access: No code specified');
        }

        load_libs('image,mailer');
        image_send(PATH_ROOT.'/pub/img/'.mailer_viewed($_GET['code']));

    }catch(Exception $e) {
        page_404('html');
    }
?>