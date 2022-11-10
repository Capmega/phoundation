<?php
/*
 * Rating library
 *
 * Library to manage HTML star ratings, google ratings, etc
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package rating
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package
 *
 * @return void
 */
function rating_library_init() {
    try {
        ensure_installed(array('name'     => 'rating',
                               'callback' => 'rating_install',
                               'checks'   => array(PATH_ROOT.'pub/js/rating/rating.js',
                                                   PATH_ROOT.'pub/css/rating/rating.css')));

//        load_config('rating');
        html_load_js('rating/rating');
        html_load_css('rating/rating');

    }catch(Exception $e) {
        throw new CoreException('rating_library_init(): Failed', $e);
    }
}



/*
 * Automatically install dependencies for the rating library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package rating
 * @see rating_init_library()
 * @version 2.0.3: Added function and documentation
 * @note This function typically gets executed automatically by the rating_init_library() through the ensure_installed() call, and does not need to be run manually
 *
 * @param params $params A parameters array
 * @return void
 */
function rating_install($params) {
    try {
        $css = download('https://cdn.jsdelivr.net/rating2/6.6.0/rating2.css', 'ratings');
        $js  = download('https://cdn.jsdelivr.net/rating2/6.6.0/rating2.js' , 'ratings');

        File::new()->executeMode(PATH_ROOT.'pub/js/', 0770, function() {
            Path::ensure(PATH_ROOT.'pub/js/rating/', 0550);

            File::new()->executeMode(PATH_ROOT.'pub/js/rating/', 0770, function() {
                rename($js , PATH_ROOT.'pub/js/rating/rating.js');
                rename($css, PATH_ROOT.'pub/css/rating/rating.css');
            });
        });

        file_delete(PATH_TMP.'ratings');

    }catch(Exception $e) {
        throw new CoreException('rating_install(): Failed', $e);
    }
}



/*
 * Show specified rating
 */
function rating($stars) {
    try {
//    $(".star").raty({
//        starOff: "pub/img/base/raty/star-off.png",
//        starOn : "pub/img/base/raty/star-on.png"
//    });


    }catch(Exception $e) {
        throw new CoreException('rating(): Failed', $e);
    }
}



/*
 * Recalculate and update the value for the specified rating
 */
function rating_calculate($rating) {
    try {
        $average = sql_get('SELECT AVG(`ratings_votes`.`rating`) FROM `ratings_votes` WHERE `ratings_id` = :ratings_id', array(':ratings_id' => $rating['id']));
        return $average;

    }catch(Exception $e) {
        throw new CoreException('rating_calculate(): Failed', $e);
    }
}



/*
 * Update the value for the specified rating with the specified value
 */
function rating_update($ratings_id, $value) {
    try {
        if (!is_numeric($value) or ($value > 5) or ($value < 0)) {
            throw new CoreException(tr('rating_calculate(): Specified value ":value" is invalid, it should be in between 0 and 5', array(':value' => $value)), $e);
        }

        sql_query('UPDATE `ratings`

                   SET    `value` = :value

                   WHERE  `id`    = :id',

                   array(':id'    => $ratings_id,
                         ':value' => $value));

    }catch(Exception $e) {
        throw new CoreException('rating_calculate(): Failed', $e);
    }
}
?>
