<?php

namespace Cubix\Cookie;

readonly final class CookieOptions
{
    /**
     * CookieOptions constructor
     *
     * @param int    $expire   The expiration time of the cookie in seconds. Default is 0 (session cookie)
     * @param string $path     The path on the server in which the cookie will be available. Default is '/'
     * @param string $domain   The (sub)domain that the cookie is available to. Default is an empty string
     * @param bool   $secure   Whether the cookie should only be transmitted over secure connections. Default is false
     * @param bool   $httponly Whether the cookie should be accessible only through HTTP (not JavaScript). Default is true
     */
    public function __construct(
        private int    $expire = 0,
        private string $path = '/',
        private string $domain = '',
        private bool   $secure = false,
        private bool   $httponly = true,
    )
    {
    }

    /**
     * Get the expiration time of the cookie
     *
     * @return int Expiration time in seconds
     */
    public function getExpire(): int
    {
        return $this->expire;
    }

    /**
     * Get the path of the cookie
     *
     * @return string Cookie path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the domain of the cookie
     *
     * @return string Cookie domain
     */
    public function getDomain(): string
    {
        return $this->domain;
    }

    /**
     * Check if the cookie is secure
     *
     * @return bool True if the cookie is secure, false otherwise
     */
    public function isSecure(): bool
    {
        return $this->secure;
    }

    /**
     * Check if the cookie is HTTP-only
     *
     * @return bool True if the cookie is HTTP-only, false otherwise
     */
    public function isHttponly(): bool
    {
        return $this->httponly;
    }
}
