<?php

namespace Cubix\Supports\Collection;

class Collection
{
    /**
     * The collection of items
     *
     * @var array
     */
    private array $items;

    /**
     * Collection constructor
     *
     * @param array $collection The initial collection of items
     */
    public function __construct(array $collection = [])
    {
        $this->items = $collection;
    }

    /**
     * Get an item by key
     *
     * @param string|int $key The key of the item to retrieve
     *
     * @return mixed|null The item or null if not found
     */
    public function get(string|int $key): mixed
    {
        return $this->traversed($this->items, $key) ?? null;
    }

    /**
     * Add an item to the collection
     *
     * @param string|int $key The key of the item to add
     * @param mixed $value The value of the item
     * @param bool $overwrite Whether to overwrite an existing item with the same key
     *
     * @return Collection
     */
    public function add(string|int $key, mixed $value, bool $overwrite = false): Collection
    {
        if (is_string($key) && str_contains($key, '.')) {
            $keys = explode('.', $key);
            $array = &$this->items;

            foreach ($keys as $k) {
                if (!isset($array[$k]) || !is_array($array[$k])) {
                    $array[$k] = [];
                }
                $array = &$array[$k];
            }

            if ($overwrite || !isset($array) || empty($array)) {
                $array = $value;
            }
        } else {
            if ($overwrite || !$this->has($key)) {
                $this->items[$key] = $value;
            }
        }

        return $this;
    }

    /**
     * Push one or more values onto the end of the collection
     *
     * @param mixed ...$value The values to push
     *
     * @return Collection
     */
    public function push(...$value): Collection
    {
        foreach ($value as $item) {
            $this->items[] = $item;
        }

        return $this;
    }

    /**
     * Check if the collection has a given key
     *
     * @param string|int $key The key to check
     *
     * @return bool True if the key exists, false otherwise
     */
    public function has(string|int $key): bool
    {
        if (is_string($key) && str_contains($key, '.')) {
            $keys = explode('.', $key);
            $array = $this->items;

            foreach ($keys as $k) {
                if (!is_array($array) || !array_key_exists($k, $array)) {
                    return false;
                }
                $array = $array[$k];
            }

            return true;
        }

        return array_key_exists($key, $this->items);
    }

    /**
     * Remove an item from the collection by key
     *
     * @param string $key The key of the item to remove
     *
     * @return Collection
     */
    public function remove(string $key): static
    {
        if (str_contains($key, '.')) {
            $keys = explode('.', $key);
            $array = &$this->items;

            foreach ($keys as $i => $k) {
                if (!is_array($array) || !array_key_exists($k, $array)) {
                    return $this;
                }

                if ($i === count($keys) - 1) {
                    unset($array[$k]);
                } else {
                    $array = &$array[$k];
                }
            }
        } else {
            unset($this->items[$key]);
        }

        return $this;
    }

    /**
     * Clear all items from the collection
     *
     * @return $this
     */
    public function clear(): Collection
    {
        $this->items = [];

        return $this;
    }

    /**
     * Implode the collection into a string using a separator
     *
     * @param string $separator The separator string
     *
     * @return string The imploded string
     */
    public function implode(string $separator = ','): string
    {
        return implode($separator, $this->items);
    }

    /**
     * Get all items as an array
     *
     * @return array The collection as an array
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Convert the collection to an object
     *
     * @return object The collection as an object
     */
    public function toObject(): object
    {
        return (object)$this->items;
    }

    /**
     * Convert the collection to a JSON string
     *
     * @return false|string The collection as a JSON string or false on failure
     */
    public function toJson(): false|string
    {
        return json_encode($this->items);
    }

    /**
     * Execute a callback over each item in the collection
     *
     * @param callable $callback The callback to apply to each item
     *
     * @return void
     */
    public function each(callable $callback): void
    {
        foreach ($this->items as $key => $value) {
            $callback($value, $key);
        }
    }

    /**
     * Get the next item in the collection relative to the given key or value
     *
     * @param string|null $key The current key
     * @param mixed|null $value The current value
     *
     * @return mixed The next item or null if it does not exist
     */
    public function next(?string $key = null, mixed $value = null): mixed
    {
        if (is_null($key) && is_null($value)) return null;

        $keys = $this->keys();

        if (is_null($value)) {
            $index = $keys->search($key);

            $keys = $keys->toArray();

            if (!isset($keys[$index + 1])) {
                return null;
            }

            $next = $keys[$index + 1];

            return $next ?? null;
        }

        if (is_null($key)) {
            $original = $this->search($value);

            $index = $keys->search($original);

            $keys = $keys->toArray();

            if (!isset($keys[$index + 1])) {
                return null;
            }

            $next = $keys[$index + 1];

            return $next === null ? $next : $this->get($next);
        }

        $key = $this->next($key);

        return [
            $key => $this->toArray()[$key]
        ];
    }

    /**
     * Get the previous item in the collection relative to the given key or value
     *
     * @param string|null $key The current key
     * @param mixed|null $value The current value
     *
     * @return mixed The previous item or null if it does not exist
     */
    public function previous(?string $key = null, mixed $value = null): mixed
    {
        if (is_null($key) && is_null($value)) return null;

        $keys = $this->keys();

        if (is_null($value)) {
            $index = $keys->search($key);

            $keys = $keys->toArray();

            if (!isset($keys[$index - 1])) {
                return null;
            }

            $next = $keys[$index - 1];

            return $next ?? null;
        }

        if (is_null($key)) {
            $original = $this->search($value);

            $index = $keys->search($original);

            $keys = $keys->toArray();

            if (!isset($keys[$index - 1])) {
                return null;
            }

            $next = $keys[$index - 1];

            return $next === null ? $next : $this->get($next);
        }

        $key = $this->previous($key);

        return [
            $key => $this->toArray()[$key]
        ];
    }

    /**
     * Replace an item in the collection by key
     *
     * @param string|int $key The key of the item to replace
     * @param mixed $value The new value
     *
     * @return array The updated collection
     */
    public function replace(string|int $key, mixed $value): array
    {
        $this->add($key, $value, true);

        return $this->all();
    }

    /**
     * Reverse the order of the collection
     *
     * @return Collection The reversed collection
     */
    public function reverse(): Collection
    {
        return new static(array_reverse($this->items));
    }

    /**
     * Get the first item in the collection
     *
     * @param callable|null $callback Optional callback to filter items
     *
     * @return mixed The first item, or null if none found
     */
    public function first(?callable $callback = null): mixed
    {
        if (null === $callback) {
            return reset($this->items);
        }

        foreach ($this->items as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Get the last item in the collection
     *
     * @param callable|null $callback Optional callback to filter items
     *
     * @return mixed The last item, or null if none found
     */
    public function last(?callable $callback = null): mixed
    {
        if (null === $callback) {
            return end($this->items);
        }

        foreach ($this->reverse()->toArray() as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }

    /**
     * Get all values in the collection
     *
     * @param callable|null $callback Optional callback to filter values
     *
     * @return Collection The collection of values
     */
    public function values(?callable $callback = null): Collection
    {
        return new static(
            array_values(
                $callback === null
                    ? $this->items
                    : $this->filter($callback)->all()
            )
        );
    }

    /**
     * Get all the keys from the collection, optionally filtered by a callback
     *
     * @param callable|null $callback Optional callback to filter the keys
     *
     * @return Collection A collection containing the keys
     */
    public function keys(?callable $callback = null): Collection
    {
        return new static(
            array_keys(
                $callback === null
                    ? $this->items
                    : $this->filter($callback)->all()
            )
        );
    }

    /**
     * Get unique items from the collection
     *
     * @param int $flag Optional flag to determine the uniqueness comparison behavior
     *
     * @return array The unique items
     */
    public function unique(int $flag = SORT_STRING): array
    {
        return array_unique($this->items, $flag);
    }

    /**
     * Get all odd-indexed items from the collection
     *
     * @return array The items at odd indexes
     */
    public function odd(): array
    {
        return $this->filter(fn ($value, $key) => is_int($key) && $key % 2 !== 0)->all();
    }

    /**
     * Get all even-indexed items from the collection
     *
     * @return array The items at even indexes
     */
    public function even(): array
    {
        return $this->filter(fn ($value, $key) => is_int($key) && $key % 2 === 0)->all();
    }

    /**
     * Filter the collection using a callback
     *
     * @param callable $callback A callback function to filter the items
     *
     * @return Collection A new filtered collection
     */
    public function filter(callable $callback): Collection
    {
        return new static(
            array_filter(
                $this->items,
                $callback,
                ARRAY_FILTER_USE_BOTH
            )
        );
    }

    /**
     * Map the collection using a callback
     *
     * @param callable $callback A callback function to map the items
     *
     * @return Collection A new mapped collection
     */
    public function map(callable $callback): Collection
    {
        $mapped = [];

        $this->each(function ($value, $key) use ($callback, &$mapped) {
            $mapped[$key] = $callback($value, $key);
        });

        return new static($mapped);
    }

    /**
     * Get all items except for the specified keys
     *
     * @param array|string $keys The keys to exclude from the collection
     *
     * @return Collection A new collection without the specified keys
     */
    public function except(array|string $keys): Collection
    {
        if (is_string($keys)) {
            $keys = [$keys];
        }

        return $this->filter(function ($value, $key) use ($keys) {
            return !in_array($key, $keys, true);
        });
    }

    /**
     * Search for a value in the collection
     *
     * @param callable|mixed $value The value to search for, or a callback function
     * @param bool $strict Whether to use strict comparison
     *
     * @return false|int|string The key if found, or false if not found
     */
    public function search($value, bool $strict = false): false|int|string
    {
        if (is_callable($value)) {
            foreach ($this->items as $key => $value) {
                if ($value($value, $key)) {
                    return $key;
                }
            }

            return false;
        }

        return array_search($value, $this->items, $strict);
    }

    /**
     * Sort the items in the collection
     *
     * @param callable|null $callback Optional callback to customize sorting behavior
     *
     * @return Collection A new collection with sorted items
     */
    public function sort(?callable $callback = null): Collection
    {
        $array = $this->all();

        if (!is_null($callback)) {
            $array = $this->map(fn ($value, $key) => $callback($value, $key))->all();
        }

        sort($array);

        return new static($array);
    }

    /**
     * Sort the items in reverse order
     *
     * @param callable|null $callback Optional callback to customize reverse sorting behavior
     *
     * @return Collection A new collection with reverse sorted items
     */
    public function rsort(?callable $callback = null): Collection
    {
        $array = $this->all();

        if (!is_null($callback)) {
            $array = $this->map(fn ($value, $key) => $callback($value, $key))->all();
        }

        rsort($array);

        return new static($array);
    }

    /**
     * Update the collection items using a callback function
     *
     * @param callable|null $callback Optional callback to update the items
     *
     * @return Collection The updated collection.
     */
    public function update(?callable $callback = null): Collection
    {
        $this->items = $this->map($callback)->all();

        return $this;
    }

    /**
     * Get the number of items in the collection
     *
     * @return int The number of items
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get all items in the collection
     *
     * @return array The collection items
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Check whether items array is empty or not
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * @param array $array
     * @param string $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    private function traversed(array $array, string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);

        foreach ($keys as $k) {
            if (!is_array($array) || !array_key_exists($k, $array)) {
                return $default;
            }

            $array = $array[$k];
        }

        return $array;
    }
}
