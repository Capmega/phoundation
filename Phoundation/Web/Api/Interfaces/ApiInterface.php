<?php

declare(strict_types=1);

namespace Phoundation\Web\Api\Interfaces;

use Phoundation\Web\Json\Interfaces\JsonInterface;

interface ApiInterface extends JsonInterface
{
    /**
     * Execute the specified API page
     *
     * @return string|null
     */
    public function execute(): ?string;


    /**
     * Build and send API specific HTTP headers
     *
     * @param string $output
     *
     * @return void
     */
    public function renderHttpHeaders(string $output): void;
}
