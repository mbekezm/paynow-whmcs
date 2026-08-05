<?php

declare(strict_types=1);

namespace PaynowZW;

/**
 * Client for the Paynow Zimbabwe web checkout protocol: initiates
 * transactions, polls for authoritative status, and verifies inbound
 * status callbacks. All three operations share the same hash scheme
 * (see computeHash).
 */
final class PaynowClient
{
    private const INITIATE_URL = 'https://www.paynow.co.zw/interface/initiatetransaction';

    private string $integrationId;
    private string $integrationKey;
    private string $returnUrl;
    private string $resultUrl;

    public function __construct(
        string $integrationId,
        string $integrationKey,
        string $returnUrl,
        string $resultUrl
    ) {
        $integrationId = trim($integrationId);
        $integrationKey = strtolower(trim($integrationKey));
        $returnUrl = trim($returnUrl);
        $resultUrl = trim($resultUrl);

        if ($integrationId === '' || $integrationKey === '' || $returnUrl === '' || $resultUrl === '') {
            throw new \InvalidArgumentException('PaynowClient constructor arguments must not be empty');
        }

        $this->integrationId = $integrationId;
        $this->integrationKey = $integrationKey;
        $this->returnUrl = $returnUrl;
        $this->resultUrl = $resultUrl;
    }

    public function initiateTransaction(
        string $reference,
        float $amount,
        string $additionalInfo,
        string $authEmail
    ): InitResponse {
        // Field order is significant here: the hash is computed over these
        // values in exactly this order, per the Paynow protocol.
        $fields = [
            'resulturl' => $this->resultUrl,
            'returnurl' => $this->returnUrl,
            'reference' => $reference,
            'amount' => number_format($amount, 2, '.', ''),
            'id' => $this->integrationId,
            'additionalinfo' => $additionalInfo,
            'authemail' => $authEmail,
            'status' => 'Message',
        ];
        $fields['hash'] = $this->computeHash($fields);

        $response = $this->httpPost(self::INITIATE_URL, $fields);

        if (isset($response['hash']) && !$this->verifyHash($response)) {
            throw new \RuntimeException('Response hash verification failed');
        }

        return new InitResponse($response);
    }

    public function poll(string $pollUrl): StatusResponse
    {
        if (filter_var($pollUrl, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('Poll URL is not a valid URL');
        }

        $host = parse_url($pollUrl, PHP_URL_HOST);

        // Reject anything but Paynow's own host so a forged poll URL cannot
        // redirect this server-to-server status check elsewhere.
        if ($host !== 'www.paynow.co.zw' && $host !== 'paynow.co.zw') {
            throw new \InvalidArgumentException('Poll URL host is not a recognised Paynow host');
        }

        $response = $this->httpPost($pollUrl, []);

        if (!isset($response['hash']) || !$this->verifyHash($response)) {
            throw new \RuntimeException('Response hash verification failed');
        }

        return new StatusResponse($response);
    }

    public function verifyCallback(array $postFields): StatusResponse
    {
        if (!isset($postFields['hash']) || !$this->verifyHash($postFields)) {
            throw new \RuntimeException('Callback hash verification failed');
        }

        return new StatusResponse($postFields);
    }

    private function computeHash(array $fields): string
    {
        $concatenated = '';

        foreach ($fields as $key => $value) {
            if (strtolower((string) $key) === 'hash') {
                continue;
            }

            $concatenated .= (string) $value;
        }

        $concatenated .= $this->integrationKey;

        return strtoupper(hash('sha512', $concatenated));
    }

    private function verifyHash(array $fields): bool
    {
        $expected = $this->computeHash($fields);
        $received = (string) ($fields['hash'] ?? '');

        return hash_equals($expected, $received);
    }

    private function httpPost(string $url, array $fields): array
    {
        $curl = curl_init($url);

        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);

        $body = curl_exec($curl);
        $errno = curl_errno($curl);
        $error = curl_error($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($errno !== 0) {
            throw new \RuntimeException('Paynow request failed: ' . $error);
        }

        if ($statusCode < 200 || $statusCode > 299) {
            throw new \RuntimeException('Paynow request returned HTTP status ' . $statusCode);
        }

        $parsed = [];
        parse_str((string) $body, $parsed);

        if ($parsed === []) {
            throw new \RuntimeException('Paynow response could not be parsed');
        }

        return $parsed;
    }
}
