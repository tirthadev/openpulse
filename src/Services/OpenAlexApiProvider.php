<?php

declare(strict_types=1);

namespace OpenPulse\Services;

use InvalidArgumentException;
use JsonException;
use OpenPulse\Contracts\OpenAlexWorksProviderInterface;
use RuntimeException;

final class OpenAlexApiProvider implements OpenAlexWorksProviderInterface
{
    private const API_URL = 'https://api.openalex.org/works';

    private const SELECT_FIELDS =
        'id,doi,title,publication_year,publication_date,type,cited_by_count,open_access';

    private const MAX_ATTEMPTS = 4;

    private const MAX_RETRY_DELAY_SECONDS = 10;

    public function fetchPage(
        ?string $cursor = null,
        int $perPage = 100
    ): array {
        if ($perPage < 1 || $perPage > 100) {
            throw new InvalidArgumentException(
                'OpenAlex per-page value must be between 1 and 100.'
            );
        }

        $apiKey = $this->resolveApiKey();

        if ($apiKey === '') {
            throw new RuntimeException(
                'OPENALEX_API_KEY is missing. Configure it in your local environment.'
            );
        }

        $query = [
            'per-page' => $perPage,
            'cursor' => $cursor ?? '*',
            'select' => self::SELECT_FIELDS,
        ];

        $url = self::API_URL . '?' . http_build_query($query);

        $response = '';
        $httpStatus = 0;
        $curlError = '';
        $retryAfter = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            $curl = curl_init($url);

            if ($curl === false) {
                throw new RuntimeException(
                    'Failed to initialize the OpenAlex HTTP request.'
                );
            }

            $retryAfter = null;

            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Authorization: Bearer ' . $apiKey,
                    'User-Agent: OpenPulse/1.0',
                ],
                CURLOPT_HEADERFUNCTION => static function (
                    \CurlHandle $handle,
                    string $header
                ) use (&$retryAfter): int {
                    if (
                        preg_match(
                            '/^Retry-After:\s*(\d+)\s*$/i',
                            trim($header),
                            $matches,
                        ) === 1
                    ) {
                        $retryAfter = (int) $matches[1];
                    }

                    return strlen($header);
                },
            ]);

            $result = curl_exec($curl);
            $httpStatus = (int) curl_getinfo(
                $curl,
                CURLINFO_HTTP_CODE,
            );
            $curlError = curl_error($curl);
            $curlErrno = curl_errno($curl);

            if (is_string($result)) {
                $response = $result;
            } else {
                $response = '';
            }

            $retryableCurlError = in_array(
                $curlErrno,
                [
                    CURLE_COULDNT_RESOLVE_HOST,
                    CURLE_COULDNT_CONNECT,
                    CURLE_OPERATION_TIMEDOUT,
                    CURLE_RECV_ERROR,
                    CURLE_SEND_ERROR,
                    CURLE_GOT_NOTHING,
                ],
                true,
            );

            $retryableHttpStatus = $httpStatus === 429
                || ($httpStatus >= 500 && $httpStatus <= 599);

            if (
                is_string($result)
                && $httpStatus >= 200
                && $httpStatus < 300
            ) {
                break;
            }

            if (
                $attempt === self::MAX_ATTEMPTS
                || (!$retryableCurlError && !$retryableHttpStatus)
            ) {
                if (!is_string($result)) {
                    throw new RuntimeException(
                        'OpenAlex request failed after '
                        . $attempt
                        . ' attempt(s): '
                        . $curlError
                    );
                }

                throw new RuntimeException(
                    'OpenAlex API returned HTTP status '
                    . $httpStatus
                    . ' after '
                    . $attempt
                    . ' attempt(s).'
                );
            }

            $delay = min(
                2 ** ($attempt - 1),
                self::MAX_RETRY_DELAY_SECONDS,
            );

            if ($retryAfter !== null) {
                $delay = min(
                    $retryAfter,
                    self::MAX_RETRY_DELAY_SECONDS,
                );
            }

            if ($delay > 0) {
                sleep($delay);
            }
        }

        try {
            $data = json_decode(
                $response,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'OpenAlex returned invalid JSON.',
                0,
                $exception,
            );
        }

        if (
            !is_array($data)
            || !isset($data['results'])
            || !is_array($data['results'])
            || !isset($data['meta'])
            || !is_array($data['meta'])
        ) {
            throw new RuntimeException(
                'OpenAlex returned an unexpected response structure.'
            );
        }

        $works = [];

        foreach ($data['results'] as $record) {
            if (!is_array($record)) {
                continue;
            }

            $id = $record['id'] ?? null;

            if (
                !is_string($id)
                || trim($id) === ''
                || strlen($id) > 64
            ) {
                continue;
            }

            $doi = $record['doi'] ?? null;
            $title = $record['title'] ?? null;
            $year = $record['publication_year'] ?? null;
            $date = $record['publication_date'] ?? null;
            $type = $record['type'] ?? null;
            $citedBy = $record['cited_by_count'] ?? 0;
            $openAccess = $record['open_access'] ?? null;

            $publicationDate = is_string($date)
                && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $date) === 1
                    ? $date
                    : null;

            $works[] = [
                'openalex_id' => $id,
                'doi' => is_string($doi) ? $doi : null,
                'title' => is_string($title) ? $title : null,
                'publication_year' => is_numeric($year)
                    ? (int) $year
                    : null,
                'publication_date' => $publicationDate,
                'work_type' => is_string($type) && trim($type) !== ''
                    ? substr(trim($type), 0, 50)
                    : 'unknown',
                'cited_by_count' => is_numeric($citedBy)
                    ? max(0, (int) $citedBy)
                    : 0,
                'is_open_access' => is_array($openAccess)
                    && ($openAccess['is_oa'] ?? false) === true,
            ];
        }

        $nextCursor = $data['meta']['next_cursor'] ?? null;

        return [
            'works' => $works,
            'next_cursor' => is_string($nextCursor)
                && $nextCursor !== ''
                    ? $nextCursor
                    : null,
        ];
    }

    private function resolveApiKey(): string
    {
        $configuredKey = $_ENV['OPENALEX_API_KEY']
            ?? $_SERVER['OPENALEX_API_KEY']
            ?? getenv('OPENALEX_API_KEY');

        return is_string($configuredKey)
            ? trim($configuredKey)
            : '';
    }
}