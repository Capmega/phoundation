<?php

/**
 * Class RequestRestrictions
 *
 * This class manages restrictions for HTTP request methods. Pages can individually allow, require, or restrict the use
 * of GET, POST, or POST-with-file-uploads requests
 *
 * Restrictions can be defined as follows:
 *
 * NULL  means allowed
 * TRUE  means required
 * FALSE means restricted
 *
 * By default, GET requests are allowed, all other types of request methods are restricted
 *
 * The virtual request method "upload" is a post-request method that also has files uploaded
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests\Restrictions;

use Phoundation\Core\Log\Log;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Requests\Restrictions\Exception\RequestMethodRestrictionsException;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Restrictions\Interfaces\RequestMethodRestrictionsInterface;


class RequestMethodRestrictions implements RequestMethodRestrictionsInterface
{
    /**
     * Tracks the restrictions for the different methods
     *
     * NULL  means allowed
     * TRUE  means required
     * FALSE means restricted
     *
     * By default, GET requests are allowed, all other types of request methods are restricted
     *
     * The virtual request method "upload" is a post-request method that also has files uploaded
     *
     * @var array $restrictions
     */
    protected array $restrictions = [
        'get'     => null,
        'post'    => false,
        'files'   => false,
        'head'    => false,
        'trace'   => false,
        'delete'  => false,
        'options' => false,
        'patch'   => false,
        'upload'  => false,
    ];


    /**
     * Sets the specified request method to be required
     *
     * @param EnumHttpRequestMethod $method
     *
     * @return static
     */
    public function require(EnumHttpRequestMethod $method): static
    {
        if ($method === EnumHttpRequestMethod::upload) {
            // UPLOAD isn't a real method, use POST request instead
            $method = EnumHttpRequestMethod::post;
            $upload = true;
        }

        $method_string = $method->value;

        // If a specific method is required, no other method will be allowed
        foreach ($this->restrictions as $method_key => $value) {
            if ($value === true) {
                if ($method_key === $method_string) {
                    // This method has already been set to be required
                    return $this;
                }

                throw RequestMethodRestrictionsException::new(tr('Cannot require requests with method ":method" as pages already have been specified to be required with method ":before"', [
                    ':method' => strtoupper(Request::getRequestMethod()->value),
                    ':before' => $method_key
                ]))->registerIncident(EnumSeverity::high);
            }
        }

        if (isset($upload)) {
            $this->restrictions['upload'] = true;

            if (Request::isRequestMethod($method)) {
                if (!Request::getFileUploadHandlersObject()->hasUploadedFiles()) {
                    throw RequestMethodRestrictionsException::new(tr('Page ":page" request with method "POST" without file uploads has been restricted, this page only allows "POST" requests with file uploads', [
                        ':page' => Strings::from(Request::getTarget(), '/web/')
                    ]))->registerIncident(EnumSeverity::high);
                }

                return $this;
            }

        } else {
            $this->restrictions[$method_string] = true;

            if (Request::isRequestMethod($method)) {
                return $this;
            }
        }

        throw RequestMethodRestrictionsException::new(tr('Page ":page" request with method ":method" has been restricted, this page only allows ":allowed" requests', [
            ':page'    => Strings::from(Request::getTarget(), '/web/'),
            ':method'  => strtoupper(Request::getRequestMethod()->value),
            ':allowed' => strtoupper($method->value)
        ]))->registerIncident(EnumSeverity::high);
    }


    /**
     * Sets the specified request method to be allowed
     *
     * @param EnumHttpRequestMethod $method
     *
     * @return static
     */
    public function allow(EnumHttpRequestMethod $method): static
    {
        if ($method === EnumHttpRequestMethod::upload) {
            // Upload isn't a real method
            $method = EnumHttpRequestMethod::post;
            $upload = true;
        }

        $this->restrictions[$method->value] = true;
        return $this;
    }


    /**
     * Sets the specified request method to be restricted
     *
     * @param EnumHttpRequestMethod $method
     *
     * @return static
     */
    public function restrict(EnumHttpRequestMethod $method): static
    {
        if ($method === EnumHttpRequestMethod::upload) {
            // Upload isn't a real method
            $method = EnumHttpRequestMethod::post;
            $upload = true;
        }

        $method_string = $method->value;
        $this->restrictions[$method_string] = false;

        if (Request::isRequestMethod($method)) {
            if (isset($upload)) {
                if (Request::getFileUploadHandlersObject()->hasUploadedFiles()) {
                    throw RequestMethodRestrictionsException::new(tr('Page ":page" request with method ":method" has been restricted, it does not permit ":allowed" requests with file uploads', [
                        ':page'    => Strings::from(Request::getTarget(), '/web/'),
                        ':method'  => strtoupper(Request::getRequestMethod()->value),
                        ':allowed' => strtoupper(Request::getRequestMethod()->value)
                    ]))->registerIncident(EnumSeverity::high);
                }

            } else {
                throw RequestMethodRestrictionsException::new(tr('Page ":page" request with method ":method" has been restricted, it does not permit ":allowed" requests', [
                    ':page'    => Strings::from(Request::getTarget(), '/web/'),
                    ':method'  => strtoupper(Request::getRequestMethod()->value),
                    ':allowed' => strtoupper(Request::getRequestMethod()->value)
                ]))->registerIncident(EnumSeverity::high);
            }
        }

        return $this;
    }


    /**
     * Checks if all restrictions are satisfied, will throw an exception otherwise
     *
     * @return static
     */
    public function checkRestrictions(): static
    {
        $method = Request::getRequestMethod()->value;

        if ($method === 'post') {
            if (Request::getFileUploadHandlersObject()->hasUploadedFiles()) {
                $method = 'upload';
            }
        }

        $restrictions = isset_get($this->restrictions[$method]);

        if ($restrictions === false) {
            // This method is restricted!
            if ($method === 'upload') {
                throw RequestMethodRestrictionsException::new(tr('Page ":page" request with method ":method" has been restricted, it does not permit ":allowed" requests with file uploads', [
                    ':page'    => Strings::from(Request::getTarget(), '/web/'),
                    ':method'  => strtoupper(Request::getRequestMethod()->value),
                    ':allowed' => strtoupper(Request::getRequestMethod()->value)
                ]))->registerIncident(EnumSeverity::high);
            }

            throw RequestMethodRestrictionsException::new(tr('Page ":page" request with method ":method" has been restricted, it does not permit ":allowed" requests', [
                ':page'    => Strings::from(Request::getTarget(), '/web/'),
                ':method'  => strtoupper(Request::getRequestMethod()->value),
                ':allowed' => strtoupper(Request::getRequestMethod()->value)
            ]))->registerIncident(EnumSeverity::high);
        }

        return $this;
    }


    /**
     * Clears all request method restrictions
     *
     * @return $this
     */
    public function clear(): static
    {
        $this->restrictions = [];

        return $this;
    }
}
