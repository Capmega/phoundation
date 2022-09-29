<?php
/*
 * Twiml library
 *
 * This library can create twiml XML files
 *
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
function twiml_library_init() {
    try{

    }catch(Exception $e) {
        throw new CoreException('twiml_library_init(): Failed', $e);
    }
}



/*
 * Write the specified twiml data to the specified twiml file in the
 * (optionally) specified twiml directory
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package
 *
 * @param $name
 * @param $data
 * @param $root
 * @return void
 */
function twiml_write($name, $data, $root = null) {
    try{
        if (empty($root)) {
            $root = ROOT.'twiml/';
        }

        if (!preg_match('/[a-z0-9-]+/', $name)) {
            throw new CoreException(tr('twiml_write(): Invalid twiml file name ":name" specified, use only a-z, 0-9 and -', array(':name' => $name)), 'invalid');
        }

        file_ensure_path($root);
        file_put_contents($root.$name, $data);

    }catch(Exception $e) {
        throw new CoreException('twiml_write(): Failed', $e);
    }
}



/*
 * ...
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package
 *
 * @param
 * @return
 */
function twiml_create($params) {
    try{
        Arrays::ensure($params, 'root,name,type');

        switch($params['type']) {
            case 'forward':
                $data = twilm_create_forward($params);
                break;

            case 'simulring':
                $data = twilm_create_simulring($params);
                break;

            default:
                throw new CoreException(tr('twiml_create(): Unknown twiml type ":type" specified, use one of "forward", or "simulring"', array(':type' => $type)), 'unknown');
        }

        twiml_write($params['name'], $data, $params['root']);

    }catch(Exception $e) {
        throw new CoreException('twiml_create(): Failed', $e);
    }
}



/*
 * Create twilio forward twiml XML
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @category Function reference
 * @package
 *
 * @param
 * @return
 */
function twiml_create_forward($params) {
    try{
        Arrays::ensure($params, 'phone_number,caller_id,fail_url,timeout,allowed_callers');
        $data = '';

        if (!$params['phone_number']) {
            throw new CoreException(tr('twiml_create_forward(): No forward phone number specified'), 'not-specified');
        }

        if (!$params['timeout']) {
            $params['timeout'] = 20;
        }

        $data = '<Dial action="/forward?Dial=true" timeout="'.$params['timeout'].($params['fail_url'] ? '&FailUrl='.urlencode($params['fail_url']) : '').'"'.($params['caller_id'] ? ' callerId="'.$params['caller_id'].'"' : '').'>'.$params['phone_number'].'</Dial>';

        if ($params['allowed_callers']) {
            /*
             * This functionality is not supported
             */
            not_supported('allowed_callers');
        }

        $data = "<Response>\n".$data."</Response>";

        return $data;

    }catch(Exception $e) {
        throw new CoreException('twiml_create_forward(): Failed', $e);
    }
}
?>
