<?php
/*
 * Google library
 *
 * This library contains all kinds of basic google related functions
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 */



/*
 * Get the avatar from the google account of the specified user
 */
function google_get_avatar($user){
    global $_CONFIG;

    try{
        load_libs('image,user');

        if(is_array($user)){
            if(empty($user['gp_id'])){
                if(empty($user['id'])){
                    throw new BException('google_get_avatar: Specified user array contains no "id" or "gp_id"');
                }

                $user = sql_get('SELECT `gp_id` FROM `users` WHERE `id` = '.cfi($user['id']));
            }

            /*
             * Assume this is a user array
             */
            $user = $user['gp_id'];
        }

        if(!$user){
            throw new BException('google_get_avatar(): No google ID specified');
        }

        // Avatars are on http://graph.facebook.com/USERID/picture
        $file   = TMP.file_move_to_target('http://graph.facebook.com/'.$user.'/picture?type=large', TMP, '.jpg');

        // Create the avatars, and store the base avatar location
        $retval = image_create_avatars($file);

        // Clear the temporary file and cleanup paths
        file_clear_path($file);

        // Update the user avatar
        return user_update_avatar($user, $retval);

    }catch(Exception $e){
        throw new BException('facebook_get_avatar(): Failed', $e);
    }
}



/*
 * Returns the necessary javascript for adding a google analytics code to a page
 * This code, however, will load the script from our own servers, avoiding extra
 * DNS lookups, avoiding shitty google caching headers which google is
 * complaining about, etc
 *
 * @obsolete analytics_google()
 */
function google_get_analytics($code){
    try{
        load_libs('analytics');
        return analytics_google($code);

    }catch(Exception $e){
        throw new BException('google_get_analytics(): Failed', $e);
    }
}
?>
