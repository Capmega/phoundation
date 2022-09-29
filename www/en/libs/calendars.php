<?php
/*
 * Calendar library
 *
 * This is the calendar library file, it contains functions to manage calendars
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package calendars
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
 * @package calendars
 * @version 2.5.38: Added function and documentation
 *
 * @return void
 */
function calendars_library_init() {
    try{
        //ensure_installed(array('name'      => 'calendar',
        //                       'callback'  => 'calendars_install',
        //                       'checks'    => ROOT.'libs/external/calendar/calendar,'.ROOT.'libs/external/calendar/foobar',
        //                       'functions' => 'calendar,foobar',
        //                       'which'     => 'calendar,foobar'));

    }catch(Exception $e) {
        throw new CoreException('calendars_library_init(): Failed', $e);
    }
}



/*
 * Install the external calendar library
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @version 2.5.38: Added function and documentation
 * @package calendars
 *
 * @param
 * @return
 */
function calendars_install($params) {
    try{
        //load_libs('apt');
        //apt_install('calendar');

    }catch(Exception $e) {
        throw new CoreException('calendars_install(): Failed', $e);
    }
}



/*
 * Validate the specified calendar
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @calendar Function reference
 * @package calendars
 *
 * @param array $calendar The calendar to validate
 * @return array The validated and cleaned $calendar array
 */
function calendars_validate($calendar) {
    try{
        load_libs('validate,seo');

        $v = new ValidateForm($calendar, 'name');

        /*
         * Validate foo
         */
        $v->isNotEmpty ($calendar['name']    , tr('Please specify a calendar name'));
        $v->hasMinChars($calendar['name'],  2, tr('Please ensure the calendar name has at least 2 characters'));
        $v->hasMaxChars($calendar['name'], 64, tr('Please ensure the calendar name has less than 64 characters'));

        /*
         * Validate description
         */
        if (empty($calendar['description'])) {
            $calendar['description'] = null;

        } else {
            $v->hasMinChars($calendar['description'],   16, tr('Please ensure the calendar description has at least 16 characters'));
            $v->hasMaxChars($calendar['description'], 2047, tr('Please ensure the calendar description has less than 2047 characters'));

            $calendar['description'] = str_clean($calendar['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $calendar['seoname'] = seo_unique($calendar['name'], 'calendars', isset_get($calendar['id']));

      return $calendar;

    }catch(Exception $e) {
        throw new CoreException('calendars_validate(): Failed', $e);
    }
}



/*
 * Insert the specified calendar into the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package calendars
 * @see calendars_validate()
 * @see calendars_update()
 * @table: `calendar`
 * @note: This is a note
 * @version 2.5.38: Added function and documentation
 * @example [Title]
 * code
 * $result = calendars_insert(array('foo' => 'bar',
 *                                 'foo' => 'bar',
 *                                 'foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * @param params $calendar The calendar to be inserted
 * @param string $calendar[foo]
 * @param string $calendar[bar]
 * @return params The specified calendar, validated and sanitized
 */
function calendars_insert($calendar) {
    try{
        $calendar = calendars_validate($calendar);

        sql_query('INSERT INTO `calendars` (`createdby`, `meta_id`, `status`, )
                   VALUES                  (:createdby , :meta_id , :status , )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':meta_id'   => meta_action(),
                         ':status'    => $calendar['status']));

        $calendar['id'] = sql_insert_id();

        return $calendar;

    }catch(Exception $e) {
        throw new CoreException('calendars_insert(): Failed', $e);
    }
}



/*
 * Update the specified calendar in the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package calendars
 * @see calendars_validate()
 * @see calendars_insert()
 * @table: `calendar`
 * @note: This is a note
 * @version 2.5.38: Added function and documentation
 * @example [Title]
 * code
 * $result = calendars_update(array('foo' => 'bar',
 *                                 'foo' => 'bar',
 *                                 'foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * @param params $params The calendar to be updated
 * @param string $params[foo]
 * @param string $params[bar]
 * @return boolean True if the user was updated, false if not. If not updated, this might be because no data has changed
 */
function calendars_update($calendar) {
    try{
        $calendar = calendars_validate($calendar);

        meta_action($calendar['meta_id'], 'update');

        $update = sql_query('UPDATE `calendars`

                             SET    ``   = :,
                                    ``   = :

                             WHERE  `id` = :id',

                             array(':id' => $calendar['id'],
                                   ':'   => $calendar[''],
                                   ':'   => $calendar['']));

        return (boolean) $update->rowCount();

    }catch(Exception $e) {
        throw new CoreException('calendars_update(): Failed', $e);
    }
}



/*
 * Return data for the specified calendar
 *
 * This function returns information for the specified calendar. The calendar can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @calendar Function reference
 * @package calendars
 * @version 2.5.38: Added function and documentation
 *
 * @param mixed $calendar The requested calendar. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @param string $status
 * @param string $parent
 * @return mixed The calendar data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified calendar does not exist, NULL will be returned.
 */
function calendars_get($calendar, $column = null, $status = null, $parent = false) {
    try{
        Arrays::ensure($params, 'seocalendar');

        $params['table']   = 'calendars';

        array_default($params, 'filters', array('seoname' => $params['seocalendar'],
                                                'status'  => null));

        array_default($params, 'joins'  , array('LEFT JOIN `geo_countries`
                                                 ON        `geo_countries`.`id` = `customers`.`countries_id`',

                                                'LEFT JOIN `categories`
                                                 ON        `categories`.`id`    = `customers`.`categories_id`'));

        array_default($params, 'columns', 'calendars.id,
                                           calendars.createdon,
                                           calendars.createdby,
                                           calendars.meta_id,
                                           calendars.status,
                                           calendars.name,
                                           calendars.seoname,
                                           calendars.description,

                                           categories.name       AS category,
                                           categories.seoname    AS seocategory,

                                           geo_countries.name    AS country,
                                           geo_countries.seoname AS seocountry');

        return sql_simple_get($params);

    }catch(Exception $e) {
        throw new CoreException('calendars_get(): Failed', $e);
    }
}



/*
 * Return a list of all available calendars filtered by
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @calendar Function reference
 * @see sql_simple_list()
 * @package calendars
 * @version 2.5.38: Added function and documentation
 *
 * @param params $params The list parameters
 * @return mixed The list of available calendars
 */
function calendars_list($params) {
    try{
        Arrays::ensure($params);

        $params['table'] = 'calendars';

        return sql_simple_list($params);

    }catch(Exception $e) {
        throw new CoreException('calendars_list(): Failed', $e);
    }
}



/*
 * Return HTML for a calendars select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available calendars
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @calendar Function reference
 * @package calendars
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
 * @return string HTML for a calendars select box within the specified parameters
 */
function calendars_select($params = null) {
    try{
        Arrays::ensure($params);
        array_default($params, 'name'      , 'seocalendar');
        array_default($params, 'class'     , 'form-control');
        array_default($params, 'selected'  , null);
        array_default($params, 'autosubmit', true);
        array_default($params, 'parents_id', null);
        array_default($params, 'status'    , null);
        array_default($params, 'remove'    , null);
        array_default($params, 'empty'     , tr('No calendars available'));
        array_default($params, 'none'      , tr('Select a calendar'));
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

        $query              = 'SELECT `seoname`, `name` FROM `calendars` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('calendars_select(): Failed', $e);
    }
}



/*
 * Validate the specified calendar event
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @calendar Function reference
 * @package calendars
 *
 * @param array $event The calendar to validate
 * @return array The validated and cleaned $event parameter array
 */
function calendars_validate_event($event) {
    try{
        load_libs('validate,seo');

        $v = new ValidateForm($event, 'name');

        /*
         * Validate foo
         */
        $v->isNotEmpty ($event['name']    , tr('Please specify a calendar name'));
        $v->hasMinChars($event['name'],  2, tr('Please ensure the calendar name has at least 2 characters'));
        $v->hasMaxChars($event['name'], 64, tr('Please ensure the calendar name has less than 64 characters'));

        /*
         * Validate description
         */
        if (empty($event['description'])) {
            $event['description'] = null;

        } else {
            $v->hasMinChars($event['description'],   16, tr('Please ensure the calendar description has at least 16 characters'));
            $v->hasMaxChars($event['description'], 2047, tr('Please ensure the calendar description has less than 2047 characters'));

            $event['description'] = str_clean($event['description']);
        }

        /*
         * All valid?
         */
        $v->isValid();

        /*
         * Set seoname
         */
        $event['seoname'] = seo_unique($event['name'], 'calendars_events', isset_get($event['id']));

      return $event;

    }catch(Exception $e) {
        throw new CoreException('calendars_validate_event(): Failed', $e);
    }
}



/*
 * Insert the specified calendar into the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package calendars
 * @see calendars_validate()
 * @see calendars_update()
 * @table: `calendar`
 * @note: This is a note
 * @version 2.5.38: Added function and documentation
 * @example [Title]
 * code
 * $result = calendars_insert(array('foo' => 'bar',
 *                                 'foo' => 'bar',
 *                                 'foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * @param params $event The calendar to be inserted
 * @param string $event[foo]
 * @param string $event[bar]
 * @return params The specified calendar, validated and sanitized
 */
function calendars_insert_event($event) {
    try{
        $event = calendars_validate_event($event);

        sql_query('INSERT INTO `calendars_events` (`createdby`, `meta_id`, `status`, `calendars_id`)
                   VALUES                         (:createdby , :meta_id , :status , :calendars_id )',

                   array(':createdby'    => isset_get($_SESSION['user']['id']),
                         ':meta_id'      => meta_action(),
                         ':status'       => $event['status'],
                         ':calendars_id' => $event['calendars_id']));

        $event['id'] = sql_insert_id();

        return $event;

    }catch(Exception $e) {
        throw new CoreException('calendars_insert_event(): Failed', $e);
    }
}



/*
 * Update the specified calendar in the database
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package calendars
 * @see calendars_validate()
 * @see calendars_insert()
 * @table: `calendar`
 * @note: This is a note
 * @version 2.5.38: Added function and documentation
 * @example [Title]
 * code
 * $result = calendars_update(array('foo' => 'bar',
 *                                 'foo' => 'bar',
 *                                 'foo' => 'bar'));
 * showdie($result);
 * /code
 *
 * @param params $params The calendar to be updated
 * @param string $params[foo]
 * @param string $params[bar]
 * @return boolean True if the user was updated, false if not. If not updated, this might be because no data has changed
 */
function calendars_update_event($event) {
    try{
        $event = calendars_validate($event);

        meta_action($event['meta_id'], 'update');

        $update = sql_query('UPDATE `calendars_events`

                             SET    `calendars_id` = :calendars_id,
                                    ``             = :

                             WHERE  `id` = :id',

                             array(':id'           => $event['id'],
                                   ':calendars_id' => $event['calendars_id'],
                                   ':'             => $event['']));

        return (boolean) $update->rowCount();

    }catch(Exception $e) {
        throw new CoreException('calendars_update_event(): Failed', $e);
    }
}



/*
 * Return data for the specified calendar
 *
 * This function returns information for the specified calendar. The calendar can be specified by seoname or id, and return data will either be all data, or (optionally) only the specified column
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @calendar Function reference
 * @package calendars
 * @version 2.5.38: Added function and documentation
 *
 * @param mixed $event The requested calendar. Can either be specified by id (natural number) or string (seoname)
 * @param string $column The specific column that has to be returned
 * @param string $status
 * @param string $parent
 * @return mixed The calendar data. If no column was specified, an array with all columns will be returned. If a column was specified, only the column will be returned (having the datatype of that column). If the specified calendar does not exist, NULL will be returned.
 */
function calendars_get_event($event, $column = null, $status = null, $parent = false) {
    try{
        Arrays::ensure($params, 'seocalendar');

        $params['table']   = 'calendars';

        array_default($params, 'filters', array('seoname' => $params['seocalendar'],
                                                'status'  => null));

        array_default($params, 'joins'  , array('LEFT JOIN `geo_countries`
                                                 ON        `geo_countries`.`id` = `customers`.`countries_id`',

                                                'LEFT JOIN `categories`
                                                 ON        `categories`.`id`    = `customers`.`categories_id`'));

        array_default($params, 'columns', 'calendars.id,
                                           calendars.createdon,
                                           calendars.createdby,
                                           calendars.meta_id,
                                           calendars.status,
                                           calendars.name,
                                           calendars.seoname,
                                           calendars.description,

                                           categories.name       AS category,
                                           categories.seoname    AS seocategory,

                                           geo_countries.name    AS country,
                                           geo_countries.seoname AS seocountry');

        return sql_simple_get($params);

    }catch(Exception $e) {
        throw new CoreException('calendars_get_event(): Failed', $e);
    }
}



/*
 * Return a list of all available calendars filtered by
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @calendar Function reference
 * @see sql_simple_list()
 * @package calendars
 * @version 2.5.38: Added function and documentation
 *
 * @param params $params The list parameters
 * @return mixed The list of available calendars
 */
function calendars_list_events($params) {
    try{
        Arrays::ensure($params);

        $params['table'] = 'calendars';

        return sql_simple_list($params);

    }catch(Exception $e) {
        throw new CoreException('calendars_list_events(): Failed', $e);
    }
}



/*
 * Return HTML for a calendars select box
 *
 * This function will generate HTML for an HTML select box using html_select() and fill it with the available calendars
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @calendar Function reference
 * @package calendars
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
 * @return string HTML for a calendars select box within the specified parameters
 */
function calendars_select_event($params = null) {
    try{
        Arrays::ensure($params);
        array_default($params, 'name'      , 'seocalendar');
        array_default($params, 'class'     , 'form-control');
        array_default($params, 'selected'  , null);
        array_default($params, 'autosubmit', true);
        array_default($params, 'parents_id', null);
        array_default($params, 'status'    , null);
        array_default($params, 'remove'    , null);
        array_default($params, 'empty'     , tr('No calendars available'));
        array_default($params, 'none'      , tr('Select a calendar'));
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

        $query              = 'SELECT `seoname`, `name` FROM `calendars_events` '.$where.' ORDER BY `name`';
        $params['resource'] = sql_query($query, $execute);
        $retval             = html_select($params);

        return $retval;

    }catch(Exception $e) {
        throw new CoreException('calendars_select_event(): Failed', $e);
    }
}
?>
