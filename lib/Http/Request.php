<?php

namespace Cubix\Http;

use Cubix\Foundation\RuntimeEnv;

class Request
{
    /**
     * Stores global request data.
     *
     * @var array
     */
    protected array $globals;

    /**
     * Stores sanitized request data.
     *
     * @var array
     */
    protected array $sanitized;

    /**
     * Cached raw request body
     *
     * @var string|null
     */
    private ?string $rawBody = null;

    /**
     * Cached headers
     *
     * @var array
     */
    private array $headers;

    /**
     * Cached scheme (e.g., http, https)
     *
     * @var string|null
     * */
    private ?string $scheme = null;

    /**
     * Cached host
     *
     * @var string|null
     */
    private ?string $host = null;

    /**
     * Cached full URL
     *
     * @var string|null
     */
    private ?string $fullUrl = null;

    /**
     * Cached request URI
     *
     * @var string|null
     */
    private ?string $requestUri = null;

    /**
     * Request constructor
     *
     * @param array|null $get    The GET parameters.
     * @param array|null $post   The POST parameters.
     * @param array|null $cookie The COOKIE parameters.
     * @param array|null $files  The FILES parameters.
     * @param array|null $server The SERVER parameters.
     */
    public function __construct(
        private readonly ?array $get = null,
        private readonly ?array $post = null,
        private readonly ?array $cookie = null,
        private readonly ?array $files = null,
        private readonly ?array $server = null
    ) {
        $this->globals = [
            'get'    => $this->get ?? $_GET,
            'post'   => $this->post ?? $_POST,
            'cookie' => $this->cookie ?? $_COOKIE,
            'files'  => $this->files ?? $_FILES,
            'server' => $this->server ?? $_SERVER,
        ];
        $this->headers = RuntimeEnv::envIs('cli') ? [] : getallheaders();
        $this->rawBody = file_get_contents("php://input");
        $this->globals['body'] = $this->rawBody ? json_decode($this->rawBody, true) : [];
        $this->sanitize();
    }

    /**
     * Creates a new Request instance from PHP globals
     *
     * @return Request
     */
    public static function createFromGlobals(): Request
    {
        return new Request($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER);
    }

    /**
     * Retrieves a SERVER variable
     *
     * @param string $name   Variable name
     * @param mixed $default Default value if not set
     *
     * @return mixed
     */
    public function server(string $name, mixed $default = null): mixed
    {
        return $this->globals['server'][$name] ?? $default;
    }

    /**
     * Get the server port from the SERVER global array
     *
     * @return mixed The server port
     */
    public function port(): mixed
    {
        return $this->server('SERVER_PORT');
    }

    /**
     * Get the host name from the headers or SERVER global array
     *
     * @return mixed The host name
     */
    public function host(): mixed
    {
        return $this->host ??= ($this->header('HOST') ?? $this->server('HTTP_HOST'));
    }

    /**
     * Retrieves a header value
     *
     * @param string $name Header name
     *
     * @return mixed
     */
    public function header(string $name): mixed
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Get the protocol used in the request (e.g., HTTP/1.1)
     *
     * @return mixed The request protocol
     */
    public function protocol(): mixed
    {
        return $this->server('SERVER_PROTOCOL');
    }

    /**
     * Get the request scheme (e.g., http or https)
     *
     * @return mixed The request scheme
     */
    public function scheme(): mixed
    {
        return $this->scheme ??= $this->server('REQUEST_SCHEME') ?? 'http';
    }

    /**
     * Get the server IP address
     *
     * @return mixed The server IP address.
     */
    public function serveraddr(): mixed
    {
        return $this->server('SERVER_ADDR');
    }

    /**
     * Get the base URL of the request
     *
     * @return string The base URL
     */
    public function baseurl(): string
    {
        $fullUrl = $this->fullUrl();
        $schemeHost = $this->scheme() . "://" . $this->host();
        $request = str_replace($schemeHost, "", $fullUrl);

        if ($request !== $this->requestUri() && $this->requestUri() !== '/') {
            return $this->trimQuery(str_replace($this->requestUri(), "", $fullUrl));
        }

        return URL::new($this->scheme(), $this->host(), $this->port(), $this->trimQuery($request));
    }

    /**
     * Returns the request URI with optional query string
     *
     * @param bool $query Include query string
     *
     * @return string
     */
    public function requestUri(bool $query = false): string
    {
        if ($this->requestUri === null) {
            if (!function_exists('env') || !env('APP_BASE_URL', false)) {
                $this->requestUri = $this->server('REQUEST_URI') ?? '';
            } else {
                $url = str_replace(env('APP_BASE_URL'), "", $this->fullUrl());
                $url = str_ends_with($url, "/") ? substr($url, 0, -1) : $url;
                $this->requestUri = str_starts_with($url, '/') ? $url : "/$url";
            }
        }
        return $query ? $this->requestUri : $this->trimQuery($this->requestUri);
    }

    /**
     * Get the full URL of the current request
     *
     * @return string
     */
    public function fullUrl(): string
    {
        return $this->fullUrl ??= URL::new(
            $this->scheme(),
            $this->host(),
            $this->port(),
            $this->server('REQUEST_URI')
        );
    }

    /**
     * Get the request method
     *
     * @param bool $lowercase Return in lowercase
     *
     * @return string
     */
    public function method(bool $lowercase = false): string
    {
        static $method;
        $method ??= $this->server('REQUEST_METHOD');
        return $lowercase ? strtolower($method) : strtoupper($method);
    }

    /**
     * Get query string or specific GET parameter.
     *
     * @param string|null $key Parameter key
     *
     * @return mixed
     */
    public function query(?string $key = null): mixed
    {
        return $key !== null ? $this->input($key, 'GET') : $this->server('QUERY_STRING');
    }

    /**
     * Retrieves input data
     *
     * @param string $name        Input name
     * @param string|null $method Request method
     * @param mixed $default      Default value
     * @param bool $sanitized     Use sanitized data
     *
     * @return mixed
     */
    public function input(string $name, ?string $method = null, mixed $default = null, bool $sanitized = true): mixed
    {
        $source = $sanitized ? $this->sanitized : $this->globals;
        $method = $method ? strtolower($method) : null;

        if (!$method) {

            foreach (['post', 'get'] as $m) {
                if (($value = $this->traverse($source[$m], $name, $default)) !== $default) break;
            }

            return $value;
        }

        $method = in_array($method, ['put', 'patch', 'delete']) ? 'body' : $method;
        return in_array($method, ['get', 'post', 'body']) ? $this->traverse($source[$method], $name, $default) : $default;
    }

    /**
     * Retrieves file data
     *
     * @param string $name    File input name
     * @param bool $sanitized Use sanitized data
     *
     * @return mixed
     */
    public function file(string $name, bool $sanitized = true): mixed
    {
        $source = $sanitized ? $this->sanitized : $this->globals;
        return $source['files'][$name] ?? null;
    }

    /**
     * Sanitizes all request data once
     *
     * @return void
     */
    protected function sanitize(): void
    {
        $this->sanitized = [
            'get'    => $this->_sanitize('GET'),
            'post'   => $this->_sanitize('POST'),
            'cookie' => $this->_sanitize('COOKIE'),
            'files'  => $this->_sanitize('FILES'),
            'server' => $this->globals['server'],
            'header' => $this->headers,
            'body'   => $this->_sanitize('BODY'),
        ];
    }

    /**
     * Sanitizes data for a specific method
     *
     * @param string $method Method to sanitize
     *
     * @return array
     */
    private function _sanitize(string $method): array
    {
        $data = $this->globals[strtolower($method)] ?? [];
        if (empty($data) && $method !== 'BODY') {
            return [];
        }

        return match ($method) {
            'GET', 'POST', 'COOKIE' => filter_var_array($data, FILTER_SANITIZE_SPECIAL_CHARS) ?: [],
            'FILES' => array_map(function ($file) {
                return [
                    'name' => filter_var($file['name'], FILTER_SANITIZE_SPECIAL_CHARS),
                    'type' => $file['type'],
                    'size' => $file['size'],
                    'tmp_name' => $file['tmp_name'],
                    'error' => $file['error'],
                ];
            }, $data),
            'BODY' => $this->rawBody ? filter_var_array($this->globals['body'], FILTER_SANITIZE_SPECIAL_CHARS) ?: [] : [],
            default => [],
        };
    }

    /**
     * Traverses an array using dot notation
     *
     * @param array $array   Array to traverse
     * @param string $key    Dot notation key
     * @param mixed $default Default value
     *
     * @return mixed
     */
    private function traverse(array $array, string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $current = $array;

        foreach ($keys as $k) {
            if (!is_array($current) || !array_key_exists($k, $current)) {
                return $default;
            }
            $current = $current[$k];
        }
        return $current;
    }

    /**
     * Trims query string from a URL
     *
     * @param string $url URL to trim
     *
     * @return string
     */
    private function trimQuery(string $url): string
    {
        $pos = strpos($url, '?');
        return $pos === false ? $url : substr($url, 0, $pos);
    }
}
