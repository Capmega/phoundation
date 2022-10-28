<?php

namespace Phoundation\Web\Http\Html;

use Phoundation\Core\Arrays;



/**
 * Class Select
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Select extends Element
{
    public function set
    /**
     * Generates and returns the HTML string for a <select> control
     *
     * @return string
     */
    public function render(): string
    {
        static $count = 0;

        Arrays::default($params, 'option_class', '');
        Arrays::default($params, 'selected'    , null);

        Arrays::default($params, 'hide_empty'  , false);
        Arrays::default($params, 'autofocus'   , false);
        Arrays::default($params, 'multiple'    , false);

        if (!$params['tabindex']) {
            $params['tabindex'] = html_tabindex();
        }

        if (!$params['name']) {
            if (!$params['id']) {
                throw new HtmlException(tr('html_select(): No name specified'), 'not-specified');
            }

            $params['name'] = $params['id'];
        }

        if ($params['autosubmit']) {
            if ($params['class']) {
                $params['class'] .= ' autosubmit';

            } else {
                $params['class']  = 'autosubmit';
            }
        }

        if (empty($params['resource'])) {
            if ($params['hide_empty']) {
                return '';
            }

            $params['resource'] = array();

// :DELETE: Wut? What exactly was this supposed to do? doesn't make any sense at all..
            //if (is_numeric($params['disabled'])) {
            //    $params['disabled'] = true;
            //
            //} else {
            //    if (is_array($params['resource'])) {
            //        $params['disabled'] = ((count($params['resource']) + ($params['name'] ? 1 : 0)) <= $params['disabled']);
            //
            //    } elseif (is_object($params['resource'])) {
            //        $params['disabled'] = (($params['resource']->rowCount() + ($params['name'] ? 1 : 0)) <= $params['disabled']);
            //
            //    } elseif ($params['resource'] === null) {
            //        $params['disabled'] = true;
            //
            //    } else {
            //        throw new HtmlException(tr('html_select(): Invalid resource of type "%type%" specified, should be either null, an array, or a PDOStatement object', array('%type%' => gettype($params['resource']))), 'invalid');
            //    }
            //}
        }

        if ($params['bodyonly']) {
            return self::renderBody($params);
        }

        // <select> class should not be applied to <option>
        $class = $params['class'];
        $params['class'] = $params['option_class'];

        $body = html_select_body($params);

        if (substr($params['id'], -2, 2) == '[]') {
            $params['id'] = substr($params['id'], 0, -2).$count++;
        }

        if ($params['multiple']) {
            $params['multiple'] = ' multiple="multiple"';

        } else {
            $params['multiple'] = '';
        }

        if ($params['disabled']) {
            // Add a hidden element with the name to ensure that multiple selects with [] will not show holes
            return '<select'.$params['multiple'].($params['tabindex'] ? ' tabindex="'.$params['tabindex'].'"' : '').($params['id'] ? ' id="'.$params['id'].'_disabled"' : '').' name="'.$params['name'].'" '.($class ? ' class="'.$class.'"' : '').($params['extra'] ? ' '.$params['extra'] : '').' readonly disabled>'.
                $body.'</select><input type="hidden" name="'.$params['name'].'" >';
        } else {
            $return = '<select'.$params['multiple'].($params['id'] ? ' id="'.$params['id'].'"' : '').' name="'.$params['name'].'" '.($class ? ' class="'.$class.'"' : '').($params['disabled'] ? ' disabled' : '').($params['autofocus'] ? ' autofocus' : '').($params['extra'] ? ' '.$params['extra'] : '').'>'.
                $body.'</select>';
        }

        if ($params['onchange']) {
            // Execute the JS code for an onchange
            $return .= html_script('$("#'.$params['id'].'").change(function() { '.$params['onchange'].' });');

        }

        if (!$params['autosubmit']) {
            // There is no onchange and no autosubmit
            return $return;

        } elseif ($params['autosubmit'] === true) {
            // By default autosubmit on the id
            $params['autosubmit'] = $params['name'];
        }

        // Autosubmit on the specified selector
        $params['autosubmit'] = str_replace('[', '\\\\[', $params['autosubmit']);
        $params['autosubmit'] = str_replace(']', '\\\\]', $params['autosubmit']);

        return $return.Html::script('$("[name=\''.$params['autosubmit'].'\']").change(function() { $(this).closest("form").find("input,textarea,select").addClass("ignore"); $(this).closest("form").submit(); });');
    }



    /**
     * Generates and returns the HTML string for only the select body
     *
     * This will return all HTML WITHOUT the <select> tags around it
     *
     * @return string
     */
    public function renderBody(): string
    {
        /*
         * Return the body HTML for a <select> list
         *
         * This function returns only the body (<option> tags) for a <select> list. Typically, html_select() would be used, but this function is useful in situations where only the <option> tags would be required, like for example a web page that dynamically wants to change the contents of a <select> box using an AJAX call
         *
         * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
         * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink
         * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
         * @category Function reference
         * @package html
         * @see html_select()
         * @version 1.26.0: Added documentation
         *
         * @param params $params The parameters for this <select> box
         * @param string $params[class] If specified, <option class="CLASS"> will be used
         * @param string $params[none] If specified, and no <option> is selected due to "selected", this text will be shown. Defaults to "None selected"
         * @param string $params[empty] If specified, and the resource is empty, this text will be shown. Defaults to "None available"
         * @param string $params[selected] If specified, the <option> that has the specified key will be selected
         * @param boolean $params[auto_select] If specified and the resource contains only one item, this item will be autmatically selected
         * @param mixed $params[resource] The resource for the contents of the <select>. May be a key => value array (where each value must be of scalar datatype) or a PDO statement from a query that selects 2 columns, where the first column will be the key and the second column the value.
         * @param mixed $params[data_resource]
         * @return string The body HTML for a <select> tag, containing all <option> tags
         */
        global $_CONFIG;

        try {
            array_params ($params);
            array_default($params, 'class'        , '');
            array_default($params, 'none'         , tr('None selected'));
            array_default($params, 'empty'        , tr('None available'));
            array_default($params, 'selected'     , null);
            array_default($params, 'auto_select'  , true);
            array_default($params, 'data_resource', null);

            if ($params['none']) {
                $return = '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.(($params['selected'] === null) ? ' selected' : '').' value="">'.$params['none'].'</option>';

            } else {
                $return = '';
            }

            if ($params['data_resource'] and !is_array($params['data_resource'])) {
                throw new HtmlException(tr('html_select_body(): Invalid data_resource specified, should be an array, but received a ":gettype"', array(':gettype' => gettype($params['data_resource']))), 'invalid');
            }

            if ($params['resource']) {
                if (is_array($params['resource'])) {
                    if ($params['auto_select'] and ((count($params['resource']) == 1) and !$params['none'])) {
                        /*
                         * Auto select the only available element
                         */
                        $params['selected'] = array_keys($params['resource']);
                        $params['selected'] = array_shift($params['selected']);
                    }

                    /*
                     * Process array resource
                     */
                    foreach ($params['resource'] as $key => $value) {
                        $notempty    = true;
                        $option_data = '';

                        if ($params['data_resource']) {
                            foreach ($params['data_resource'] as $data_key => $resource) {
                                if (!empty($resource[$key])) {
                                    $option_data .= ' data-'.$data_key.'="'.$resource[$key].'"';
                                }
                            }
                        }

                        $return  .= '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.((($params['selected'] !== null) and ($key === $params['selected'])) ? ' selected' : '').' value="'.html_safe($key).'"'.$option_data.'>'.html_safe($value).'</option>';
                    }

                } elseif (is_object($params['resource'])) {
                    if (!($params['resource'] instanceof PDOStatement)) {
                        throw new HtmlException(tr('html_select_body(): Specified resource object is not an instance of PDOStatement'), 'invalidresource');
                    }

                    if ($params['auto_select'] and ($params['resource']->rowCount() == 1)) {
                        /*
                         * Auto select the only available element
                         */
// :TODO: Implement
                    }

                    /*
                     * Process SQL resource
                     */
                    while ($row = sql_fetch($params['resource'], false, PDO::FETCH_NUM)) {
                        $notempty    = true;
                        $option_data = '';

                        /*
                         * To avoid select problems with "none" entries, empty id column values are not allowed
                         */
                        if (!$row[0]) {
                            $row[0] = str_random(8);
                        }

                        /*
                         * Add data- in this option?
                         */
                        if ($params['data_resource']) {
                            foreach ($params['data_resource'] as $data_key => $resource) {
                                if (!empty($resource[$key])) {
                                    $option_data = ' data-'.$data_key.'="'.$resource[$key].'"';
                                }
                            }
                        }

                        $return  .= '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').''.(($row[0] === $params['selected']) ? ' selected' : '').' value="'.html_safe($row[0]).'"'.$option_data.'>'.html_safe($row[1]).'</option>';
                    }

                } else {
                    throw new HtmlException(tr('html_select_body(): Specified resource ":resource" is neither an array nor a PDO statement', array(':resource' => $params['resource'])), 'invalid');
                }
            }


            if (empty($notempty)) {
                /*
                 * No conent (other than maybe the "none available" entry) was added
                 */
                if ($params['empty']) {
                    $return = '<option'.($params['class'] ? ' class="'.$params['class'].'"' : '').' selected value="">'.$params['empty'].'</option>';
                }

                /*
                 * Return empty body (though possibly with "none" element) so that the html_select() function can ensure the select box will be disabled
                 */
                return $return;
            }

            return $return;
    }



    /**
     * Render the select body
     *
     * @return string
     */
    protected function renderHeaders(): string
    {
        return Element::element('select')->render();
    }
}