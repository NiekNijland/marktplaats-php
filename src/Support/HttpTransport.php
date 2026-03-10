<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Support;

use GuzzleHttp\Psr7\Request;
use NiekNijland\Marktplaats\Exception\ClientException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;

class HttpTransport
{
    /** @var array<string, string> */
    private array $sessionCookies = [];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly int $maxRetries,
        private readonly int $retryDelayMilliseconds,
    ) {}

    public function get(string $url): string
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                $request = $this->withSessionCookies(new Request('GET', $url));
                $response = $this->httpClient->sendRequest($request);
            } catch (ClientExceptionInterface $e) {
                if ($this->shouldRetryAttempt($attempt)) {
                    $this->applyRetryDelay($attempt);

                    continue;
                }

                throw new ClientException('HTTP request failed: '.$e->getMessage(), 0, $e);
            }

            $this->captureSessionCookies($response->getHeader('Set-Cookie'));

            $statusCode = $response->getStatusCode();

            if ($this->shouldRetryStatusCode($statusCode) && $this->shouldRetryAttempt($attempt)) {
                $this->applyRetryDelay($attempt);

                continue;
            }

            if ($statusCode === 204) {
                return '{}';
            }

            if ($statusCode === 200) {
                return (string) $response->getBody();
            }

            throw new ClientException($this->buildStatusErrorMessage($statusCode), $statusCode);
        }
    }

    public function resetSession(): void
    {
        $this->sessionCookies = [];
    }

    private function buildStatusErrorMessage(int $statusCode): string
    {
        if ($statusCode === 400) {
            return 'Marktplaats API returned 400 Bad Request';
        }

        if ($statusCode === 401 || $statusCode === 403) {
            return 'Marktplaats API authorization error (HTTP '.$statusCode.')';
        }

        if ($statusCode === 404) {
            return 'Marktplaats API endpoint not found (HTTP 404)';
        }

        if ($statusCode === 429) {
            return 'Marktplaats API rate limit exceeded (HTTP 429)';
        }

        if ($statusCode >= 500) {
            return 'Marktplaats API server error (HTTP '.$statusCode.')';
        }

        return 'Unexpected HTTP status code: '.$statusCode;
    }

    private function shouldRetryStatusCode(int $statusCode): bool
    {
        return $statusCode === 429 || $statusCode >= 500;
    }

    private function shouldRetryAttempt(int $attempt): bool
    {
        return $attempt <= $this->maxRetries;
    }

    private function applyRetryDelay(int $attempt): void
    {
        if ($this->retryDelayMilliseconds === 0) {
            return;
        }

        $delayMilliseconds = $this->retryDelayMilliseconds * (2 ** ($attempt - 1));
        usleep($delayMilliseconds * 1000);
    }

    private function withSessionCookies(Request $request): Request
    {
        if ($this->sessionCookies === []) {
            return $request;
        }

        $cookieHeader = [];

        foreach ($this->sessionCookies as $name => $value) {
            $cookieHeader[] = $name.'='.$value;
        }

        return $request->withHeader('Cookie', implode('; ', $cookieHeader));
    }

    /**
     * @param  array<string>  $setCookieHeaders
     */
    private function captureSessionCookies(array $setCookieHeaders): void
    {
        foreach ($setCookieHeaders as $header) {
            $cookiePair = explode(';', $header, 2)[0];
            $nameValue = explode('=', $cookiePair, 2);

            $name = trim($nameValue[0]);
            $value = trim($nameValue[1] ?? '');

            if ($name === '') {
                continue;
            }

            if ($value === '') {
                unset($this->sessionCookies[$name]);

                continue;
            }

            $this->sessionCookies[$name] = $value;
        }
    }
}
