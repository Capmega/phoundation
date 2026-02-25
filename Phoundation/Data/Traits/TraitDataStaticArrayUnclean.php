<?php

namespace Phoundation\Data\Traits;

trait TraitDataStaticArrayUnclean {
    /**
     * Tracks unclean records that were not validated before static::validate() was called
     *
     * @var array|null $unclean
     */
    protected static ?array $unclean = null;


    /**
     * Returns the unclean records that were left after static::validate() was called
     *
     * @return array|null
     */
    public function getUnclean(): ?array
    {
        return static::$unclean;
    }
}