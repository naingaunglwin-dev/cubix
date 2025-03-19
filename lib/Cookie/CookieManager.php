<?php

namespace Cubix\Cookie;

class CookieManager
{
    /**
     * CookieManager constructor
     *
     * @param Cookie $cookie The Cookie instance
     */
    public function __construct(private readonly Cookie $cookie)
    {
    }

    /**
     * Sets cookies using the stored cookie data
     *
     * Iterates over the cookie data and sets cookies with appropriate parameters
     *
     * @return void
     */
    public function set(): void
    {
        foreach ($this->cookie->getCookies() as $name => $cookie) {
            setcookie(
                $name,
                $cookie['value'],
                [
                    'expires'  => $this->getFinalTime((int) $cookie['expire']),
                    'path'     => $cookie['path'],
                    'domain'   => $cookie['domain'],
                    'secure'   => $cookie['secure'],
                    'httponly' => $cookie['httponly'],
                ]
            );
        }
    }

    /**
     * Calculates the final expiration time for a cookie based on the current time and the specified expiration time
     *
     * @param int $expire The expiration time of the cookie in seconds
     *
     * @return int The final expiration time of the cookie
     */
    private function getFinalTime(int $expire): int
    {
        return time() + $expire;
    }
}
