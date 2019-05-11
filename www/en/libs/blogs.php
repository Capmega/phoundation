<?php
/*
 * Blogs library
 *
 * This library contains functions to manage and display blogs and blog entries
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright 2019 Capmega <license@capmega.com>
 * @category Function reference
 * @package blogs
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
 * @package blogs
 *
 * @return void
 */
function blogs_library_init(){
    try{
        load_config('blogs');

    }catch(Exception $e){
        throw new BException('blogs_library_init(): Failed', $e);
    }
}



/*
 * Return requested data for specified blog
 */
function blogs_get($blog = null){
    global $_CONFIG;

    try{
        $query = 'SELECT    `blogs`.`id`,
                            `blogs`.`name`,
                            `blogs`.`status`,
                            `blogs`.`slogan`,
                            `blogs`.`seoname`,
                            `blogs`.`keywords`,
                            `blogs`.`createdon`,
                            `blogs`.`createdby`,
                            `blogs`.`modifiedon`,
                            `blogs`.`description`,
                            `blogs`.`url_template`,

                            `createdby`.`name`   AS `createdby_name`,
                            `createdby`.`email`  AS `createdby_email`,
                            `modifiedby`.`name`  AS `modifiedby_name`,
                            `modifiedby`.`email` AS `modifiedby_email`

                  FROM      `blogs`

                  LEFT JOIN `users` as `createdby`
                  ON        `blogs`.`createdby`     = `createdby`.`id`

                  LEFT JOIN `users` as `modifiedby`
                  ON        `blogs`.`modifiedby`    = `modifiedby`.`id`';

        if($blog){
            if(!is_string($blog)){
                throw new BException(tr('blogs_get(): Specified blog name ":name" is not a string', array(':name' => $blog)), 'invalid');
            }

            $retval = sql_get($query.'

                              WHERE      `blogs`.`seoname` = :seoname
                              AND        `blogs`.`status` IS NULL',

                              array(':seoname' => $blog));

        }else{
            /*
             * Pre-create a new blog
             */
            $retval = sql_get($query.'

                              WHERE  `blogs`.`createdby` = :createdby

                              AND    `blogs`.`status`    = "_new"',

                              array(':createdby' => $_SESSION['user']['id']));

            if(!$retval){
                sql_query('INSERT INTO `blogs` (`createdby`, `status`, `name`)
                           VALUES              (:createdby , :status , :name )',

                           array(':name'      => $blog,
                                 ':status'    => '_new',
                                 ':createdby' => isset_get($_SESSION['user']['id'])));

                return blogs_get($blog);
            }
        }

        return $retval;

    }catch(Exception $e){
        throw new BException('blogs_get(): Failed', $e);
    }
}



/*
 * Get a new or existing blog post
 */
function blogs_post_get($blog = null, $post = null, $language = null, $alternative_language = null){
    global $_CONFIG;

    try{
        if($blog){
            /*
             * Verify the specified blog
             */
            if(is_numeric($blog)){
                $blogs_id = sql_get('SELECT `id` FROM `blogs` WHERE `id`      = :blog AND `status` IS NULL', 'id', array(':blog' => $blog));

            }else{
                $blogs_id = sql_get('SELECT `id` FROM `blogs` WHERE `seoname` = :blog AND `status` IS NULL', 'id', array(':blog' => $blog));
            }

            if(!$blogs_id){
                throw new BException(tr('blogs_post_get(): Specified blog ":blog" does not exist, or is not available because of its status', array(':blog' => $blog)), 'not-exists');
            }
        }

        if(!$post){
            if(empty($blogs_id) and empty($language)){
                throw new BException(tr('blogs_post_get(): No post and no blog and no language specified. For a new post, specify at least a blog and a language'), 'not-specified');
            }

            /*
             * Is there already a post available for this user?
             * If so, use that one
             */
            $post = sql_get('SELECT `id`

                             FROM   `blogs_posts`

                             WHERE  `createdby` = :createdby
                             AND    `blogs_id`  = :blogs_id
                             AND    `language`  = :language
                             AND    `status`    = "_new"
                             LIMIT  1',

                             'id', array(':createdby' => isset_get($_SESSION['user']['id']),
                                         ':language'  => $language,
                                         ':blogs_id'  => $blogs_id));
        }

        if($post){
            if(is_numeric($post)){
                if(empty($language)){
                    /*
                     * Get the post by its id
                     */
                    $where   = ' WHERE `blogs_posts`.`id` = :id';
                    $execute = array(':id' => $post);

                }else{
                    /*
                     * Get the post by its masters id, and get the specified
                     * language
                     */
                    $masters_id = $post;
                    $where      = ' WHERE `blogs_posts`.`masters_id` = :masters_id
                                    AND   `blogs_posts`.`language`   = :language';

                    $execute    = array(':masters_id' => $masters_id,
                                        ':language'   => $language);
                }

            }else{
                /*
                 * Language will have to be specified!
                 */
                if(!$language){
                    if($_CONFIG['language']['supported']){
                        throw new BException(tr('blogs_post_get(): This is a multi-lingual system, but no language was specified for the blog post name ":post"', array(':post' => $post)), 'not-specified');
                    }

                    $where   = ' WHERE `blogs_posts`.`seoname`  = :seoname ';
                    $execute = array(':seoname'  => $post);

                }else{
                    $where   = ' WHERE `blogs_posts`.`seoname`  = :seoname AND `blogs_posts`.`language` = :language ';
                    $execute = array(':seoname'  => $post,
                                     ':language' => $language);
                }
            }

            $retval = sql_get('SELECT    `blogs_posts`.`id`,
                                         `blogs_posts`.`createdon`,
                                         `blogs_posts`.`createdby`,
                                         `blogs_posts`.`modifiedby`,
                                         `blogs_posts`.`modifiedon`,
                                         `blogs_posts`.`status`,
                                         `blogs_posts`.`blogs_id`,
                                         `blogs_posts`.`assigned_to_id`,
                                         `blogs_posts`.`seocategory1`,
                                         `blogs_posts`.`category1`,
                                         `blogs_posts`.`seocategory2`,
                                         `blogs_posts`.`category2`,
                                         `blogs_posts`.`seocategory3`,
                                         `blogs_posts`.`category3`,
                                         `blogs_posts`.`keywords`,
                                         `blogs_posts`.`seokeywords`,
                                         `blogs_posts`.`featured_until`,
                                         `blogs_posts`.`upvotes`,
                                         `blogs_posts`.`downvotes`,
                                         `blogs_posts`.`description`,
                                         `blogs_posts`.`level`,
                                         `blogs_posts`.`priority`,
                                         `blogs_posts`.`views`,
                                         `blogs_posts`.`rating`,
                                         `blogs_posts`.`comments`,
                                         `blogs_posts`.`language`,
                                         `blogs_posts`.`url`,
                                         `blogs_posts`.`urlref`,
                                         `blogs_posts`.`name`,
                                         `blogs_posts`.`seoname`,
                                         `blogs_posts`.`body`,
                                         `blogs_posts`.`parents_id`,
                                         `blogs_posts`.`masters_id`,
                                         `users`.`email` AS `assigned_to`

                               FROM      `blogs_posts`

                               LEFT JOIN `users`
                               ON        `users`.`id` = `blogs_posts`.`assigned_to_id`'.$where, $execute);

            if($retval){
                if($language and ($language !== $retval['language'])){
                    /*
                     * Found the blog page, but its the wrong language
                     * Fetch the right language from the
                     */
                    return blogs_post_get($blog, $retval['masters_id'], $language);
                }

                return $retval;
            }
        }

        /*
         * From here, specified blog post was not found!!! omg omg!
         *
         * Was the blog post requested by name and with language AND
         * alternative_language? Then the alternative language should exist, and
         * the requested name is for that alternative language. Get masters_id
         * for that document and try fetching the document by masters_id and
         * language
         */
        if(!is_numeric($post) and $language and $alternative_language){
            $masters_id = sql_get('SELECT `masters_id` FROM `blogs_posts` WHERE `seoname` = :seoname AND `language` = :language',  true, array(':seoname' => $post, ':language' => $alternative_language));
            return blogs_post_get($blog, $masters_id, $language);
        }

        $priority = blogs_post_get_new_priority($blogs_id);

        sql_query('INSERT INTO `blogs_posts` (`status`, `blogs_id`, `createdby`, `language`, `priority`)
                   VALUES                    ("_new"  , :blogs_id , :createdby , :language , :priority )',

                   array(':createdby' => isset_get($_SESSION['user']['id']),
                         ':language'  => $language,
                         ':priority'  => $priority,
                         ':blogs_id'  => $blogs_id));

        $posts_id = sql_insert_id();

        if(empty($masters_id)){
            $masters_id = $posts_id;
        }

        sql_query('UPDATE `blogs_posts` SET `masters_id` = :masters_id WHERE `id` = :id', array(':id' => $posts_id, ':masters_id' => $masters_id));

        return blogs_post_get($blog, $posts_id, null);

    }catch(Exception $e){
        throw new BException('blogs_post_get(): Failed', $e);
    }
}



/*
 * Return all key_values for the specified blog post
 */
function blogs_post_get_key_values($blogs_posts_id, $seovalues = false){
    try{
        return sql_list('SELECT `seokey`,
                                `'.($seovalues ? 'seo' : '').'value`

                         FROM   `blogs_key_values`

                         WHERE  `blogs_posts_id` = :blogs_posts_id',

                         array(':blogs_posts_id' => $blogs_posts_id));

    }catch(Exception $e){
        throw new BException('blogs_post_get_key_values(): Failed', $e);
    }
}



/*
 * Update the specified blog post and ensure it no longer has status "_new".
 * $params specified what columns are used for this blog post
 */
function blogs_post_update($post, $params = null){
    try{
        array_ensure($params);
        array_default($params, 'sitemap_priority'        , 1);
        array_default($params, 'sitemap_change_frequency', 'weekly');

        $post = blogs_validate_post($post, $params);

        /*
         * Build basic blog post query and execute array
         */
        $execute = array(':id'         => $post['id'],
                         ':modifiedby' => isset_get($_SESSION['user']['id']),
                         ':url'        => $post['url'],
                         ':body'       => $post['body']);

        $query   = 'UPDATE  `blogs_posts`

                    SET     `modifiedby` = :modifiedby,
                            `modifiedon` = NOW(),
                            `url`        = :url,
                            `body`       = :body ';

        /*
         * Add colunmns for update IF they are used only!
         */
        if($params['label_blog']){
            $updates[] = ' `blogs_id` = :blogs_id ';
            $execute[':blogs_id'] = $post['blogs_id'];
        }

        if($params['label_assigned_to']){
            $updates[] = ' `assigned_to_id` = :assigned_to_id ';
            $execute[':assigned_to_id'] = $post['assigned_to_id'];
        }

        if($params['label_featured']){
            $updates[] = ' `featured_until` = :featured_until ';
            $execute[':featured_until'] = get_null($post['featured_until']);
        }

        if($params['label_parent']){
            $updates[] = ' `parents_id` = :parents_id ';
            $execute[':parents_id'] = $post['parents_id'];
        }

        if($params['label_status']){
            $updates[] = ' `status` = :status ';
            $execute[':status'] = $post['status'];

        }else{
            /*
             * New post? Set to default status, and set priority to default
             * (highest of this blog + 1)
             */
            if($post['status'] === '_new'){
                $post['status'] = $params['status_default'];

                $updates[] = ' `status` = :status ';
                $execute[':status'] = $post['status'];

                $priority  = blogs_post_get_new_priority($post['blogs_id']);

                $updates[] = ' `priority` = :priority ';
                $execute[':priority'] = $priority;
            }
        }

        /*
         * Convert category input labels to standard categoryN names
         */
        for($i = 1; $i <= 3; $i++){
            if($params['label_category'.$i]){
                $updates[] = ' `category'.$i.'`    = :category'.$i.' ';
                $updates[] = ' `seocategory'.$i.'` = :seocategory'.$i.' ';

                $execute[':category'.$i]    = $post['category'.$i];
                $execute[':seocategory'.$i] = $post['seocategory'.$i];
            }
        }

        if($params['label_level']){
            $updates[] = ' `level` = :level ';
            $execute[':level'] = $post['level'];
        }

        if($params['label_language']){
            $updates[] = ' `language` = :language ';
            $execute[':language'] = $post['language'];
        }

        if($params['label_keywords']){
            $updates[] = ' `keywords`    = :keywords ';
            $updates[] = ' `seokeywords` = :seokeywords ';

            $execute[':keywords']    = $post['keywords'];
            $execute[':seokeywords'] = $post['seokeywords'];
        }

        if($params['label_description']){
            $updates[] = ' `description` = :description ';
            $execute[':description'] = $post['description'];
        }

        if($params['label_status']){
            $updates[] = ' `status` = :status ';
            $execute[':status'] = $post['status'];
        }

        if($post['urlref']){
            $updates[] = ' `urlref` = :urlref ';
            $execute[':urlref'] = $post['urlref'];
        }

        if($params['label_title']){
            $updates[] = ' `name`    = :name ';
            $updates[] = ' `seoname` = :seoname ';

            $execute[':name']    = $post['name'];
            $execute[':seoname'] = $post['seoname'];
        }

        if(!empty($updates)){
            $query .= ', '.implode(', ', $updates);
        }

        $query .= ' WHERE `id` = :id';

        /*
         * Update the post
         * First get the original URL so we can compare against new $post[url]
         * We'll need that because if the url changed, sitemap below will have
         * to drop a now no longer existing URL entry
         */
        $url = sql_get('SELECT `url` FROM `blogs_posts` WHERE `id` = :id', true, array(':id' => $post['id']));

        sql_query($query, $execute);

        /*
         * Update keywords and key_value store
         */
        blogs_update_keywords($post);
        blogs_update_key_value_store($post, isset_get($params['key_values']));

        /*
         * Add this new escort to the sitemap table.
         */
        load_libs('sitemap');

        if($url != $post['url']){
            /*
             * Page URL changed, delete old entry from the sitemap table to
             * avoid it still showing up in sitemaps, since this page is now 404
             */
            sitemap_delete_entry($url);
        }

        if(isset_get($post['status']) === 'published'){
            sitemap_insert_entry(array('url'              => $post['url'],
                                       'language'         => $post['language'],
                                       'priority'         => $params['sitemap_priority'],
                                       'page_modifiedon'  => date_convert(null, 'mysql'),
                                       'change_frequency' => $params['sitemap_change_frequency']));
        }

        run_background('base/sitemap update --env '.ENVIRONMENT);

        return $post;

    }catch(Exception $e){
        throw new BException(tr('blogs_post_update(): Failed'), $e);
    }
}



/*
 * Update the status for the specified blog posts
 */
function blogs_update_post_status($blog, $params, $list, $status){
    try{
        load_libs('sitemap');

        array_ensure($params);
        array_default($params, 'sitemap_priority'        , 1);
        array_default($params, 'sitemap_change_frequency', 'daily');

        $count   = 0;
        $execute = array(':blogs_id' => $blog['id'],
                         ':status'   => $status);

        $update  = sql_prepare('UPDATE  `blogs_posts`

                                SET     `status`   = :status

                                WHERE   `id`       = :id
                                AND     `blogs_id` = :blogs_id');

        foreach($list as $id){
            $post = sql_get('SELECT `id`, `url`, `seoname`, `language`, `status` FROM `blogs_posts` WHERE `id` = :id', array(':id' => $id));

            if(!$post){
                /*
                 * This post doesn't exist
                 */
                log_console(tr('blogs_update_post_status(): The specified blogs_post ":id" does not exist', array(':id' => $id)), 'yellow');
                continue;
            }

            /*
             * Clear affected objects from cache
             */
            cache_clear(null, 'blogpages_'.$post['seoname']);
            cache_clear(null, 'blogpage_'.$post['seoname']);

            /*
             * Update sitemap for the posts
             */
            switch($status){
                case 'erased':
                    // FALLTHROUGH
                case 'deleted':
                    // FALLTHROUGH
                case 'unpublished':
// :TODO: sitemap script can detect new and updated links, it cannot detect deleted links, so with deleting we always force generation of new sitemap files. Make a better solution for this
                    $force = true;
                    sitemap_delete_entry($post['url']);
                    break;

                case 'published':
                    if($post['url']){
                        sitemap_insert_entry(array('url'              => $post['url'],
                                                   'language'         => $post['language'],
                                                   'priority'         => $params['sitemap_priority'],
                                                   'page_modifiedon'  => date_convert(null, 'mysql'),
                                                   'change_frequency' => $params['sitemap_change_frequency']));
                    }
            }

            $execute[':id'] = $id;
            $update->execute($execute);
            $count += $update->rowCount();
        }

        if(!$count){
            throw new BException(tr('Found no :object to :status', array(':object' => $params['object_name'], ':status' => $status)), 'not-exists');
        }

        /*
         * Process sitemap
         */
        if(empty($force)){
            run_background('base/sitemap update');

        }else{
            run_background('base/sitemap generate');
        }

        return $count;

    }catch(Exception $e){
        throw new BException('blogs_update_post_status(): Failed', $e);
    }
}



/*
 * List available blogs
 */
function blogs_list($user, $from = null, $until = null, $limit = null){
    try{
        if(is_array($user)){
            $user = isset_get($user['id']);
        }

        $execute =  array();

        $query   = 'SELECT `addedon`,
                           `rights_id`,
                           `name`

                    FROM   `blogs_posts`

                    WHERE  `status`  = "posted"';

        if($user){
            $query    .= ' AND `createdby` = :createdby';
            $execute[] = array(':createdby' => $user);
        }

        if($from){
            $query    .= ' AND `addedon` >= :from';
            $execute[] = array(':from' => $from);
        }

        if($until){
            $query    .= ' AND `addedon` <= :until';
            $execute[] = array(':until' => $until);
        }

        if($limit){
            $query    .= ' LIMIT '.cfi($limit);
        }

        return sql_list($query, $execute);

    }catch(Exception $e){
        throw new BException('blogs_list(): Failed', $e);
    }
}



/*
 * Set the status of the specified blog
 */
function blogs_post($blog){
    try{
        /*
         * Only users may post blogs
         */
        user_or_signin();

        if(is_array($blog)){
            $blog = isset_get($blog['id']);
        }

        if(!$blog){
            throw new BException('blogs_post(): No blog specified', 'not-specified');
        }

        $execute = array(':id' => $blog);

        $query   = 'UPDATE `blogs_posts`

                    SET    `status` = "posted"

                    WHERE  `id`     = :id';

        if(!has_rights('admin')){
            /*
             * Only the user itself can post this
             */
            $query               .= ' AND `createdby` = :createdby';
            $execute[':createdby']  = $_SESSION['user']['id'];
        }

        return sql_query($query, $execute);

    }catch(Exception $e){
        throw new BException('blogs_post(): Failed', $e);
    }
}



/*
 * Return HTML select list containing all available blogs
 */
function blogs_select($params, $selected = 0, $name = 'blog', $none = '', $class = '', $option_class = '', $disabled = false) {
    try{
        array_params ($params, 'seoname');
        array_default($params, 'selected'    , $selected);
        array_default($params, 'class'       , $class);
        array_default($params, 'disabled'    , $disabled);
        array_default($params, 'name'        , $name);
        array_default($params, 'none'        , not_empty($none, tr('Select a blog')));
        array_default($params, 'option_class', $option_class);

        $params['resource'] = sql_query('SELECT   `seoname` AS id,
                                                  `name`

                                         FROM     `blogs`

                                         WHERE    `status` IS NULL

                                         ORDER BY `name` ASC');

        return html_select($params);

    }catch(Exception $e){
        throw new BException('blogs_select(): Failed', $e);
    }
}



/*
 * Return HTML select list containing all available blog categories
 */
function blogs_categories_select($params) {
    try{
        array_params ($params);
        array_default($params, 'selected'    , 0);
        array_default($params, 'class'       , '');
        array_default($params, 'disabled'    , false);
        array_default($params, 'name'        , 'seocategory');
        array_default($params, 'column'      , '`blogs_categories`.`seoname`');
        array_default($params, 'none'        , tr('Select a category'));
        array_default($params, 'empty'       , tr('No categories available'));
        array_default($params, 'option_class', '');
        array_default($params, 'right'       , false);
        array_default($params, 'parent'      , false);
        array_default($params, 'filter'      , array());

        if(empty($params['blogs_id'])){
            /*
             * Categories work per blog, so without a blog we cannot show
             * categories
             */
            $params['resource'] = null;

        }else{
            $execute = array(':blogs_id' => $params['blogs_id']);

            $query   = 'SELECT  '.$params['column'].' AS id,
                                `blogs_categories`.`name`

                        FROM    `blogs_categories` ';

            $join    = '';

            $where   = 'WHERE   `blogs_categories`.`blogs_id` = :blogs_id
                        AND     `blogs_categories`.`status`   IS NULL ';

            if($params['right']){
                /*
                 * User must have right of the category to be able to see it
                 */
                $join .= ' JOIN `users_rights`
                           ON   `users_rights`.`users_id` = :users_id
                           AND (`users_rights`.`name`     = `blogs_categories`.`seoname`
                           OR   `users_rights`.`name`     = "god") ';

                $execute[':users_id'] = isset_get($_SESSION['user']['id']);
            }

            if($params['parent']){
                $join .= ' JOIN `blogs_categories` AS parents
                           ON   `parents`.`seoname` = :parent
                           AND  `parents`.`id`      = `blogs_categories`.`parents_id` ';

                $execute[':parent'] = $params['parent'];

            }elseif($params['parent'] === null){
                $where .= ' AND  `blogs_categories`.`parents_id` IS NULL ';

            }elseif($params['parent'] === false){
                /*
                 * Don't filter for any parent
                 */

            }else{
                $where .= ' AND `blogs_categories`.`parents_id` = 0 ';

            }

            /*
             * Filter specified values.
             */
            foreach($params['filter'] as $key => $value){
                if(!$value) continue;

                $where            .= ' AND `'.$key.'` != :'.$key.' ';
                $execute[':'.$key] = $value;
            }

            $params['resource'] = sql_query($query.$join.$where.' ORDER BY `name` ASC', $execute);
        }

        return html_select($params);

    }catch(Exception $e){
        throw new BException('blogs_categories_select(): Failed', $e);
    }
}



/*
 * Return HTML select list containing all available parent posts
 */
function blogs_parents_select($params) {
    try{
        array_params ($params);
        array_default($params, 'selected'    , null);
        array_default($params, 'class'       , '');
        array_default($params, 'disabled'    , false);
        array_default($params, 'name'        , 'seoparent');
        array_default($params, 'column'      , '`blogs_posts`.`seoname`');
        array_default($params, 'none'        , tr('Select a parent'));
        array_default($params, 'empty'       , tr('No parents available'));
        array_default($params, 'blogs_id'    , null);
        array_default($params, 'filter'      , array());

        if(empty($params['blogs_id'])){
            throw new BException('blogs_parents_select(): No blog specified', 'not-specified');
        }

        $execute = array(':blogs_id' => $params['blogs_id']);

        $query   = 'SELECT   '.$params['column'].' AS id,
                             `blogs_posts`.`name`

                    FROM     `blogs_posts`';

        $where[] = '`blogs_posts`.`status`   = "published"';

        if($params['blogs_id']){
            $where[]              = ' `blogs_posts`.`blogs_id` = :blogs_id ';
            $execute[':blogs_id'] = $params['blogs_id'];
        }

        /*
         * Filter specified values.
         */
        if(!empty($params['filter'])){
            foreach($params['filter'] as $key => $value){
                if(!$value) continue;

                $query            .= ' AND `'.$key.'` != :'.$key.' ';
                $execute[':'.$key] = $value;
            }
        }

        if(!empty($where)){
            $query .= ' WHERE '.implode(' AND ', $where);
        }

        $query  .= ' ORDER BY `name` ASC';

        $params['resource'] = sql_query($query, $execute);

        return html_select($params);

    }catch(Exception $e){
        throw new BException('blogs_parents_select(): Failed', $e);
    }
}



///*
// * Return HTML select list containing all available blogs
// */
//function blogs_priorities_select($params, $selected = 0) {
//    try{
//        array_params ($params, 'seoname');
//        array_default($params, 'selected'    , $selected);
//        array_default($params, 'class'       , $class);
//        array_default($params, 'disabled'    , $disabled);
//        array_default($params, 'name'        , $name);
//        array_default($params, 'none'        , not_empty($none, tr('Select a level')));
//        array_default($params, 'option_class', $option_class);
//        array_default($params, 'filter'      , array());
//
//        if(empty($params['blogs_id'])){
//            throw new BException('blogs_priorities_select(): No blog specified', 'not-specified');
//        }
//
//        $params['resource'] = array(4 => tr('Low'),
//                                    3 => tr('Normal'),
//                                    2 => tr('High'),
//                                    1 => tr('Urgent'),
//                                    0 => tr('Immediate'));
//
//        return html_select($params);
//
//    }catch(Exception $e){
//        throw new BException('blogs_priorities_select(): Failed', $e);
//    }
//}



/*
 * Update the key-value store for this blog post
 */
function blogs_update_key_value_store($post, $limit_key_values){
    try{
        load_libs('seo');
        sql_query('DELETE FROM `blogs_key_values` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post['id']));

        if(empty($post['key_values'])){
            /*
             * There are no key_values for this post
             */
            return false;
        }

        if($limit_key_values){
// :TODO: Implement
            //foreach($limit_key_values as $data){
            //    foreach($post['key_values'] as $seokey => $seovalue){
            //        if($data['name'] == $seokey){
            //            if(!empty($data['resource'])){
            //                /*
            //                 * This key-value is from a list, get the real value.
            //                 */
            //                if(empty($data['resource'][$seovalue])){
            //                    if($seovalue){
            //                        throw new BException(tr('blogs_update_key_value_store(): Key ":key" has unknown value ":value"', array(':key' => $seokey, ':value' => $seovalue)),  'unknown');
            //                    }
            //
            //                    $seovalue = null;
            //                    $value    = null;
            //
            //                }else{
            //                    $value = $data['resource'][$seovalue];
            //                }
            //            }
            //
            //            break;
            //        }
            //    }
            //}
        }

        /*
         * Scalars before arrays because the arrays can contain parents that are defined in the scalars.
         */
        uasort($post['key_values'], 'blogs_update_key_value_sort');

        $p = sql_prepare('INSERT INTO `blogs_key_values` (`blogs_id`, `blogs_posts_id`, `parent`, `key`, `seokey`, `value`, `seovalue`)
                          VALUES                         (:blogs_id , :blogs_posts_id , :parent , :key , :seokey , :value , :seovalue )');

        foreach($post['key_values'] as $key => $values){
            if(!is_array($values)){
                $values = array($values);
            }

            $parent = isset_get($values['parent']);
            unset($values['parent']);

            foreach($values as $value){
                $p->execute(array(':blogs_id'       => $post['blogs_id'],
                                  ':blogs_posts_id' => $post['id'],
                                  ':parent'         => $parent,
                                  ':key'            => $key,
                                  ':seokey'         => seo_create_string($key),
                                  ':value'          => $value,
                                  ':seovalue'       => seo_create_string($value)));
            }

            switch($key){
                case 'longitude':
                    // FALLTROUGH
                case 'latitude':
                    $user_execute[':'.$key] = array_shift($values);
            }
        }

        if(!empty($post['assigned_to_id']) and isset($user_execute)){
            /*
             * Update assigned user long/lat data with the blog information
             */
            $user_execute[':id'] = $post['assigned_to_id'];

            sql_query('UPDATE `users`

                       SET    `longitude` = :longitude,
                              `latitude`  = :latitude

                       WHERE  `id`        = :id', $user_execute);
        }

    }catch(Exception $e){
        throw new BException('blogs_update_key_value_store(): Failed', $e);
    }
}



/*
 * Sort key_value arrays
 * scalars before arrays
 * then order by parent
 * then order by value
 */
function blogs_update_key_value_sort($a, $b){
    try{
        if(is_scalar($a)){
            /*
             * A is scalar
             */
            if(is_scalar($b)){
                /*
                 * A and B are scalar
                 */
                return 0;
            }

            /*
             * A is scalar, B is array
             */
            return -1;
        }

        /*
         * A is array
         */
        if(is_array($b)){
            /*
             * A and B are array
             */
            return 0;

        }

        /*
         * A is array, B is scalar
         */
        return 1;

    }catch(Exception $e){
        throw new BException('blogs_update_key_value_sort(): Failed', $e);
    }
}



/*
 * Update the keywords in the blogs_keywords table and the
 * seokeywords column in the blogs_posts table
 */
function blogs_update_keywords($post){
    try{
        /*
         * Ensure all keywords of this blog post are gone
         */
        sql_query('DELETE FROM `blogs_keywords` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post['id']));

        /*
         * Store the keywords
         */
        $p = sql_prepare('INSERT INTO `blogs_keywords` (`blogs_id`, `blogs_posts_id`, `name`, `seoname`)
                          VALUES                       (:blogs_id , :blogs_posts_id , :name , :seoname )');

        foreach(array_force($post['keywords'], ',') as $keyword){
            if(strlen($keyword) < 2) continue;

            $p->execute(array(':blogs_id'       => $post['blogs_id'],
                              ':blogs_posts_id' => $post['id'],
                              ':name'           => mb_trim($keyword),
                              ':seoname'        => seo_create_string($keyword)));
        }

    }catch(Exception $e){
        throw new BException('blogs_update_keywords(): Failed', $e);
    }
}



/*
 * Return keywords string for the specified keyword string where all keywords are trimmed
 */
function blogs_clean_keywords($keywords, $allow_empty = false){
    try{
        if(!$keywords and $allow_empty){
            return '';
        }

        $retval = array();

        foreach(array_force($keywords) as $keyword){
            $retval[] = mb_trim($keyword);
        }

        $retval = array_unique($retval);

        if(count($retval) > 15){
            throw new BException('blogs_clean_keywords(): Too many keywords. Do not use more than 15 keywords', 'invalid');
        }

        $retval = implode(',', $retval);

        return $retval;

    }catch(Exception $e){
        throw new BException('blogs_clean_keywords(): Failed', $e);
    }
}



/*
 * Return the seokeywords as a csv string
 */
function blogs_seo_keywords($keywords){
    try{
        $retval = array();

        foreach(array_force($keywords) as $keyword){
            $retval[] = seo_create_string($keyword);
        }

        return implode(',', $retval);

    }catch(Exception $e){
        throw new BException('blogs_generate_seokeywords(): Failed', $e);
    }
}



/*
 * Validate all blog data
 */
function blogs_validate($blog){
    try{
        load_libs('seo,validate');

        /*
         * Validate input
         */
        $v = new ValidateForm($blog, 'id,name,url_template,keywords,slogan,description');
        $v->isNatural($blog['id'], tr('Please ensure that the specified post id is a natural number; numeric, integer, and > 0'));

        if(is_numeric($blog['name'])){
            throw new BException(tr('Blog post name can not be numeric'), 'invalid');
        }

        $v->isNotEmpty($blog['name'], tr('Please provide a name for your blog'));

        if($blog['id']){
            if(sql_get('SELECT `id` FROM `blogs` WHERE `name` = :name AND `id` != :id', array(':id' => $blog['id'], ':name' => $blog['name']), 'id')){
                /*
                 * Another category with this name already exists in this blog
                 */
                $v->setError(tr('A blog with the name ":blog" already exists', array(':blog' => $blog['name'])));
            }

        }else{
            if(sql_get('SELECT `id` FROM `blogs` WHERE `name` = :name', 'id', array(':name' => $blog['name']))){
                $v->setError(tr('A blog with the name ":blog" already exists', array(':blog' => $blog['name'])));
            }
        }

        $v->isValid();

        $blog['seoname'] = seo_unique($blog['name'], 'blogs', $blog['id']);

        return $blog;

    }catch(Exception $e){
        throw new BException('blogs_validate(): Failed', $e);
    }
}



/*
 * Validate the specified category data
 */
function blogs_validate_category($category, $blog){
    try{
        load_libs('seo');
        $v = new ValidateForm($category, 'name,seoname,keywords,description,parent,assigned_to');

        $v->isNotEmpty ($category['name']            , tr('Please provide the name of your category'));
        $v->hasMinChars($category['name']       ,   3, tr('Please ensure that the name has a minimum of 3 characters'));
        $v->hasMaxChars($category['keywords']   , 255, tr('Please ensure that the keywords have a maximum of 255 characters'));
        $v->hasMaxChars($category['description'], 160, tr('Please ensure that the description has a maximum of 160 characters'));

        if(empty($category['parent'])){
            $category['parents_id'] = null;

        }else{
            /*
             * Make sure the parent category is inside this blog
             */
            if(!$parent = sql_get('SELECT `id`, `blogs_id` FROM `blogs_categories` WHERE `seoname` = :seoname', array(':seoname' => $category['parent']))){
                /*
                 * Specified parent does not exist at all
                 */
                throw new BException(tr('The specified parent category ":parent" does not exist', array(':parent' => $category['parent'])), 'not-exists');
            }

// :DELETE: parents_id can be blog post from any blog
            //if($parent['blogs_id'] != $blog['id']){
            //    /*
            //     * Specified parent does not exist inside this blog
            //     */
            //    throw new BException('The specified parent category does not exist in this blog', 'not-exists');
            //}

            $category['parents_id'] = $parent['id'];
        }

        $v->isValid();

        if(!empty($category['id'])){
            if(sql_get('SELECT `id` FROM `blogs_categories` WHERE `blogs_id` = :blogs_id AND `name` = :name AND `id` != :id', array(':blogs_id' => $blog['id'], ':id' => $category['id'], ':name' => $category['name']), 'id')){
                /*
                 * Another category with this name already exists in this blog
                 */
                $v->setError(tr('A category with the name ":category" already exists in the blog ":blog"', array(':category' => $category['name'], ':blog' => $blog['name'])));
            }

        }else{
            if(sql_get('SELECT `id` FROM `blogs_categories` WHERE `blogs_id` = :blogs_id AND `name` = :name', 'id', array(':blogs_id' => $blog['id'], ':name' => $category['name']))){
                $v->setError(tr('A category with the name ":category" already exists in the blog ":blog"', array(':category' => $category['name'], ':blog' => $blog['name'])));
            }
        }

        if($category['assigned_to']){
            if(!$category['assigned_to_id'] = sql_get('SELECT `id` FROM `users` WHERE `username` = :username OR `email` = :email', 'id', array(':username' => $category['assigned_to'], ':email' => $category['assigned_to']))){
                $v->setError(tr('The specified user ":user" does not exist', array(':user' => $category['assigned_to'])));
            }

        }else{
            $category['assigned_to_id'] = null;
        }

        $category['seoname']     = seo_unique(array('seoname' => $category['name'], 'blogs_id' => $blog['id']), 'blogs_categories', empty($category['id']));
        $category['keywords']    = blogs_clean_keywords($category['keywords'], true);
        $category['seokeywords'] = blogs_seo_keywords($category['keywords']);

        $v->isValid();

        return $category;

    }catch(Exception $e){
        throw new BException('blogs_validate_category(): Failed', $e);
    }
}



/*
 * Ensure that all post data is okay
 */
function blogs_validate_post($post, $params = null){
    global $_CONFIG;

    try{
        array_ensure($params);
        array_default($params, 'force_id'         , false);
        array_default($params, 'use_id'           , false);
        array_default($params, 'namemax'          , 64);
        array_default($params, 'bodymin'          , 100);
        array_default($params, 'label_keywords'   , true);
        array_default($params, 'label_category1'  , false);
        array_default($params, 'label_category2'  , false);
        array_default($params, 'label_category3'  , false);
        array_default($params, 'level'            , 1);
        array_default($params, 'change_frequency' , 'weekly');
        array_default($params, 'status_default'   , 'unpublished');
        array_default($params, 'object_name'      , 'blog posts');
        array_default($params, 'parents_empty'    , true);
// :TODO: Make this configurable from `blogs` configuration table
        array_default($params, 'filter_html'      , '<p><a><br><span><small><strong><img><iframe><h1><h2><h3><h4><h5><h6><ul><ol><li>');
//        array_default($params, 'filter_attributes', '/<([a-z][a-z0-9]*)(?: .*?=".*?")*?(\/?)>/imus');  // Filter only class and style attributes
        array_default($params, 'filter_attributes', '/<([a-z][a-z0-9]*)(?: style=".*?)>/imus');  // Filter only class and style attributes
//        array_default($params, 'filter_attributes', '[^>]'); // Filter all attributes

        load_libs('seo,validate');

        /*
         * Validate input
         */
        $v = new ValidateForm($post, 'id,name,featured_until,assigned_to,seocategory1,seocategory2,seocategory3,category1,category2,category3,body,keywords,description,language,level,urlref,status');

        for($i = 1; $i <= 3; $i++){
            /*
             * Translate categories
             */
            $post['category'.$i] = isset_get($post[$params['category'.$i]]);

            if(empty($params['label_category'.$i])){
                $post['category'.$i]    = null;
                $post['seocategory'.$i] = null;

            }else{
                if(empty($post['category'.$i])){
                    if(!empty($params['errors']['category'.$i.'_required'])){
                        /*
                         * Category required
                         */
                        $v->setError($params['errors']['category'.$i.'_required']);

                    }else{
                        $post['category'.$i]    = null;
                        $post['seocategory'.$i] = null;
                    }

                }else{
                    $category = blogblogs_validate_category($post['category'.$i], $post['blogs_id'], isset_get($params['categories'.$i.'_parent']));

                    $post['category'.$i]    = $category['name'];
                    $post['seocategory'.$i] = $category['seoname'];
                }
            }
        }

// :DELETE: The following 5 lines cause key_values to no longer update as the key_values values are not loaded. Since data merging now should be done outside of validation functions (And in this case are done in blog_post), these lines should be removed
        ///*
        // * Merge in from old DB post
        // */
        //$db_post = blogs_post_get($post['blogs_id'], $post['id']);
        //$post    = sql_merge($db_post, $post, (empty($params['label_status']) ? 'id,status' : 'id'));

        /*
         * Just ensure that the specified id is a valid number
         */
        if(!$post['id']){
            throw new BException(tr('Blog post has no id specified'), 'not-specified');
        }

        $v->isNatural($post['id'], tr('Please ensure that the specified post id is a natural number; numeric, integer, and > 0'));

        if(is_numeric($post['name'])){
            throw new BException(tr('Blog post name can not be numeric'), 'invalid');
        }

        $v->isNotEmpty($post['name']    , tr('Please provide a name for your :objectname'      , array(':objectname' => $params['object_name'])));
        $v->isNotEmpty($post['blogs_id'], tr('Please provide a blog for your :objectname'      , array(':objectname' => $params['object_name'])));
        $v->isNumeric ($post['blogs_id'], tr('Please provide a valid blog for your :objectname', array(':objectname' => $params['object_name'])));

        $id = sql_get('SELECT `id` FROM `blogs_posts` WHERE `blogs_id` = :blogs_id AND `id` = :id', 'id', array(':blogs_id' => $post['blogs_id'], ':id' => $post['id']));

        if(!$id){
            /*
             * This blog post does not exist
             */
            throw new BException(tr('Can not update blog ":blog" post ":name", it does not exist', array(':blog' => $post['blogs_id'], ':name' => $post['name'])), 'not-exists');
        }

        if(empty($params['allow_duplicate_name'])){
            if($_CONFIG['language']['supported']){
                /*
                 * Multilingual site!
                 */
                $exists = sql_get('SELECT `id` FROM `blogs_posts` WHERE `blogs_id` = :blogs_id AND `name` = :name AND `language` = :language AND `id` != :id LIMIT 1', array(':blogs_id' => $post['blogs_id'], ':id' => $id, ':name' => $post['name'], ':language' => $post['language']), 'id');

                if($exists){
                    /*
                     * Another post with this name already exists
                     */
                    $v->setError(tr('A post with the name ":name" already exists for the language ":language"', array(':name' => $post['name'], ':language' => $post['language'])), $params['object_name']);
                }

            }else{
                $exists = sql_get('SELECT `id` FROM `blogs_posts` WHERE `blogs_id` = :blogs_id AND `id` != :id AND `name` = :name LIMIT 1', array(':blogs_id' => $post['blogs_id'], ':id' => $id, ':name' => $post['name']), 'id');

                if($exists){
                    /*
                     * Another post with this name already exists
                     */
                    $v->setError(tr('A post with the name ":name" already exists', array(':name' => $post['name'])), $params['object_name']);
                }
            }
        }

        if(!empty($params['label_append'])){
            /*
             * Only allow data to be appended to this post
             * Find changes between current and previous state and store those as well
             */
            load_libs('user');

            $changes = array();
            $oldpost = sql_get('SELECT `assigned_to_id`, `level`, `status`, `name`, `urlref`, `body` FROM `blogs_posts` WHERE `id` = :id', array(':id' => $id));

        }else{
            /*
             * Only if we're editing in label_append mode we don't have to check body size
             */
            if($params['bodymin']){
                /*
                 * bodymin will be very small when using append mode because appended messages may be as short as "ok!"
                 */
                if(empty($params['label_append'])){
                    $params['bodymin'] = 1;
                }

                $v->hasMinChars($post['body'], $params['bodymin'], tr('Please ensure that the body text has a minimum of :bodymin characters', array(':bodymin' => $params['bodymin'])));
                $v->isNotEmpty ($post['body']                    , tr('Please provide the body text of your :objectname', array(':objectname' => $params['object_name'])));
            }
        }

        $v->isChecked  ($post['name']   , tr('Please provide the name of your :objectname'     , array(':objectname' => $params['object_name'])));
        $v->hasMinChars($post['name'], 1, tr('Please ensure that the name has a minimum of 1 character'));

        if(empty($params['label_parent'])){
            $post['parents_id'] = null;

        }else{
            try{
                if($post['parents_id']){
                    /*
                     * Validate that the specified parent is part of the required parent blog
                     */
                    $post['parents_id'] = blogs_validate_parent($post['parents_id'], $params['use_parent']);

                }else{
                    /*
                     * No parent was specified. Is this allowed?
                     */
                    if(empty($params['parents_empty'])){
                        $v->setError(tr('Please select a :object', array(':object' => $params['label_parent'])));

                    }else{
                        $post['parents_id'] = null;
                    }
                }

            }catch(Exception $e){
                switch($e->getCode()){
                    case 'not-member':
                        // FALLTHROUGH
                    case 'not-specified':
                        throw $e->makeWarning(true);

                    default:
                        /*
                         * Unknown error, keep on throwing
                         */
                        throw $e;
                }

            }
        }

        /*
         * Continue validation
         */
        if(empty($params['label_keywords'])){
            $post['keywords']    = '';
            $post['seokeywords'] = '';

        }else{
            $post['keywords']    = blogs_clean_keywords($post['keywords']);
            $post['seokeywords'] = blogs_seo_keywords($post['keywords']);

            $v->hasMinChars($post['keywords'], 1, tr('Please ensure that the keywords have a minimum of 1 character'));
            $v->isNotEmpty ($post['keywords'],    tr('Please provide keywords for your :objectname', array(':objectname' => $params['object_name'])));
        }

        if(empty($post['assigned_to'])){
            $post['assigned_to_id'] = null;

        }else{
            $post['assigned_to_id'] = sql_get('SELECT `id` FROM `users` WHERE `email` = :email', 'id', array(':email' => $post['assigned_to']));

            if(!$post['assigned_to_id']){
                $v->setError(tr('The specified assigned-to-user ":assigned_to" does not exist', array(':assigned_to' => $post['assigned_to'])));
            }
        }

        if(!empty($params['label_featured'])){
            if($post['featured_until']){
                $post['featured_until'] = date_convert($post['featured_until'], 'mysql');

            }else{
                $post['featured_until'] = null;
            }
        }

        if(!empty($params['label_status'])){
            if(!isset($params['status_list'][$post['status']])){
                if($post['status'] and ($post['status'] != $params['status_default'])){
                    $v->setError(tr('The specified status ":status" is invalid, it must be either one of ":status_list"', array(':status' => $post['status'], ':status_list' => str_force($params['status_list']))));

                }else{
                    $post['status'] = $params['status_default'];
                }
            }
        }

        if(!empty($params['label_language']) and $_CONFIG['language']['supported']){
            $v->isNotEmpty($post['language'],    tr('Please select a language for your :objectname', array(':objectname' => $params['object_name'])));
            $v->isRegex($post['language'], '/\w{2}/', tr('Please provide a valid language'));

            if(empty($_CONFIG['language']['supported'][$post['language']])){
                $v->setError(tr('Please provide a valid language, must be one of ":languages"', array(':languages' => $post['language'])));
            }

        }else{
            $post['language'] = 'en';
        }

        if(!empty($params['label_levels'])){
            $v->isNotEmpty ($post['level'], tr('Please provide a level for your :objectname', array(':objectname' => $params['object_name'])));

// :TODO: Check against level list, or min-max
            //if(!is_numeric($post['level']) or ($post['level'] < 1) or ($post['level'] > 5) or (fmod($post['level'], 1))){
            //    $v->setError('The specified level "'.$post['level']).'" is invalid, it must be one of 1, 2, 3, 4, or 5');
            //}
        }

        if(!empty($params['label_description'])){
            $v->isNotEmpty ($post['description'],      tr('Please provide a description for your :objectname', array(':objectname' => $params['object_name'])));
            $v->hasMinChars($post['description'],   4, tr('Please ensure that the description has a minimum of 4 characters'));
            $v->hasMaxChars($post['description'], 160, tr('Please ensure that the description has a maximum of 160 characters'));
        }

        if(!empty($params['label_status'])){
            if(empty($params['status_list'][$post['status']])){
                $v->setError(tr('Please provide a valid status for your :objectname', array(':objectname' => $params['object_name'])));
            }
        }

        $v->isValid();

        /*
         * Set extra parameters
         */
        $post['seoname']  = seo_unique($post['name'], 'blogs_posts', $id);
        $post['url']      = blogs_post_url($post);

        /*
         * Append post to current body?
         */
        if(!empty($params['label_append'])){
            /*
             * Only allow data to be appended to this post
             * Find changes between current and previous state and store those as well
             */
            load_libs('user');

            $changes      = array();
            $oldpost      = sql_get('SELECT `assigned_to_id`, `level`, `status`, `name`, `urlref`, `seocategory1`, `seocategory2`, `seocategory3`, `body` FROM `blogs_posts` WHERE `id` = :id', array(':id' => $id));

            if(isset_get($oldpost['assigned_to_id']) != $post['assigned_to_id']){
                $user = sql_get('SELECT `id`, `name`, `username`, `email` FROM `users` WHERE `id` = :id', array(':id' => $post['assigned_to_id']));

                if(isset_get($oldpost['assigned_to_id'])){
                    $changes[] = tr('Re-assigned post to ":user"', array(':user' => name($user)));

                }else{
                    $changes[] = tr('Assigned post to ":user"', array(':user' => name($user)));
                }
            }

            if(isset_get($oldpost['level']) != $post['level']){
                $changes[] = tr('Set level to ":level"', array(':level' => blogs_level($post['level'])));
            }

            if(isset_get($oldpost['urlref']) != $post['urlref']){
                $changes[] = tr('Set URL to ":url"', array(':url' => $post['urlref']));
            }

            if(isset_get($oldpost['name']) != $post['name']){
                $changes[] = tr('Set name to ":name"', array(':name' => $post['name']));
            }

            if(isset_get($oldpost['status']) != $post['status']){
                $changes[] = tr('Set status to ":status"', array(':status' => $post['status']));
            }

            for($i = 1; $i <= 3; $i++){

                if(isset_get($oldpost['seocategory'.$i]) != $post['seocategory'.$i]){
                    $changes[] = tr('Set :categoryname to ":category"', array(':categoryname' => strtolower($params['label_category'.$i]), ':category' => $post['category'.$i]));
                }
            }

            /*
             * If no body was given, and no changes were made, then we don't update
             */
            if(!$post['body'] and !$changes){
                throw new BException('blogs_validate_post(): No changes were made', 'nochanges');
            }

            $post['body'] = '<h3>'.name($_SESSION['user']).' <small>['.date_convert().']</small></h3><p><small>'.implode('<br>', $changes).'</small></p><p>'.$post['body'].'</p><hr>'.isset_get($oldpost['body'], '');
        }

        $post['body'] = str_replace('&nbsp;', ' ', $post['body']);

        if($params['filter_html']){
            /*
             * Filter all HTML, allowing only the specified tags in filter_html
             */
            $post['body'] = strip_tags($post['body'], $params['filter_html']);
        }

        if($params['filter_attributes']){
            $post['body'] = preg_replace($params['filter_attributes'],'<$1>', $post['body']);
        }

        return $post;

    }catch(Exception $e){
        if(!empty($oldpost['body'])){
            $post['body'] = $oldpost['body'];
        }

        if($e->getCode() == 'validation'){
            /*
             * Just throw the list of validation errors.
             */
            throw $e;
        }

        throw new BException('blogs_validate_post(): Failed', $e);
    }
}



/*
 * Process uploaded blog post media file
 */
function blogs_media_upload($files, $post, $level = null){
    global $_CONFIG;

    try{
        /*
         * Check for upload errors
         */
        upload_check_files(1);

        if(!empty($_FILES['files'][0]['error'])){
            throw new BException(isset_get($_FILES['files'][0]['error_message'], tr('PHP upload error code ":error"', array(':error' => $_FILES['files'][0]['error']))), $_FILES['files'][0]['error']);
        }

        $file     = $files;
        $original = $file['name'][0];
        $file     = file_get_local($file['tmp_name'][0]);

        return blogs_media_process($file, $post, $level, $original);

    }catch(Exception $e){
        throw new BException('blogs_media_upload(): Failed', $e);
    }
}



/*
 * Process local blog post media file
 */
function blogs_media_add($file, $post, $level = null){
    global $_CONFIG;

    try{
        /*
         * Check for upload errors
         */
        if(!file_exists($file)){
            throw new BException(tr('blogs_media_add(): Specified file ":file" does not exist', array(':file' => $file)), 'uploaderror');
        }

        return blogs_media_process($file, $post, $level);

    }catch(Exception $e){
        throw new BException('blogs_media_add(): Failed', $e);
    }
}



/*
 * Process blog media file
 */
function blogs_media_process($file, $post, $priority = null, $original = null){
    global $_CONFIG;

    try{
        load_libs('image,upload,cdn');

        if(empty($post['id'])) {
            throw new BException('blogs_media_process(): No blog post specified', 'not-specified');
        }

        $post = sql_get('SELECT `blogs_posts`.`id`,
                                `blogs_posts`.`blogs_id`,
                                `blogs_posts`.`createdby`,
                                `blogs_posts`.`assigned_to_id`,
                                `blogs_posts`.`name`,
                                `blogs_posts`.`seoname`,

                                `blogs`.`seoname` AS blog_name,
                                `blogs`.`large_x`,
                                `blogs`.`large_y`,
                                `blogs`.`medium_x`,
                                `blogs`.`medium_y`,
                                `blogs`.`small_x`,
                                `blogs`.`small_y`,
                                `blogs`.`wide_x`,
                                `blogs`.`wide_y`,
                                `blogs`.`thumb_x`,
                                `blogs`.`thumb_y`,
                                `blogs`.`wide_x`,
                                `blogs`.`wide_y`,
                                `blogs`.`retina`

                         FROM   `blogs_posts`

                         JOIN   `blogs`
                         ON     `blogs_posts`.`blogs_id` = `blogs`.`id`

                         WHERE  `blogs_posts`.`id`       = '.cfi($post['id']));

        if(empty($post['id'])) {
            throw new BException('blogs_media_process(): Unknown blog post specified', 'unknown');
        }

        if((PLATFORM_HTTP) and ($post['createdby'] != $_SESSION['user']['id']) and ($post['assigned_to_id'] != $_SESSION['user']['id']) and !has_rights('god')){
            /*
             * User is not post creator, is not assigned. Check if the user has group access (ie, has a group with the posts seoname)
             */
            if(!has_groups($post['seoname'])){
                throw new BException(tr('blogs_media_process(): Cannot upload media, post ":post" is not yours', array(':post' => $post['name'])), 'access-denied');
            }
        }

        /*
         *
         */
        $mime_type = file_mimetype($file);
        $prefix    = ROOT.'data/content/';
        $types     = $_CONFIG['blogs']['images'];

        if(str_until($mime_type, '/') === 'video'){
            load_libs('video');

            $original_video = $file;
            $video_thumb    = video_get_thumbnail($file, 'hd720');
            $file           = $post['blog_name'].'/'.file_move_to_target($video_thumb, $prefix.$post['blog_name'].'/', '-original.jpg', false, 4);

        }else{
            $file = $post['blog_name'].'/'.file_move_to_target($file, $prefix.$post['blog_name'].'/', '-original.jpg', false, 4);
        }

        $media  = str_runtil($file, '-');
        $files  = array('media/'.$media.'-original.jpg' => $prefix.$file);
        $hash   = hash('sha256', $prefix.$file);

        /*
         * Process all image types
         */
        foreach($types as $type => $params){
            if($params['method'] and (!empty($post[$type.'_x']) or !empty([$type.'_y']))){
                $params['x'] = $post[$type.'_x'];
                $params['y'] = $post[$type.'_y'];

                image_convert($prefix.$file, $prefix.$media.'-'.$type.'.jpg', $params);
                $files['media/'.$media.'-'.$type.'.jpg'] = $prefix.$media.'-'.$type.'.jpg';

            }else{
                copy($prefix.$file, $prefix.$media.'-'.$type.'.jpg');
            }

            if($post['retina']){
                if($params['method'] and (!empty($post[$type.'_x']) or !empty($post[$type.'_y']))){
                    $params['x'] = $post[$type.'_x'] * 2;
                    $params['y'] = $post[$type.'_y'] * 2;

                    image_convert($prefix.$file, $prefix.$media.'-'.$type.'@2x.jpg', $params);

                }else{
                    symlink($prefix.$media.'-'.$type.'@2x.jpg', $prefix.$media.'-'.$type.'@2x.jpg');
                }

            }else{
                /*
                 * If retina images are not supported, then just symlink them so that they at least are available
                 */
                symlink(basename($prefix.$media.'-'.$type.'.jpg'), $prefix.$media.'-'.$type.'@2x.jpg');
            }

            $files['media/'.$media.'-'.$type.'@2x.jpg'] = $prefix.$media.'-'.$type.'@2x.jpg';
        }

        if (isset($original_video)) {
            // $file   = $post['blog_name'].'/'.file_move_to_target($original_video, $prefix.$post['blog_name'].'/', '-original.' . $video_extension, false, 4);
            // copy($prefix.$file, $prefix.$media.'-original.' . $video_extension);

            file_delete($prefix.$media.'-original.*');
            $file   = $post['blog_name'].'/'.file_move_to_target($original_video, $prefix.$post['blog_name'].'/', '-original.mp4', false, 4);
            copy($prefix.$file, $prefix.$media.'-original.mp4');
        }

        /*
         * If no priority has been specified then get the highest one
         */
        if(!$priority){
            $priority = sql_get('SELECT (COALESCE(MAX(`priority`), 0) + 1) AS `priority` FROM `blogs_media` WHERE `blogs_posts_id` = :blogs_posts_id', true, array(':blogs_posts_id' => $post['id']));
        }

        /*
         * Store blog post photo in database
         */
        $res  = sql_query('INSERT INTO `blogs_media` (`createdby`, `blogs_posts_id`, `blogs_id`, `file`, `hash`, `original`, `priority`)
                           VALUES                    (:createdby , :blogs_posts_id , :blogs_id , :file , :hash , :original , :priority )',

                           array(':createdby'      => isset_get($_SESSION['user']['id']),
                                 ':blogs_posts_id' => $post['id'],
                                 ':blogs_id'       => $post['blogs_id'],
                                 ':file'           => $media,
                                 ':hash'           => $hash,
                                 ':original'       => $original,
                                 ':priority'       => $priority));

        cdn_add_files($files, 'blogs');

        return array('id'          => sql_insert_id(),
                     'file'        => $media,
                     'description' => '');

    }catch(Exception $e){
        throw new BException('blogs_media_process(): Failed', $e);
    }
}



/*
 *
 */
function blogs_media_delete($blogs_posts_id){
    try{
        $media = sql_query('SELECT `id`, `file` FROM `blogs_media` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $blogs_posts_id));

        if(!$media->rowCount()){
            /*
             * There are no files to delete
             */
            return false;
        }

        while($file = sql_fetch($media)){
            file_delete_tree(dirname(ROOT.'data/content/'.$file['file']));
            file_clear_path(dirname(ROOT.'data/content/'.$file['file']));
        }

        sql_query('DELETE FROM `blogs_media` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $blogs_posts_id));

    }catch(Exception $e){
        throw new BException('blogs_media_delete(): Failed', $e);
    }
}



/*
 * Process uploaded club photo
 */
function blogs_url_upload($files, $post, $priority = null){
    global $_CONFIG;

    try{
        load_libs('upload');

        /*
         * Check for upload errors
         */
        upload_check_files(1);

        /*
         * Check for errors
         */
        if(!empty($_FILES['files'][0]['error'])) {
            throw new BException($_FILES['files'][0]['error_message'], 'uploaderror');
        }

        /*
         *
         */
        $file = $files;
        $file = file_get_local($file['tmp_name'][0]);

        return blogs_media_process($file, $post, $priority);

    }catch(Exception $e){
        throw new BException('blogs_url_upload(): Failed', $e);
    }
}



/*
 * Find and return a free priority for blog photo
 */
function blogs_media_get_free_priority($blogs_posts_id, $insert = false){
    global $_CONFIG;

    try{
        if($insert){
            /*
             * Insert mode, return the first possible priority, in case there is a gap (ideally should be highest though, if there are no gaps)
             */
            $list = sql_list('SELECT `priority` FROM `blogs_media` WHERE `blogs_posts_id` = :blogs_posts_id ORDER BY `priority` ASC', array(':blogs_posts_id' => $blogs_posts_id));

            for($current = 1; ; $current++){
                if(!in_array($current, $list)){
                    return $current;
                }

            }

            return $current;
        }

        /*
         * Highest mode, return the highest priority + 1
         */
        return (integer) sql_get('SELECT MAX(`priority`) FROM `blogs_media` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $blogs_posts_id)) + 1;

    }catch(Exception $e){
        throw new BException('blogs_media_get_free_level(): Failed', $e);
    }
}



/*
 * Photo description
 */
function blogs_photo_description($user, $media_id, $description){
    try{
        if(!is_numeric($media_id)){
            $media_id = str_from($media_id, 'photo');
        }

        $media    = sql_get('SELECT `blogs_media`.`id`,
                                    `blogs_media`.`createdby`

                             FROM   `blogs_media`

                             JOIN   `blogs_posts`

                             WHERE  `blogs_media`.`blogs_posts_id` = `blogs_posts`.`id`
                             AND    `blogs_media`.`id`             = '.cfi($media_id));

        if(empty($media['id'])) {
            throw new BException('blogs_photo_description(): Unknown blog post photo specified', 'unknown');
        }

        if(($media['createdby'] != $_SESSION['user']['id']) and !has_rights('god')){
            throw new BException('blogs_photo_description(): Cannot upload media, this post is not yours', 'access-denied');
        }

        sql_query('UPDATE `blogs_media`

                   SET    `description` = :description

                   WHERE  `id`          = :id',

                   array(':description' => cfm($description),
                         ':id'          => cfi($media_id)));

    }catch(Exception $e){
        throw new BException('blogs_photo_description(): Failed', $e);
    }
}



/*
 * Get a full URL of the photo
 */
function blogs_photo_url($media, $size, $section = ''){
    try{
        load_libs('cdn');

        switch($size){
            case 'original':
                // FALLTHROUGH
            case 'large':
                // FALLTHROUGH
            case 'medium':
                // FALLTHROUGH
            case 'small':
                // FALLTHROUGH
            case 'wide':
                // FALLTHROUGH
            case 'thumb':
                /*
                 * Valid
                 */
                return cdn_domain('/'.$media.'-'.$size.'.jpg', $section);

            default:
                throw new BException(tr('blogs_photo_url(): Unknown size ":size" specified', array(':size' => $size)), 'unknown');
        }

    }catch(Exception $e){
        throw new BException('blogs_photo_url(): Failed', $e);
    }
}



/*
 * Get a display level for the level ID or vice versa
 */
function blogs_level($level){
    static $list, $rlist;

    try{
        if(empty($list)){
            $list = array(5 => tr('Low'),
                          4 => tr('Normal'),
                          3 => tr('High'),
                          2 => tr('Urgent'),
                          1 => tr('Immediate'));
        }

        if(is_numeric($level)){
            if(isset($list[$level])){
                return $list[$level];
            }

            return $list[3];
        }

        if($level === null){
            return 'Unknown';
        }

        /*
         * Reverse lookup
         */
        if(empty($rlist)){
            $rlist = array_flip($list);
        }

        $level = strtolower($level);

        if(isset($rlist[$level])){
            return $rlist[$level];
        }

        return 3;

    }catch(Exception $e){
        throw new BException('blogs_level(): Failed', $e);
    }
}



/*
 * Validate the specified category
 */
function blogblogs_validate_category($category, $blogs_id){
    try{
        if(!$category){
            throw new BException(tr('blogblogs_validate_category(): No category specified'), 'not-exists');
        }

        if(!$retval = sql_get('SELECT `id`, `blogs_id`, `name`, `seoname` FROM `blogs_categories` WHERE `blogs_id` = :blogs_id AND `seoname` = :seoname', array(':blogs_id' => $blogs_id, ':seoname' => $category))){
// :DELETE: Delete following 2 debug code lines
//show(current_file(1).current_line(1));
//showdie(tr('The specified category ":category" does not exists', ':category', $category)));
            /*
             * The specified category does not exist
             */
            throw new BException(tr('blogblogs_validate_category(): The specified category ":category" does not exists in blog ":blogs_id"', array(':blogs_id' => $blogs_id, ':category' => $category)), 'not-exists');
        }

// :DELETE: This check is no longer needed since the query now filters on blogs_id
        //if($retval['blogs_id'] != $blogs_id){
        //    /*
        //     * The specified category is not of this blog
        //     */
        //    throw new BException(tr('blogblogs_validate_category(): The specified category ":category" is not of this blog', array(':category' => $category)), 'invalid');
        //}

        return $retval;

    }catch(Exception $e){
        throw new BException('blogblogs_validate_category(): Failed', $e);
    }
}



/*
 *
 */
function blogs_validate_parent($blog_post_id, $blogs_id){
    try{
        if(!$blog_post_id){
            throw new BException(tr('blogs_validate_parent(): No blogs_posts_id specified'), 'not-specified');
        }

        if(!$blogs_id){
            throw new BException(tr('blogs_validate_parent(): No blogs_id specified'), 'not-specified');
        }

        if(is_numeric($blog_post_id)){
            $id = sql_get('SELECT `id` FROM `blogs_posts` WHERE `id` = :id AND `blogs_id` = :blogs_id', true, array(':blogs_id' => $blogs_id,
                                                                                                                    ':id'       => cfi($blog_post_id)));

        }else{
            $id = sql_get('SELECT `id` FROM `blogs_posts` WHERE `seoname` = :seoname AND `blogs_id` = :blogs_id', true, array(':blogs_id' => $blogs_id,
                                                                                                                              ':seoname'  => cfm($blog_post_id)));
        }

        if(!$id){
            throw new BException(tr('blogs_validate_parent(): Blog ":blog" does not contain a blog post named ":post"', array(':blog' => $blogs_id, ':post' => $blog_post_id)), 'not-member');
        }

        return $id;

    }catch(Exception $e){
        throw new BException('blogs_validate_parent(): Failed', $e);
    }
}



/*
 * Generate and return a URL for the specified blog post,
 * based on blog url configuration
 */
function blogs_post_url($post){
    global $_CONFIG;

    try{
        /*
         * What URL template to use?
         */
        if(empty($post['url_template'])){
            if(empty($post['blogs_id'])){
                throw new BException(tr('blogs_post_url(): No URL template or blogs_id specified for post ":post"', array(':post' => $post)), 'not-specified');
            }

            $post['url_template'] = sql_get('SELECT `url_template` FROM `blogs` WHERE `id` = :id', array(':id' => $post['blogs_id']), 'url_template');
        }

        if(empty($post['url_template'])){
            /*
             * This blog has no URL template configured, so don't generate URLs
             * and don't add them to the sitemap
             */
            return false;
        }

        $url      = $post['url_template'];
        $sections = array('id',
                          'time',
                          'date',
                          'createdon',
                          'blog',
                          'seoname',
                          'language',
                          'seoparent',
                          'category1',
                          'seocategory1',
                          'category2',
                          'seocategory2',
                          'category3',
                          'seocategory3');

        if(empty($post['blog'])){
            $post['blog'] = sql_get('SELECT `seoname` FROM `blogs` WHERE `id` = :id', array(':id' => $post['blogs_id']), 'seoname');
        }

        foreach($sections as $section){
            switch($section){
                case 'seoparent':
                    $post[$section] = sql_get('SELECT `seoname` FROM `blogs_posts` WHERE `id` = :id', 'seoname', array(':id' => isset_get($post['parents_id'])));
                    break;

                case 'date':
                    $post[$section] = str_until(isset_get($post['createdon']), ' ');
                    break;

                case 'time':
                    $post[$section] = str_from(isset_get($post['createdon']), ' ');
            }

            if(strstr($url, '%'.$section.'%')){
                if(trim(isset_get($post[$section]))){
                    $url = str_replace('%'.$section.'%', isset_get($post[$section]), $url);

                }else{
                    /*
                     * This post does not have all required sections available. Disable post and notify
                     */
                    sql_query('UPDATE `blogs_posts` SET `status` = "incomplete" WHERE `id` = :id', array(':id' => $post['id']));
                    throw new BException(tr('blogs_post_url(): URL template ":template" for blog post ":post" requires the section ":section", but the blog post does not have this section available. A URL cannot be generated', array(':template' => $post['url_template'], ':post' => $post['id'], ':section' => $section)), 'incomplete');
                }
            }
        }

        $url = trim($url);

        if(preg_match('/$https?:\/\//', $url)){
            /*
             * This is an absolute URL, return it as-is
             */
            return $url;
        }

        $config = read_config('');
        return domain($url, null, $config['url_prefix'], null, $post['language']);

    }catch(Exception $e){
        throw new BException('blogs_post_url(): Failed', $e);
    }
}



/*
 * Update the URL's for the specified blog post
 */
function blogs_update_url($post){
    try{
        $url = blogs_post_url($post);

        if((PLATFORM_CLI) and VERBOSE){
            log_console(tr('blogs_update_url(): Updating blog post :post to URL ":url"', array(':url' => $url, ':post' => str_size('"'.str_truncate($post['seoname'], 40).'"', 42, ' '))));
        }

        sql_query('UPDATE `blogs_posts`

                   SET    `url` = :url

                   WHERE  `id`  = :id',

                   array(':url' => $url,
                         ':id'  => $post['id']));

        return $url;

    }catch(Exception $e){
        throw new BException('blogs_update_url(): Failed', $e);
    }
}



/*
 * Update the URL's for blog posts
 * Can update all posts, all posts for multiple blogs, or all posts within one category within one blog
 */
function blogs_update_urls($blogs = null, $category = null){
    try{
        load_libs('sitemap');

        $params = array();

        array_ensure($params);
        array_default($params, 'status'                  , 'published');
        array_default($params, 'sitemap_priority'        , 1);
        array_default($params, 'sitemap_change_frequency', 'weekly');

        $count = 0;

        if($category){
            /*
             * Only update for a specific category
             * Ensure that the category exists. If no blog was specified, then get the blog from the specified category
             */
            if(is_numeric($category)){
                $category = sql_get('SELECT `id`, `blogs_id`, `seoname`, `name` FROM `blogs_categories` WHERE `id`      = :id'     , array(':id'     => $category));

            }elseif(is_scalar($category)){
                $category = sql_get('SELECT `id`, `blogs_id`, `seoname`, `name` FROM `blogs_categories` WHERE `seoname` = :seoname', array(':seoname'=> $category));

            }elseif(!is_array($category)){
                throw BException('blogs_update_urls(): Invalid category datatype specified. Either specify id, seoname, or full array', 'invalid');
            }

            if(!$blogs){
                $blogs = $category['blogs_id'];
            }
        }

        if(!$blogs){
            /*
             * No specific blog was specified? process the posts for all blogs
             */
            $r = sql_query('SELECT `id` FROM `blogs` WHERE `status` IS NULL');

            log_console(tr('blogs_update_urls(): Updating posts for all blogs'));

            while($blog = sql_fetch($r)){
                $count += blogs_update_urls($blog['id'], $category);
            }

            return $count;
        }

        foreach(array_force($blogs) as $blogname){
            try{
                /*
                 * Get blog data either from ID or seoname
                 */
                if(is_numeric($blogname)){
                    $blog = sql_get('SELECT `id`, `name`, `seoname`, `url_template` FROM `blogs` WHERE `id`      = :id'     , array(':id'      => $blogname));

                }else{
                    $blog = sql_get('SELECT `id`, `name`, `seoname`, `url_template` FROM `blogs` WHERE `seoname` = :seoname', array(':seoname' => $blogname));
                }

                if(!$blog){
                    log_console(tr('blogs_update_urls(): Specified blog ":blog" does not exist, skipping', array(':blog' => $blogname)), 'yellow');
                    continue;
                }

                if(!$blog['url_template']){
                    log_console(tr('blogs_update_urls(): Skipping updating post urls for blog :blog, the blog has no URL template configured', array(':blog' => str_size('"'.str_truncate($blog['name'], 40).'"', 42, ' '))), 'yellow');
                    continue;
                }

                log_console(tr('blogs_update_urls(): Updating post urls for blog :blog', array(':blog' => str_size('"'.str_truncate($blog['name'], 40).'"', 42, ' '))));

                /*
                 * Walk over all posts of the specified blog
                 */
                $query   = 'SELECT `id`,
                                   `blogs_id`,
                                   `parents_id`,
                                   `status`,
                                   `url`,
                                   `name`,
                                   `seoname`,
                                   `language`,
                                   `createdon`,
                                   `modifiedon`,
                                   `createdby`,
                                   `category1`,
                                   `seocategory1`,
                                   `category2`,
                                   `seocategory2`,
                                   `category3`,
                                   `seocategory3`

                            FROM   `blogs_posts`

                            WHERE  `blogs_id` = :id
                            AND    `status`   = :status';

                $execute = array(':id'     => $blog['id'],
                                 ':status' => $params['status']);

                if($category){
                    /*
                     * Add category filter
                     * Since categories are limited to specific blogs, ensure
                     * that this category is available within the blog
                     */
                    if($category['blogs_id'] != $blog['id']){
                        if(PLATFORM_CLI){
                            log_console(tr('blogs_update_urls(): The category ":category" does not exist for the blog ":blog", skipping', array(':category' => $category['name'], ':blog' => $blog['name'])), 'yellow');
                        }

                        continue;
                    }

                    $query .= ' AND (`seocategory1` = :seocategory1 OR `seocategory2` = :seocategory2 OR `seocategory3` = :seocategory3) ';

                    $execute[':seocategory1'] = $category['seoname'];
                    $execute[':seocategory2'] = $category['seoname'];
                    $execute[':seocategory3'] = $category['seoname'];
                }

                /*
                 * Walk over all posts in the selected filter, and update the URL's
                 */
                $posts = sql_query($query, $execute);

                while($post = sql_fetch($posts)){
                    try{
                        $url                  = $post['url'];
                        $post['url_template'] = $blog['url_template'];
                        $post['url']          = blogs_update_url($post);

                        if($url != $post['url']){
                            /*
                             * Page URL changed, delete old entry from the sitemap table to
                             * avoid it still showing up in sitemaps, since this page is now 404
                             */
                            sitemap_delete_entry($url);
                        }

                        if($post['url']){
                            if($post['status'] == $params['status']){
                                sitemap_insert_entry(array('url'              => $post['url'],
                                                           'language'         => $post['language'],
                                                           'priority'         => $params['sitemap_priority'],
                                                           'page_modifiedon'  => date_convert($post['createdon'], 'mysql'),
                                                           'change_frequency' => $params['sitemap_change_frequency']));
                            }
                        }

                        cli_dot(1);
                        $count++;

                    }catch(Exception $e){
                        notify($e->makeWarning(true));
                        log_console(tr('blogs_update_urls(): URL update failed for blog post ":post" in blog ":blog" with error ":e". Its status has been updated to "incomplete"', array(':post' => $post['id'].' / '.$post['seoname'], ':blog' => $blog['seoname'], ':e' => $e)), 'yellow');
                    }
                }

            }catch(Exception $e){
                /*
                 * URL generation failed for this blog post
                 */
                notify($e);
                log_console(tr('blogs_update_urls(): URL update failed for blog ":blog" with error ":e"', array(':blog' => $blog['seoname'], ':e' => $e)), 'yellow');
            }
        }

        if($count){
            log_console();
        }

        return $count;

    }catch(Exception $e){
        throw new BException('blogs_update_urls(): Failed', $e);
    }
}


/*
 *
 */
function blogs_post_erase($post){
    global $_CONFIG;

    try{
        if(is_array($post)){
            $count = 0;

            foreach($post as $id){
                $count += blogs_post_erase($id);
            }

            return $count;
        }

        if(is_numeric($post)){
            $post = sql_get('SELECT `id` FROM `blogs_posts` WHERE `id` = :id', 'id', array(':id' => $post));

        }else{
            $post = sql_get('SELECT `id` FROM `blogs_posts` WHERE `seoname` = :seoname', 'id', array(':seoname' => $post));
        }

        /*
         * First delete the physical image files
         */
        $r = sql_query('SELECT `file` FROM `blogs_media` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post));

        while($media = sql_fetch($r)){
            foreach($_CONFIG['blogs']['images'] as $type => $config){
                file_delete(ROOT.'data/content/'.$media['file'].'-'.$type.'.jpg');
            }
        }

        file_clear_path(ROOT.'data/content/'.$media['file']);

        sql_query('DELETE FROM `blogs_media`      WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post));
        sql_query('DELETE FROM `blogs_keywords`   WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post));
        sql_query('DELETE FROM `blogs_key_values` WHERE `blogs_posts_id` = :blogs_posts_id', array(':blogs_posts_id' => $post));
        sql_query('DELETE FROM `blogs_posts`      WHERE `id`             = :id'            , array(':id'             => $post));

        return 1;

    }catch(Exception $e){
        throw new BException('blogs_post_erase(): Failed', $e);
    }
}



/*
 *
 */
function blogs_regenerate_sitemap_data($blogs_id, $level, $change_frequency, $group = '', $file = ''){
    try{
        load_libs('sitemap');

        $count   = 1;
        $execute = array();
        $query   = 'SELECT `id`,
                           `url`,
                           `language`,
                           `createdon`,
                           `modifiedon`

                    FROM   `blogs_posts`

                    WHERE  `status`   = "published"
                    AND    `seoname` != ""';

        if($blogs_id){
            $where[] = ' `blogs_id` = :blogs_id ';
            $execute[':blogs_id'] = $blogs_id;
            $count++;
        }

        if(!empty($where)){
            $query .= ' AND '.implode(' AND ', $where);
        }

        if($group){
            sitemap_clear($group);
        }

        $posts = sql_query($query, $execute);
        $count = 0;

        while($post = sql_fetch($posts)){
            cli_dot();
            $count++;

            sitemap_insert_entry(array('file'             => $file,
                                       'group'            => $group,
                                       'language'         => $post['language'],
                                       'url'              => $post['url'],
                                       'change_frequency' => $change_frequency,
                                       'page_modifiedon'  => ($post['modifiedon'] ? $post['modifiedon'] : $post['createdon']),
                                       'level'            => $level));
        }

        cli_dot(false);
        return $count;

    }catch(Exception $e){
        throw new BException('blogs_regenerate_sitemap_data(): Failed', $e);
    }
}



/*
 *
 */
function blogs_post_get_new_priority($blogs_id){
    try{
        $priority = sql_get('SELECT MAX(`priority`) FROM `blogs_posts` WHERE `blogs_id` = :blogs_id', true, array(':blogs_id' => $blogs_id));
        $priority = (integer) isset_get($priority, 0);

        return $priority + 1;

    }catch(Exception $e){
        throw new BException('blogs_post_get_new_priority(): Failed', $e);
    }
}



/*
 *
 */
function blogs_post_up($id, $object, $view){
    try{
        /*
         * Move post up in a list of items that are av ailable, deleted, etc?
         */
        switch($view){
            case 'available':
                $where = ' AND `blogs_posts`.`status` IN ("published", "unpublished") ';
                $join  = ' AND `higher`.`status`      IN ("published", "unpublished") ';
                break;

            case 'all':
                $where = ' AND `blogs_posts`.`status` != "_new" ';
                $join  = ' AND `higher`.`status`      != "_new" ';
                break;

            case 'deleted':
                // FALLTHROUGH
            case 'published':
                // FALLTHROUGH
            case 'unpublished':
                $where = ' AND `blogs_posts`.`status` = "'.$view.'" ';
                $join  = ' AND `higher`.`status`      = "'.$view.'" ';
                break;

            default:
                throw new BException(tr('blogs_post_up(): Unknown view ":view" specified', array(':view' => $view)), 'unknown');
        }

        sql_query('START TRANSACTION');

        $post = sql_get('SELECT    `blogs_posts`.`id`,
                                   `blogs_posts`.`createdby`,
                                   `blogs_posts`.`priority`,
                                   `higher`.`id`       AS `higher_id`,
                                   `higher`.`priority` AS `higher_priority`

                         FROM      `blogs_posts`

                         LEFT JOIN `blogs_posts` AS `higher`
                         ON        `blogs_posts`.`blogs_id` = `higher`.`blogs_id`
                         AND       `blogs_posts`.`priority` < `higher`.`priority`
                         '.$join.'

                         WHERE     `blogs_posts`.`id` = :id
                         '.$where.'

                         ORDER BY  `higher`.`priority` ASC
                         LIMIT 1',

                         array(':id' => cfi($id)));
//show($post);

        if(empty($post['id'])){
            throw new BException(tr('blogs_post_up(): Unknown :object ":id" specified', array(':object' => $object, ':id' => $id)), 'unknown');
        }

        if(($post['createdby'] != $_SESSION['user']['id']) and !has_rights('god')){
            throw new BException(tr('blogs_post_up(): The :object ":id" does not belong to you', array(':object' => $object, ':id' => $id)), 'access-denied');
        }

        if($post['higher_priority'] !== null){
            /*
             * Switch priorities
             */
            $update = sql_prepare('UPDATE `blogs_posts`

                                   SET    `priority` = :priority

                                   WHERE  `id`       = :id');

//show(array(':id'       => $post['id'],
//            ':priority' => -$post['higher_priority']));
//show(array(':id'       => $post['higher_id'],
//            ':priority' => $post['priority']));
//show(array(':id'       => $post['id'],
//            ':priority' => $post['higher_priority']));

            $update->execute(array(':id'       => $post['id'],
                                   ':priority' => -$post['higher_priority']));

            $update->execute(array(':id'       => $post['higher_id'],
                                   ':priority' => $post['priority']));

            $update->execute(array(':id'       => $post['id'],
                                   ':priority' => $post['higher_priority']));
        }

        sql_query('COMMIT');

    }catch(Exception $e){
        throw new BException('blogs_post_up(): Failed', $e);
    }

}



/*
 *
 */
function blogs_post_down($id, $object, $view){
    try{
        /*
         * Move post up in a list of items that are av ailable, deleted, etc?
         */
        switch($view){
            case 'available':
                $where = ' AND `blogs_posts`.`status` IN ("published", "unpublished") ';
                $join  = ' AND `lower`.`status`       IN ("published", "unpublished") ';
                break;

            case 'all':
                $where = ' AND `blogs_posts`.`status` != "_new" ';
                $join  = ' AND `lower`.`status`       != "_new" ';
                break;

            case 'deleted':
                // FALLTHROUGH
            case 'published':
                // FALLTHROUGH
            case 'unpublished':
                $where = ' AND `blogs_posts`.`status` = "'.$view.'" ';
                $join  = ' AND `lower`.`status`       = "'.$view.'" ';
                break;

            default:
                throw new BException(tr('blogs_post_up(): Unknown view ":view" specified', array(':view' => $view)), 'unknown');
        }

        sql_query('START TRANSACTION');

        $post = sql_get('SELECT    `blogs_posts`.`id`,
                                   `blogs_posts`.`createdby`,
                                   `blogs_posts`.`priority`,
                                   `lower`.`id`       AS `lower_id`,
                                   `lower`.`priority` AS `lower_priority`

                         FROM      `blogs_posts`

                         LEFT JOIN `blogs_posts` AS `lower`
                         ON        `blogs_posts`.`blogs_id` = `lower`.`blogs_id`
                         AND       `blogs_posts`.`priority` > `lower`.`priority`
                         '.$join.'

                         WHERE     `blogs_posts`.`id` = :id
                         '.$where.'

                         ORDER BY  `lower`.`priority` DESC
                         LIMIT 1',

                         array(':id' => cfi($id)));

        if(empty($post['id'])){
            throw new BException(tr('blogs_post_up(): Unknown :object id ":id" specified', array(':object' => $object, ':id' => $id)), 'unknown');
        }

        if(($post['createdby'] != $_SESSION['user']['id']) and !has_rights('god')){
            throw new BException(tr('blogs_post_up(): The :object ":id" does not belong to you', array(':object' => $object, ':id' => $id)), 'access-denied');
        }

        if($post['lower_priority'] !== null){
            /*
             * Switch priorities
             */
            $update = sql_prepare('UPDATE `blogs_posts`

                                   SET    `priority` = :priority

                                   WHERE  `id`       = :id');

            $update->execute(array(':id'       => $post['id'],
                                   ':priority' => -$post['lower_priority']));

            $update->execute(array(':id'       => $post['lower_id'],
                                   ':priority' => $post['priority']));

            $update->execute(array(':id'       => $post['id'],
                                   ':priority' => $post['lower_priority']));
        }

        sql_query('COMMIT');

    }catch(Exception $e){
        throw new BException('blogs_post_down(): Failed', $e);
    }
}

/**
 * generate html code
 * @param  array  $photo is the current row from SQL
 * @return string the code html for generate img
 */
function blogs_post_get_img($photo, $params, $tabindex){
    $html = '';
    /*
         * Get photo dimensions
         */
        try{
            unset($is_video);

            if(file_exists(ROOT.'data/content/'.$photo['file'].'-original.jpg')){
                $image = getimagesize(ROOT.'data/content/'.$photo['file'].'-large.jpg');

            }elseif(file_exists(ROOT.'data/content/'.$photo['file'].'-original.mp4')){
                $image     = ROOT.'data/content/'.$photo['file'].'-original.mp4';
                $mime_type = file_mimetype($image);
                $is_video  = true;

            }else{
                throw new BException(tr('blogs_post_get_img(): Media file ":file" does not exists', array(':file' => $photo['file'])), 'not-exists');
            }

        }catch(Exception $e){
            $image = false;
        }

        if(!$image){
            $image = array(tr('Invalid image'), tr('Invalid image'));
        }

        if (isset($is_video)) {
            $html .= '              <tr class="form-group blog photo" id="photo'.$photo['id'].'">
                                        <td class="file">
                                            <div>
                                                <a style="cursor:pointer;" type="button" data-toggle="modal" data-target="#modal-'.$photo['id'].'">
                                                    '.html_img(blogs_photo_url($photo['file'], 'small'), html_safe('('.$image[0].' X '.$image[1].')'), $image[0], $image[1], 'rel="blog-page" class="col-md-1 control-label"').'
                                                </a>
                                                <div id="modal-'.$photo['id'].'" class="modal fade" role="dialog">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                                                <h4 class="modal-title">'.$post['name'].'</h4>
                                                            </div>
                                                            <div class="modal-body text-center">
                                                                <video width="320" height="240" controls>
                                                                    <source src="'.cdn_domain($photo['file'].'-original.mp4', 'blogs/media').'" type="'.$mime_type.'">
                                                                    '.tr('Your browser does not support the video tag.').'
                                                                </video>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-default" data-dismiss="modal">'.tr('Close').'</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>';
        } else {
            $html .= '              <tr class="form-group blog photo" id="photo'.$photo['id'].'">
                                        <td class="file">
                                            <div>
                                                <a target="_blank" class="fancy" href="'.cdn_domain($photo['file'].'-large.jpg').'">
                                                    '.html_img(cdn_domain($photo['file'].'-small.jpg', ''), html_safe('('.$image[0].' X '.$image[1].')'), $image[0], $image[1], 'rel="blog-page" class="col-md-1 control-label"').'
                                                </a>
                                            </div>
                                        </td>';
        }

        if(isset_get($params['file_types'],false)){
            try{
                $html .= '              <td class="buttons">
                                            <div>
                                                '.html_select(array('name'     => 'file_status['.$photo['id'].']',
                                                                    'class'    => 'btn blogpost photo type',
                                                                    'extra'    => 'tabindex="'.++$tabindex.'"',
                                                                    'selected' => $photo['type'],
                                                                    'none'     => tr('Unspecified type'),
                                                                    'resource' => $params['file_types'])).'
                                                </a>
                                            </div>
                                        </td>';

            }catch(Exception $e){
                throw new BException(tr('blog-post: file type section failed'), $e);
            }
        }

        $html .= '                      <td class="buttons">
                                            <div>
                                                <a class="col-md-5 btn btn-success blogpost photo up button">'.tr('Up').'</a>
                                                <a class="col-md-5 btn btn-success blogpost photo down button">'.tr('Down').'</a>
                                                <a class="col-md-5 btn btn-danger blogpost photo delete button">'.tr('Delete').'</a>
                                            </div>
                                        </td>
                                        <td class="description">
                                            <div>
                                                <textarea class="blogpost photo description form-control" placeholder="'.tr('Description of this photo').'">'.$photo['description'].'</textarea>
                                            </div>
                                        </td>
                                    </tr>';
        return $html;
}



/*
 * Sync location information from the specified posts_id to the user that is
 * assigned to that post, or vice versa (depending on the $to_user variable)
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package blogs
 * @see blogs_update_location()
 * @see blogs_get_location()
 * @see blogs_post_get()
 *
 * @param natural $posts_id
 * @param boolean $to_user If set to true, the location information will be synced from the blog post key / value store to the user. If set to false, the location information will be taken from the user and synced to the blog post key / value store
 * @return void
 */
function blogs_sync_location($posts_id, $to_user = false){
    load_libs('user');
    try{
        if($to_user){
            $geo             = blogs_get_location($posts_id);
            $geo['users_id'] = $posts_id;
            user_update_location($geo);

        }else{
            $post = blogs_post_get($posts_id);
            $geo  = user_get_location($post['assigned_to_id']);
            blog_update_location($geo);
        }

    }catch(Exception $e){
        throw new BException(tr('blogs_sync_location(): failed'), $e);
    }
}



/*
 * Get and return location information for the specified blogs posts_id, if exist
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package blogs
 * @see blogs_update_location()
 *
 * @param natural $posts_id
 * @return array the found location information
 */
function blogs_get_location($posts_id){
    try{
        $values = blogs_post_get_key_values($posts_id);

        /*
         * Return only location keys
         */
        foreach($values as $key => $value){
            switch($key){
                case 'accuracy':
                    // FALLTHROUGH
                case 'latitude':
                    // FALLTHROUGH
                case 'longitude':
                    // FALLTHROUGH
                case 'offset_latitude':
                    // FALLTHROUGH
                case 'offset_longitude':
                    // FALLTHROUGH
                case 'cities_id':
                    // FALLTHROUGH
                case 'states_id':
                    // FALLTHROUGH
                case 'countries_id':
                    // FALLTHROUGH
                    break;

                default:
                    unset($values[$key]);
            }
        }

        return $values;

    }catch(Exception $e){
        throw new BException(tr('blogs_get_location(): failed'), $e);
    }
}



/*
 * Get and return location information for the specified blogs posts_id, if exist
 *
 * @author Sven Olaf Oostenbrink <sven@capmega.com>
 * @copyright Copyright (c) 2018 Capmega
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package blogs
 * @see blogs_get_location()
 *
 * @param natural $posts_id
 * @param params $geo
 * @param integer $geo[accuracy]
 * @param float $geo[latitude]
 * @param float $geo[longitude]
 * @param float $geo[offset_latitude]
 * @param float $geo[offset_longitude]
 * @param integer $geo[cities_id]
 * @param integer $geo[states_id]
 * @param integer $geo[countries_id]
 * @return integer The amount of updated entries
 */
function blogs_update_location($posts_id, $geo){
    try{
        $count  = 0;
        $insert = sql_prepare('INSERT INTO `blogs_key_values` (`blogs_posts_id`, `key`. `seokey`, `value`, `seovalue`)
                               VALUES                         (:blogs_posts_id , :key . :seokey , :value , :seovalue )

                               ON UPDATE SET `key`      = :update_key,
                                             `seokey`   = :update_seokey,
                                             `value`    = :update_value,
                                             `seovalue` = :update_seovalue');

        foreach($geo as $key => $value){
            switch($key){
                case 'accuracy':
                    // FALLTHROUGH
                case 'latitude':
                    // FALLTHROUGH
                case 'longitude':
                    // FALLTHROUGH
                case 'offset_latitude':
                    // FALLTHROUGH
                case 'offset_longitude':
                    // FALLTHROUGH
                case 'cities_id':
                    // FALLTHROUGH
                case 'states_id':
                    // FALLTHROUGH
                case 'countries_id':
                    // FALLTHROUGH
                    $count++;

                    $insert->execute(array(':blogs_posts_id'  => $posts_id,
                                           ':key'             => $key,
                                           ':seokey'          => seo_string($key),
                                           ':value'           => $value,
                                           ':seovalue'        => seo_string($value),
                                           ':update_key'      => $key,
                                           ':update_seokey'   => seo_string($key),
                                           ':update_value'    => $value,
                                           ':update_seovalue' => seo_string($value)));
                    break;
            }
        }

        return $count;

    }catch(Exception $e){
        throw new BException(tr('blogs_update_location(): failed'), $e);
    }
}
?>
