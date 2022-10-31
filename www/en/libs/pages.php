<?php
/*
 * Pages library
 *
 * This library contains functions to manage the various web pages on this system, list them, get details, etc
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package pages
 */



/*
 * Return a list of all available pages filtered by
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @pages Function reference
 * @see sql_simple_list()
 * @package pages
 * @version 2.5.50: Added function and documentation
 *
 * @return mixed The list of available pages
 */
function pages_list($params) {
    try {
        Arrays::ensure($params, 'filters');

        $return = array();
        $pages  = scandir(PATH_ROOT.'www/en/');

        $params['filters'] = Arrays::force($params['filters']);

        foreach ($pages as $id => $page) {
            $page = strtolower($page);

            if (substr($page, -4, 4) != '.php') {
                continue;
            }

            foreach ($params['filters'] as $key => $value) {
                if (empty($value)) {
                    continue;
                }

                if (($key === 'name') and !str_contains($page, $value)) {
                    continue;
                }

                $return[$page] = array('name'        => Strings::untilReverse(basename($page), '.php'),
                                       'package'     => '',
                                       'description' => '');
            }
        }

        ksort($return);
        return $return;

    }catch(Exception $e) {
        throw new CoreException(tr('pages_list(): Failed'), $e);
    }
}



/*
 * Return HTML for a pages select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available pages
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @pages Function reference
 * @package pages
 * @see sql_simple_get()
 * @version 2.5.38: Added function and documentation
 *
 * @param array $params The parameters required
 * @param $params name
 * @param $params class
 * @param $params empty
 * @param $params none
 * @param $params selected
 * @param $params parents_id
 * @param $params status
 * @param $params orderby
 * @param $params resource
 * @return string HTML for a pages select box within the specified parameters
 */
function pages_select($params = null) {
    try {
        Arrays::ensure($params);
        array_default($params, 'name'      , 'seopages');
        array_default($params, 'class'     , 'form-control');
        array_default($params, 'selected'  , null);
        array_default($params, 'autosubmit', true);
        array_default($params, 'parents_id', null);
        array_default($params, 'status'    , null);
        array_default($params, 'remove'    , null);
        array_default($params, 'empty'     , tr('No pages available'));
        array_default($params, 'none'      , tr('Select a pages'));
        array_default($params, 'orderby'   , '`name`');

        $execute = array();

        if ($params['remove']) {
            if (count(Arrays::force($params['remove'])) == 1) {
                /*
                 * Filter out only one entry
                 */
                $where[] = ' `id` != :id ';
                $execute[':id'] = $params['remove'];

            } else {
                /*
                 * Filter out multiple entries
                 */
                $in      = sql_in(Arrays::force($params['remove']));
                $where[] = ' `id` NOT IN ('.implode(', ', array_keys($in)).') ';
                $execute = array_merge($execute, $in);
            }
        }

        if ($params['parents_id']) {
            $where[] = ' `parents_id` = :parents_id ';
            $execute[':parents_id'] = $params['parents_id'];

        } else {
            $where[] = ' `parents_id` IS NULL ';
        }

        if ($params['status'] !== false) {
            $where[] = ' `status` '.sql_is($params['status'], ':status');
            $execute[':status'] = $params['status'];
        }

        if (empty($where)) {
            $where = '';

        } else {
            $where = ' WHERE '.implode(' AND ', $where).' ';
        }

        $query              = 'SELECT `seoname`, `name` FROM `pages` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $return             = html_select($params);

        return $return;

    }catch(Exception $e) {
        throw new CoreException(tr('pages_select(): Failed'), $e);
    }
}
?>
