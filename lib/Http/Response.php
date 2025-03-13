<?php

namespace Cubix\Http;

class Response
{
    /**
     *  An array of supported content types
     *
     * @var array|string[]
     */
    private array $contentTypes = [
        'application/java-archive',
        'application/EDI-X12',
        'application/EDIFACT',
        'application/javascript (obsolete)',
        'application/octet-stream',
        'application/ogg',
        'application/pdf',
        'application/xhtml+xml',
        'application/x-shockwave-flash',
        'application/json',
        'application/ld+json',
        'application/xml',
        'application/zip',
        'application/x-www-form-urlencoded',
        'audio/mpeg',
        'audio/x-ms-wma',
        'audio/vnd.rn-realaudio',
        'audio/x-wav',
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/tiff',
        'image/vnd.microsoft.icon',
        'image/x-icon',
        'image/vnd.djvu',
        'image/svg+xml',
        'multipart/mixed',
        'multipart/alternative',
        'multipart/related',
        'multipart/form-data',
        'text/css',
        'text/csv',
        'text/html',
        'text/javascript',
        'text/plain',
        'text/xml',
        'video/mpeg',
        'video/mp4',
        'video/quicktime',
        'video/x-ms-wmv',
        'video/x-msvideo',
        'video/x-flv',
        'video/webm',
        'application/vnd.android.package-archive',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.presentation',
        'application/vnd.oasis.opendocument.graphics',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.mozilla.xul+xml',
    ];

    /**
     *  An array of supported HTTP status codes.
     *
     * @var array
     */
    private array $statusCodes = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'See Other',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        421 => 'Misdirected Request',
        422 => 'Unprocessable Content',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        499 => 'Client Closed Request',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        599 => 'Network Connect Timeout Error'
    ];

    /**
     * Indicates if the response has been sent
     *
     * @var bool
     */
    private bool $sent = false;

    /**
     * Response constructor
     *
     * @param string $body Response body
     * @param int $statusCode HTTP status code
     * @param array $headers HTTP headers
     */
    public function __construct(
        private string $body = '',
        private int $statusCode = 200,
        private array $headers = []
    )
    {
    }

    /**
     * Sets the HTTP status code
     *
     * @param int $code HTTP status code
     *
     * @return Response
     */
    public function setStatusCode(int $code): Response
    {
        $this->statusCode = $code;

        return $this;
    }

    /**
     * Gets the HTTP status code
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Sets a response header
     *
     * @param string $key   Header name
     * @param string $value Header value
     *
     * @return Response
     */
    public function setHeader(string $key, string $value): Response
    {
        $this->headers[$key] = $value;

        return $this;
    }

    /**
     * Sets multiple response headers
     *
     * @param array $headers Array of headers
     *
     * @return Response
     */
    public function setHeaders(array $headers): Response
    {
        foreach ($headers as $key => $value) {
            $this->setHeader($key, $value);
        }

        return $this;
    }

    /**
     * Gets a response header
     *
     * @param string $key    Header name
     * @param mixed $default Default value if header is not found
     *
     * @return mixed
     */
    public function getHeader(string $key, mixed $default = null): mixed
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Gets all response headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Sets the response body
     *
     * @param string $body Response body
     *
     * @return Response
     */
    public function setBody(string $body): Response
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Gets the response body
     *
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Sets a JSON response
     *
     * @param array $data Response data
     * @param int $status HTTP status code
     *
     * @return Response
     */
    public function json(array $data, int $status = 200): Response
    {
        return $this->setStatusCode($status)
            ->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }

    /**
     * Sets an HTML response
     *
     * @param string $html HTML content
     * @param int $status  HTTP status code
     * @param bool $escape Whether to escape HTML entities
     *
     * @return Response
     */
    public function html(string $html, int $status = 200, bool $escape = true): Response
    {
        if ($escape) {
            $html = htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        return $this->setStatusCode($status)
            ->setHeader('Content-Type', 'text/html')
            ->setBody($html);
    }

    /**
     * Sets cache headers
     *
     * @param int $seconds Cache duration in seconds
     *
     * @return Response
     */
    public function cache(int $seconds): Response
    {
        return $this->setHeader('Cache-Control', "public, max-age={$seconds}")
            ->setHeader('Expires', gmdate('D, d M Y H:i:s', time() + $seconds) . ' GMT');
    }

    /**
     * Prepares headers to be sent
     *
     * @return void
     */
    private function prepareHeaderToBeSent(): void
    {
        if (headers_sent()) {
            return;
        }

        header(
            sprintf(
                'HTTP/%s %d %s',
                Request::createFromGlobals()->protocol() ?? '1.1',
                $this->statusCode,
                $this->statusCodes[$this->statusCode]
            ),
            $this->statusCode
        );

        foreach ($this->headers as $key => $value) {
            if ($this->validateHeader($key, $value)) {
                header("{$key}: {$value}");
            }
        }
    }

    /**
     * Sends the response
     *
     * @return void
     */
    public function send(): void
    {
        if ($this->sent) {
            return;
        }

        http_response_code($this->statusCode);

        $this->prepareHeaderToBeSent();

        echo $this->body;

        $this->sent = true;
    }

    /**
     * Validates a header name and value
     *
     * @param string $name  Header name
     * @param string $value Header value
     *
     * @return bool
     */
    private function validateHeader(string $name, string $value): bool
    {
        $name  = trim($name);
        $value = trim($value);

        if (
            empty($name)
            || strlen($value) > 1024
            || preg_match("/[\r\n]/", $value)
        ) {
            return false;
        }

        foreach (['Referer', 'Origin'] as $header) {
            if ($name === $header && filter_var($value, FILTER_VALIDATE_URL) === false) return false;
        }

        if ($name === 'Content-Type' && !in_array($value, $this->contentTypes, true)) return false;

        return true;
    }
}
