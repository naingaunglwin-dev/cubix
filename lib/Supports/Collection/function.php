<?php

use Cubix\Supports\Collection\Collection;

if (!function_exists('collect')) {
    /**
     * Create a new collection instance
     *
     * @param array $items An array of items to be wrapped in a collection
     *
     * @return Collection The newly created collection instance
     */
    function collect(array $items): Collection
    {
        return new Collection($items);
    }
}
