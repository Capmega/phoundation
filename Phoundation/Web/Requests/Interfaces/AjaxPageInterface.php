<?php

declare(strict_types=1);

namespace Phoundation\Web\Requests\Interfaces;

interface AjaxPageInterface extends JsonPageInterface
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
