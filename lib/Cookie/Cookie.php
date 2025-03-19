<?php

namespace Cubix\Cookie;

use Cubix\Supports\Collection\Collection;

class Cookie
{
    /**
     * The default expiration time for cookies in seconds
     *
     * @var int
     */
    private int $expire;

    /**
     * The default path for cookies
     *
     * @var string
     */
    private string $path;

    /**
     * The default domain for cookies
     *
     * @var string
     */
    private string $domain;

    /**
     * Whether cookies should only be sent over secure connections
     *
     * @var bool
     */
    private bool $secure;

    /**
     * Whether cookies should be accessible only through HTTP(S) requests
     *
     * @var bool
     */
    private bool $httponly;

    /**
     * Cookies list to set
     *
     * @var Collection
     */
    public Collection $cookies;

    /**
     * Cookie constructor.
     *
     * Initializes default cookie settings from configuration.
     */
    public function __construct(private readonly CookieOptions $options)
    {
        $this->expire   = $options->getExpire();
        $this->path     = $options->getPath();
        $this->domain   = $options->getDomain();
        $this->secure   = $options->isSecure();
        $this->httponly = $options->isHttponly();

        $this->cookies = new Collection();
    }

    /**
     * Sets a cookie with the specified name and value, using default or provided settings.
     *
     * @param string $name The name of the cookie.
     * @param mixed $value The value of the cookie.
     * @param int|null $expire The expiration time of the cookie in seconds. Default is null (uses default).
     * @param string|null $path The path on the server in which the cookie will be available. Default is null (uses default).
     * @param string|null $domain The domain that the cookie is available to. Default is null (uses default).
     * @param bool|null $secure Whether the cookie should only be transmitted over secure connections. Default is null (uses default).
     * @param bool|null $httponly Whether the cookie should only be accessible through HTTP(S) requests. Default is null (uses default).
     */
    public function set(string $name, mixed $value, ?int $expire = null, ?string $path = null, ?string $domain = null, ?bool $secure = null, ?bool $httponly = null): void
    {
        $expire   ??= $this->expire;
        $path     ??= $this->path;
        $domain   ??= $this->domain;
        $secure   ??= $this->secure;
        $httponly ??= $this->httponly;

        $this->cookies->add($name, compact('value', 'expire', 'path', 'domain', 'secure', 'httponly'));
    }

    /**
     * Retrieves the value of the specified cookie
     *
     * @param string $name The name of the cookie
     *
     * @return mixed|null The value of the cookie if it exists, or null otherwise
     */
    public function get(string $name): mixed
    {
        return $_COOKIE[$name] ?? null;
    }

    /**
     * Checks if a cookie with the specified name exists
     *
     * @param string $name The name of the cookie
     *
     * @return bool True if the cookie exists, false otherwise
     */
    public function has(string $name): bool
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Remove (deletes) the specified cookie
     *
     * @param string $name The name of the cookie to destroy
     */
    public function remove(string $name): void
    {
        $this->set($name, '', time() - 3600);
    }

    /**
     * Retrieves a stored cookie from the cookies list
     *
     * @param string $name   The name of the cookie to retrieve
     * @param mixed $default The default value to return if the cookie is not found
     *
     * @return mixed The value of the cookie if it exists, or the default value otherwise
     */
    public function getCookie(string $name, mixed $default = null): mixed
    {
        return $this->cookies->get($name) ?? $default;
    }

    /**
     * Retrieves all stored cookies
     *
     * @return array The array of stored cookies
     */
    public function getCookies(): array
    {
        return $this->cookies->all();
    }

    /**
     * Retrieves the cookie options instance
     *
     * @return CookieOptions The CookieOptions instance
     */
    public function getOption(): CookieOptions
    {
        return $this->options;
    }
}
