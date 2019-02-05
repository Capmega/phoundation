<?php
/*
 * Rating library
 *
 * Library to manage HTML star ratings, google ratings, etc
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package rating
 */



/*
 * Initialize the library, automatically executed by libs_load()
 *
 * NOTE: This function is executed automatically by the load_libs() function and does not need to be called manually
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package
 *
 * @return void
 */
function rating_library_init(){
    try{
        ensure_installed(array('name'     => 'rating',
                               'callback' => 'rating_install',
                               'checks'   => array(ROOT.'pub/js/rating/rating.js',
                                                   ROOT.'pub/css/rating/rating.css')));

//        load_config('rating');
        html_load_js('rating/rating');
        html_load_css('rating/rating');

    }catch(Exception $e){
        throw new bException('rating_library_init(): Failed', $e);
    }
}



/*
 * Automatically install dependencies for the rating library
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
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
function rating_install($params){
    try{
        $css = download('https://cdn.jsdelivr.net/rating2/6.6.0/rating2.css');
        $js  = download('https://cdn.jsdelivr.net/rating2/6.6.0/rating2.js');

        file_execute_mode(ROOT.'pub/js/', 0770, function(){
            file_ensure_path(ROOT.'pub/js/rating/', 0550);

            file_execute_mode(ROOT.'pub/js/rating/', 0770, function(){
                rename($js , ROOT.'pub/js/rating/rating.js');
                rename($css, ROOT.'pub/css/rating/rating.css');
            });
        });

    }catch(Exception $e){
        throw new bException('rating_install(): Failed', $e);
    }
}



/*
 * Show specified rating
 */
function rating($stars){
    try{
//    $(".star").raty({
//        starOff: "pub/img/base/raty/star-off.png",
//        starOn : "pub/img/base/raty/star-on.png"
//    });


    }catch(Exception $e){
        throw new bException('rating(): Failed', $e);
    }
}



/*
 * Recalculate and update the value for the specified rating
 */
function rating_calculate($rating){
    try{
        $average = sql_get('SELECT AVG(`ratings_votes`.`rating`) FROM `ratings_votes` WHERE `ratings_id` = :ratings_id', array(':ratings_id' => $rating['id']));
        return $average;

    }catch(Exception $e){
        throw new bException('rating_calculate(): Failed', $e);
    }
}



/*
 * Update the value for the specified rating with the specified value
 */
function rating_update($ratings_id, $value){
    try{
        if(!is_numeric($value) or ($value > 5) or ($value < 0)){
            throw new bException(tr('rating_calculate(): Specified value ":value" is invalid, it should be in between 0 and 5', array(':value' => $value)), $e);
        }

        sql_query('UPDATE `ratings`

                   SET    `value` = :value

                   WHERE  `id`    = :id',

                   array(':id'    => $ratings_id,
                         ':value' => $value));

    }catch(Exception $e){
        throw new bException('rating_calculate(): Failed', $e);
    }
}
?>
