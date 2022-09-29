<?php
/*
 * Environments library
 *
 * This library has functiosn to work with base environments
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package environments
 */



/*
 * Return HTML for an environments select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available environments
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package environments
 *
 * @param array $params The parameters required
 * @param string $params name
 * @param string $params empty
 * @param string $params none
 * @return string HTML for a environments select box within the specified parameters
 */
function environments_select($params = null) {
    try{
        array_ensure($params);
        array_default($params, 'name' , 'environment');
        array_default($params, 'empty', tr('No environments available'));
        array_default($params, 'none' , tr('Select an environment'));

        $params['resource'] = array();

        foreach(get_config('deploy')['deploy'] as $key => $config) {
            $params['resource'][$key] = $key;
        }

        $retval = html_select($params);
        return $retval;

    }catch(Exception $e) {
        throw new CoreException('environments_select(): Failed', $e);
    }
}
