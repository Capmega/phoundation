<?php

declare(strict_types=1);

namespace Phoundation\Web\Json\Interfaces;

interface JsonInterface
{
    /**
     * Execute the specified JSON page
     *
     * @return string|null
     */
    public function execute(): ?string;


    /**
     * Build and send JSON specific HTTP headers
     *
     * @param string $output
     *
     * @return void
     */
    public function renderHttpHeaders(string $output): void;
}
