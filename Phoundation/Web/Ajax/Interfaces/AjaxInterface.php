<?php

declare(strict_types=1);

namespace Phoundation\Web\Ajax\Interfaces;

use Phoundation\Web\Json\Interfaces\JsonInterface;

interface AjaxInterface extends JsonInterface
{
    /**
     * Execute the specified AJAX API page
     *
     * @return string|null
     */
    public function execute(): ?string;


    /**
     * Build and send AJAX API specific HTTP headers
     *
     * @param string $output
     *
     * @return void
     */
    public function renderHttpHeaders(string $output): void;
}
