<?php

namespace Phoundation\Web\Routing;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\Validator\ArgvValidator;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\PostValidator;

/**
 * Class StaticRoute
 *
 *
 *
 * @see \Phoundation\Data\DataEntry\DataEntry
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class StaticRoute extends DataEntry
{
    /**
     * @inheritDoc
     */
    protected function validate(GetValidator|PostValidator|ArgvValidator $validator, bool $no_arguments_left = false, bool $modify = true): array
    {
        // TODO: Implement validate() method.
    }

    /**
     * Sets the available data keys for this entry
     *
     * @return array
     */
    protected static function getFieldDefinitions(): array
    {
        // TODO: Implement getFieldDefinitions() method.
    }

//sql()->query(
//'INSERT INTO `routes_static` (`expiredon`                                , `meta_id`, `ip`, `uri`, `regex`, `target`, `flags`)
//                   VALUES                      (DATE_ADD(NOW(), INTERVAL :expiredon SECOND), :meta_id , :ip , :uri , :regex , :target , :flags )',
//
//[
//':expiredon' => $route['expiredon'],
//':meta_id'   => meta_action(),
//':ip'        => $route['ip'],
//':uri'       => $route['uri'],
//':regex'     => $route['regex'],
//':target'    => $route['target'],
//':flags'     => $route['flags']
//]);

//    /**
//     * Validate a route
//     *
//     * This function will validate all relevant fields in the specified $route array
//     *
//     * @param StaticRoute $route
//     * @return string HTML for a categories select box within the specified parameters
//     */
//    protected static function validate(StaticRoute $route)
//    {
////        Validator::array($route)
////            ->select('uri')->isUrl('uri')
////            ->select('fields')->sanitizeMakeString()->hasMaxCharacters(16)
////            ->select('regex')->sanitizeMakeString(255)
////            ->select('target')->sanitizeMakeString(255)
////            ->select('ip')->isIp()
////            ->validate();
////
////        return $route;
//    }
//


}