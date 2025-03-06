<?php

namespace Cubix\Http;

class URL
{
    /**
     * Parsed components of the URL
     *
     * @var array
     */
    private array $parsed;

    /**
     * The original URL string provided to the class
     *
     * @var string
     */
    private string $url;

    /**
     * Cached path segments
     *
     * @var array|null
     */
    private ?array $segments = null;

    /**
     * Cached parsed query parameters
     *
     * @var array|null
     */
    private ?array $queryParams = null;

    /**
     * URL constructor
     *
     * @param string $url The URL to be parsed.
     */
    public function __construct(string $url = '')
    {
        $this->parsed = parse_url($url ?: Request::createFromGlobals()->fullUrl());
        $this->url = $url;
    }

    /**
     * Get or set the path component of the URL.
     *
     * @param string|null $path Optional path to set.
     *
     * @return string|null The current path component, or null if not present.
     */
    public function path(?string $path = null): ?string
    {
        if ($path !== null) {
            $this->set('path', $path);
            $this->segments = null; // Invalidate cache
        }
        return $this->get('path');
    }

    /**
     * Get the segments of the URL path.
     *
     * @param int|null $index The index of the segment to return (optional).
     *
     * @return array|string|null An array of segments, a specific segment by index, or null if not found.
     */
    public function segments(?int $index = null): array|string|null
    {
        if ($this->segments === null) {
            $path = trim($this->get('path', ''), '/');
            $this->segments = $path ? explode('/', $path) : [];
        }
        return $index !== null ? ($this->segments[$index] ?? null) : $this->segments;
    }

    /**
     * Get or set the scheme of the URL.
     *
     * @param string|null $scheme The scheme to set (e.g., 'http', 'https').
     *
     * @return string|null The current scheme or null if not set.
     */
    public function scheme(?string $scheme = null): ?string
    {
        if (in_array($scheme, ['http', 'https'])) {
            $this->set('scheme', $scheme);
        }
        return $this->get('scheme');
    }

    /**
     * Get or set the host of the URL
     *
     * @param string|null $host The host to set
     *
     * @return string|null The current host or null if not set
     */
    public function host(?string $host = null): ?string
    {
        if ($host !== null) {
            $this->set('host', $host);
        }
        return $this->get('host');
    }

    /**
     * Get or set the port of the URL
     *
     * @param int|null $port The port to set
     *
     * @return int|null The current port or null if not set
     */
    public function port(?int $port = null): ?int
    {
        if ($port !== null) {
            $this->set('port', $port);
        }
        return $this->get('port');
    }

    /**
     * Get the query string or parsed query parameters.
     *
     * @param bool $parsed Whether to return the parsed query parameters.
     *
     * @return array|string|null The query string or parsed parameters, or null if not set.
     */
    public function query(bool $parsed = false): array|string|null
    {
        if ($parsed) {
            if ($this->queryParams === null) {
                parse_str($this->get('query') ?? '', $this->queryParams);
            }
            return $this->queryParams;
        }
        return $this->get('query');
    }

    /**
     * Add a query parameter to the URL
     *
     * @param string $key The query parameter key
     * @param string $value The query parameter value
     *
     * @return URL
     */
    public function addQuery(string $key, string $value): URL
    {
        $params = $this->query(true);
        $params[$key] = $value;
        $this->set('query', http_build_query($params));
        $this->queryParams = $params;

        return $this;
    }

    /**
     * Remove a query parameter from the URL
     *
     * @param string $key The query parameter key to remove
     *
     * @return URL
     */
    public function removeQuery(string $key): URL
    {
        $params = $this->query(true);
        unset($params[$key]);
        $this->set('query', http_build_query($params));
        $this->queryParams = $params; // Update cache

        return $this;
    }

    /**
     * Get or set the fragment component of the URL
     *
     * @param string|null $fragment The fragment to set
     *
     * @return string|null The current fragment or null if not set
     */
    public function fragment(?string $fragment = null): ?string
    {
        if ($fragment !== null) {
            $this->set('fragment', $fragment);
        }
        return $this->get('fragment');
    }

    /**
     * Determine if the URL uses HTTPS
     *
     * @return bool True if the scheme is 'https', false otherwise
     */
    public function secure(): bool
    {
        return $this->get('scheme') === 'https';
    }

    /**
     * Get a specific parsed component of the URL
     *
     * @param string $key    The component key (e.g., 'scheme', 'host')
     * @param mixed $default The default value if the component is not set
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->parsed[$key] ?? $default;
    }

    /**
     * Build and return the complete URL from its components
     *
     * @return string The fully constructed URL including scheme, host, port, path, query, and fragment
     */
    public function build(): string
    {
        return self::new(
            $this->get('scheme', 'https'),
            $this->get('host'),
            (int) $this->get('port', 80),
            $this->get('path'),
            $this->query(),
            $this->get('fragment')
        );
    }

    /**
     * Check if the URL starts with "http://" or "https://"
     *
     * @return bool
     */
    public function isUrlStartWithScheme(): bool
    {
        return preg_match('/^https?:\/\//', $this->url) === 1;
    }

    /**
     * Validate the URL format
     *
     * @return bool True if the URL is valid, false otherwise
     */
    public function validate(): bool
    {
        return $this->isUrlStartWithScheme() && filter_var($this->url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Set a specific parsed component of the URL
     *
     * @param string $key The component key to set
     * @param mixed $value The value to set
     *
     * @return void
     */
    private function set(string $key, mixed $value): void
    {
        $this->parsed[$key] = $value;
        if ($key === 'query') {
            $this->queryParams = null;
        }
    }

    /**
     * Create a new URL from its components
     *
     * @param string $scheme   The URL scheme
     * @param string $host     The URL host
     * @param int $port        The URL port
     * @param string $path     The URL path
     * @param string $query    The URL query string
     * @param string $fragment The URL fragment string
     *
     * @return string The constructed URL
     */
    public static function new(string $scheme, string $host, int $port = 80, string $path = '', string $query = '', string $fragment = ''): string
    {
        $portStr = ($port == 80) ? '' : ":$port";
        $path = rtrim($path, '/');
        $queryStr = $query ? ('?' . ltrim($query, '?')) : '';
        $fragmentStr = $fragment ? ('#' . ltrim($fragment, '#')) : '';
        return "$scheme://$host$portStr$path$queryStr$fragmentStr";
    }
}
