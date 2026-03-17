<?php

declare(strict_types=1);

namespace NiekNijland\Marktplaats\Support;

use GuzzleHttp\Psr7\Request;
use NiekNijland\Marktplaats\Exception\ClientException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as HttpClientInterface;

class HttpTransport
{
    private const string SEARCH_ENDPOINT_PATH = '/lrp/api/search';

    /** @var array<string, string> */
    private const array COMMON_BROWSER_HEADERS = [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        'Accept-Language' => 'nl-NL,nl;q=0.9,en-US;q=0.8,en;q=0.7',
        'Accept-Encoding' => 'gzip, deflate',
    ];

    /** @var array<string, string> */
    private const array SEARCH_BROWSER_HEADERS = [
        'Accept' => 'application/json, text/plain, */*',
    ];

    /** @var array<string, string> */
    private const array DOCUMENT_BROWSER_HEADERS = [
        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
        'Upgrade-Insecure-Requests' => '1',
    ];

    /** @var array<string, string> */
    private array $sessionCookies = [];

    private int $operationCount = 0;

    private int $requestCount = 0;

    private int $successCount = 0;

    private int $failureCount = 0;

    private int $retryCount = 0;

    private int $sessionResetCount = 0;

    private ?float $lastRequestAt = null;

    private float $totalSleepMs = 0.0;

    private readonly ClockInterface $clock;

    /**
     * @param  array<string, string>  $defaultHeaders
     */
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly int $maxRetries,
        private readonly int $retryDelayMilliseconds,
        private readonly array $defaultHeaders = [],
        ?ClockInterface $clock = null,
        private readonly int $requestDelayMilliseconds = 0,
        private readonly int $requestDelayJitterMilliseconds = 0,
        private readonly int $maxRequestsPerWindow = 0,
    ) {
        $this->clock = $clock ?? new SystemClock;
    }

    public function get(string $url): string
    {
        $this->applyRequestDelay();
        $this->operationCount++;

        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                $request = $this->buildRequest($url);
                $this->trackRequestAttempt();
                $response = $this->httpClient->sendRequest($request);
            } catch (ClientExceptionInterface $e) {
                if ($this->shouldRetryAttempt($attempt)) {
                    $this->retryCount++;
                    $this->applyRetryDelay($attempt);

                    continue;
                }

                $this->failureCount++;

                throw new ClientException('HTTP request failed: '.$e->getMessage(), 0, $e);
            }

            $this->captureSessionCookies($response->getHeader('Set-Cookie'));

            $statusCode = $response->getStatusCode();

            if ($this->shouldRetryStatusCode($statusCode) && $this->shouldRetryAttempt($attempt)) {
                if ($statusCode === 403) {
                    $this->resetSession();
                }

                $this->retryCount++;
                $this->applyRetryDelay($attempt);

                continue;
            }

            if ($statusCode === 204) {
                $this->successCount++;

                return '{}';
            }

            if ($statusCode === 200) {
                $this->successCount++;

                return (string) $response->getBody();
            }

            $this->failureCount++;

            throw new ClientException($this->buildStatusErrorMessage($statusCode), $statusCode);
        }
    }

    /**
     * @return array{
     *     requests: int,
     *     successes: int,
     *     failures: int,
     *     retries: int,
     *     session_resets: int,
     *     last_request_at: ?float,
     *     total_sleep_ms: float,
     * }
     */
    public function getStats(): array
    {
        return [
            'requests' => $this->requestCount,
            'successes' => $this->successCount,
            'failures' => $this->failureCount,
            'retries' => $this->retryCount,
            'session_resets' => $this->sessionResetCount,
            'last_request_at' => $this->lastRequestAt,
            'total_sleep_ms' => $this->totalSleepMs,
        ];
    }

    public function resetStats(): void
    {
        $this->operationCount = 0;
        $this->requestCount = 0;
        $this->successCount = 0;
        $this->failureCount = 0;
        $this->retryCount = 0;
        $this->sessionResetCount = 0;
        $this->lastRequestAt = null;
        $this->totalSleepMs = 0.0;
    }

    public function resetSession(): void
    {
        $this->sessionCookies = [];
        $this->sessionResetCount++;
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
        return $statusCode === 403 || $statusCode === 429 || $statusCode >= 500;
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

        $baseDelay = (int) ($this->retryDelayMilliseconds * (2 ** ($attempt - 1)));
        $jitter = (int) ($baseDelay * 0.25);
        $delayMilliseconds = max(0, $baseDelay + random_int(-$jitter, $jitter));

        $this->sleepMilliseconds($delayMilliseconds);
    }

    private function applyRequestDelay(): void
    {
        if ($this->operationCount === 0 || $this->requestDelayMilliseconds === 0) {
            return;
        }

        $delayMilliseconds = $this->requestDelayMilliseconds
            + random_int(0, $this->requestDelayJitterMilliseconds);

        $this->sleepMilliseconds($delayMilliseconds);
    }

    private function buildRequest(string $url): Request
    {
        $request = new Request('GET', $url);

        foreach ($this->resolveDefaultHeaders($url) as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $this->withSessionCookies($request);
    }

    private function trackRequestAttempt(): void
    {
        $this->requestCount++;
        $this->lastRequestAt = microtime(true);

        if ($this->maxRequestsPerWindow > 0 && $this->requestCount > $this->maxRequestsPerWindow) {
            $this->failureCount++;

            throw new ClientException(
                'Marktplaats request window limit exceeded; call resetStats() before sending more requests.',
            );
        }
    }

    private function sleepMilliseconds(int $milliseconds): void
    {
        $this->clock->sleepMilliseconds($milliseconds);
        $this->totalSleepMs += $milliseconds;
    }

    /**
     * @return array<string, string>
     */
    private function resolveDefaultHeaders(string $url): array
    {
        if ($this->defaultHeaders !== []) {
            return $this->defaultHeaders;
        }

        $path = (string) parse_url($url, PHP_URL_PATH);

        if ($path === self::SEARCH_ENDPOINT_PATH) {
            return [
                ...self::COMMON_BROWSER_HEADERS,
                ...self::SEARCH_BROWSER_HEADERS,
            ];
        }

        return [
            ...self::COMMON_BROWSER_HEADERS,
            ...self::DOCUMENT_BROWSER_HEADERS,
        ];
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
