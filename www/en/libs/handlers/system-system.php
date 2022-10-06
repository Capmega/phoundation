<?php
/*
 * This is the startup sequence for system web pages, like 404, 500, etc
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 */



try {

}catch(Exception $e) {
    throw new OutOfBoundsException(tr('core::system(): Failed'), $e);
}
