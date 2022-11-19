<?php
/*
 * Microsoftlibrary
 *
 * This library contains all kinds of basic microsoft related functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */


/*
 * Get the avatar from the microsoft account of the specified user
 */
function microsoft_get_avatar($user) {
    global $_CONFIG;

    try {
        load_libs('image,user');

        if (is_array($user)) {
            if (empty($user['ms_id'])) {
                if (empty($user['id'])) {
                    throw new CoreException('microsoft_get_avatar: Specified user array contains no "id" or "ms_id"');
                }

                $user = sql_get('SELECT `ms_id` FROM `users` WHERE `id` = '.cfi($user['id']));
            }

            /*
             * Assume this is a user array
             */
            $user = $user['ms_id'];
        }

        if (!$user) {
            throw new CoreException('microsoft_get_avatar(): No microsoft ID specified');
        }

        // Avatars are on http://graph.facebook.com/USERID/picture
        $file   = PATH_TMP.file_move_to_target('http://graph.facebook.com/'.$user.'/picture?type=large', PATH_TMP, '.jpg');

        // Create the avatars, and store the base avatar location
        $return = image_create_avatars($file);

        // Clear the temporary file and cleanup paths
        file_clear_path($file);

        // Update the user avatar
        return user_update_avatar($user, $return);

    }catch(Exception $e) {
        throw new CoreException('facebook_get_avatar(): Failed', $e);
    }
}
?>