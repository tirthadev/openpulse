<?php

declare(strict_types=1);

namespace OpenPulse\Services;

use OpenPulse\Contracts\CountryProviderInterface;
use RuntimeException;
use JsonException;

class WorldBankCountryProvider implements CountryProviderInterface
{
    private const API_URL =
        'https://api.worldbank.org/v2/country?format=json&per_page=300';

    public function fetch(): array
    {
        $curl = curl_init(self::API_URL);

        if ($curl === false) {
            throw new RuntimeException(
                'Failed to initialize cURL.'
            );
        }

        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $httpStatus = (int) curl_getinfo(
            $curl,
            CURLINFO_HTTP_CODE
        );
        $curlError = curl_error($curl);

        if ($response === false) {
            throw new RuntimeException(
                'Country API request failed: ' . $curlError
            );
        }

        if ($httpStatus < 200 || $httpStatus >= 300) {
            throw new RuntimeException(
                'Country API returned HTTP status ' . $httpStatus
            );
        }

        try {
            $data = json_decode(
                $response,
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $exception) {
            throw new RuntimeException(
                'Country API returned invalid JSON.',
                0,
                $exception
            );
        }

        if (
            !is_array($data)
            || !isset($data[0], $data[1])
            || !is_array($data[0])
            || !is_array($data[1])
            || !isset($data[0]['total'])
            || !is_numeric($data[0]['total'])
        ) {
            throw new RuntimeException(
                'Invalid World Bank API response structure.'
            );
        }

        $countries = [];

        foreach ($data[1] as $country) {
            if (!is_array($country)) {
                continue;
            }

            $region = $country['region'] ?? null;

            if (
                !is_array($region)
                || ($region['id'] ?? '') === 'NA'
            ) {
                continue;
            }

            $name = $country['name'] ?? null;
            $code = $country['iso2Code'] ?? null;
            $capital = $country['capitalCity'] ?? '';
            $regionName = $region['value'] ?? '';

            if (
                !is_string($name)
                || trim($name) === ''
                || !is_string($code)
                || !preg_match('/^[A-Z]{2}$/', $code)
                || !is_string($capital)
                || !is_string($regionName)
            ) {
                continue;
            }

            $countries[] = [
                'name' => $name,
                'code' => $code,
                'capital' => $capital,
                'region' => trim($regionName),
            ];
        }

        return $countries;
    }
}
