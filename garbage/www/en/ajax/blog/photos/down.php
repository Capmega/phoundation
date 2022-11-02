<?php
include_once(dirname(__FILE__).'/../../../libs/startup.php');

try {
    load_libs('admin,json,file,upload');

    /*
     * User has access?
     */
    $user = rights_or_access_denied('admin', '/admin/signin.php', 'json');

    if (empty($_POST['id'])) {
        throw new CoreException('ajax/blog/photos/delete: No photo specified', 'notspecified');
    }

    $photo   = sql_get('SELECT `blogs_photos`.`id`, `blogs_photos`.`blogs_posts_id`, `blogs_posts`.`created_by`, `blogs_photos`.`priority`

                        FROM   `blogs_photos`

                        JOIN   `blogs_posts`
                        ON     `blogs_posts`.`id`        = `blogs_photos`.`blogs_posts_id`

                        WHERE  `blogs_photos`.`id`       = '.cfi($_POST['id']));

    if (empty($photo['id'])) {
        throw new CoreException('ajax/blog/photos/delete: Unknown photo_id "'.Strings::Log($_POST['id']).'" specified', 'unknown');
    }

    if (($photo['created_by'] != $_SESSION['user']['id']) and !has_rights('god')) {
        throw new CoreException('ajax/blog/photos/delete: This photo does not belong to you.', 'accessdenied');
    }


    /*
     * Switch priorities of this entry and the entry with prio -1
     */
    $max = sql_get('SELECT COUNT(`id`) AS count FROM `blogs_photos` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $photo['blogs_posts_id']), 'count') - 1;

    if ($photo['priority'] < $max) {
        sql_query('START TRANSACTION');
            sql_query('UPDATE `blogs_photos` SET `priority` = (`priority` - 1) WHERE `blogs_posts_id` = :blogs_posts_id AND `priority` = :priority', array(':blogs_posts_id' => $photo['blogs_posts_id'], ':priority' => $photo['priority'] + 1));
            sql_query('UPDATE `blogs_photos` SET `priority` = (`priority` + 1) WHERE `id`             = :id'                                       , array(':id'             => $photo['id']));
        sql_query('COMMIT');
    }

}catch(Exception $e) {
    switch ($e->getCode()) {
        case 'unknown':
            json_error(tr('Unknown photo id specified'));
            break;

        case 'notspecified':
            json_error(tr('No photo id specified'));
            break;

        case 'accessdenied':
            json_error(tr('You cannot delete this photo, it is not yours'));
            break;

        default:
            json_error(tr('Something went wrong, please try again'));
    }
}
?>
