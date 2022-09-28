<?php
/*
 * Ingiga toolkit custom admin library template
 *
 * This library can be used to add project specific functionalities
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <license@capmega.com>
 */
//showdie($core->register['script']);



load_libs('atlant');
atlant_force_my_profile('HERE BE THE HASH OF THE DEFAULT PASSWORD');



/*
 * Custom page loader. Will add header and footer to the given HTML, then send
 * HTTP headers, and then HTML to client
 */
function c_page($params, $meta, $html){
    try{
        return atlant_page($params, $meta, $html);

    }catch(Exception $e){
        throw new CoreException('c_page(): Failed', $e);
    }
}



/*
 * Create and return the page header
 */
function c_html_header($params = null, $meta = null, $links = null){
    try{
        return atlant_html_header($params, $meta, $links);

    }catch(Exception $e){
        throw new CoreException('c_html_header(): Failed', $e);
    }
}



/*
 * Create and return the page header
 */
function c_page_header($params){
    try{
        return atlant_page_header($params);

    }catch(Exception $e){
        throw new CoreException('c_page_header(): Failed', $e);
    }
}



/*
 * Create and return the page footer
 */
function c_html_footer($params){
    try{
        return atlant_html_footer($params);

    }catch(Exception $e){
        throw new CoreException('c_html_footer(): Failed', $e);
    }
}



/*
 *
 */
function c_menu(){
    try{
        $html = '   <li>
                        <a href="https://google.com" target="_blank"><span class="fa fa-book"></span> <span class="xn-text">'.tr('External link').'</span></a>
                    </li>';

        return $html;

    }catch(Exception $e){
        throw new CoreException('c_menu(): Failed', $e);
    }
}
?>
