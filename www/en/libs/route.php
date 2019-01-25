<?php
/*
 * Route library
 *
 * This is the URL to PHP page routing library. This library will take the client request URL and depending on specified rules, send it to a specific PHP page with $_GET variables.
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package route
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
 * @package route
 *
 * @return void
 */
function route_library_init(){
    try{

    }catch(Exception $e){
        throw new bException('route_library_init(): Failed', $e);
    }
}



/*
 * Route the request uri from the client to the correct php file
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package route
 * @see route_install()
 * @see date_convert() Used to convert the sitemap entry dates
 * @table: `route`
 * @note: This is a note
 * @version 1.27.0: Added function and documentation
 * @example
 * code
 * route('/\//', 'index')
 * route('/\//', 'index')
 * /code
 *
 * @param string $regex
 * @params string $target
 * @params null string $flags
 * @return void
 */
function route($regex, $target, $flags = null){
    try{
        $match = preg_match($regex, $_SERVER['REQUEST_URI']);
        log_file(tr('Testing ":regex" on ":url"', array(':regex' => $regex, ':url' => $_SERVER['REQUEST_URI'])), 'route', 'VERYVERBOSE/cyan');

        if($match){
            log_file(tr('Succesfully applied ":regex" on ":url"', array(':regex' => $regex, ':url' => $_SERVER['REQUEST_URI'])), 'route', 'VERBOSE/green');
            page_show($target);
        }

    }catch(Exception $e){
        throw new bException('route(): Failed', $e);
    }
}
?>
