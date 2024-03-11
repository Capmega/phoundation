<?php

namespace Phoundation\Web;

use Phoundation\Core\Request;
use Phoundation\Web\Interfaces\WebRequestInterface;


/**
 * Class WebRequest
 *
 *
 *
 * @author Sven Olaf Oostenbrink <sven@medinet.ca>
 * @license This plugin is developed by, and may only exclusively be used by Medinet or customers with written authorization to do so
 * @copyright Copyright (c) 2024 Medinet <copyright@medinet.ca>
 * @package Phoundation\Web
 */
class WebRequest extends Request implements WebRequestInterface
{
    /**
     * Sets if the request should render the entire page or the contents of the page only
     *
     * @var bool $main_content_only
     */
    protected bool $main_content_only;


    /**
     * Request class constructor
     *
     * @param string $executed_file
     * @param array|null $data
     * @param bool $main_content_only
     */
    public function __construct(string $executed_file, ?array $data, bool $main_content_only)
    {
        $this->main_content_only = $main_content_only;
        $this->executed_file     = $executed_file;
        $this->data              = $data;
    }


    /**
     * Returns the file executed for this request
     *
     * @return bool
     */
    public function getMainContentsOnly(): bool
    {
        return $this->main_content_only;
    }
}