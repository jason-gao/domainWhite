<?php
/**
 * Desc:
 * Created by PhpStorm.
 * User: jasong
 * Date: 2018/09/28 16:47.
 */

namespace DomainWhiteSdk\HttpClients;

use DomainWhiteSdk\Exceptions\GuzzleHttpClientException;
use GuzzleHttp\Client;
use DomainWhiteSdk\Http\RawResponse;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class GuzzleHttpClient implements HttpClientInterface
{
    /**
     * @var \GuzzleHttp\Client The Guzzle client.
     */
    protected $guzzleClient;
    protected $logger;

    /**
     * @param \GuzzleHttp\Client|null The Guzzle client.
     * @param null|Client $guzzleClient
     * @param null|Logger $logger
     */
    public function __construct(Client $guzzleClient = null, $logger = null)
    {
        $this->guzzleClient = $guzzleClient ?: new Client();
        $this->logger       = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function send($url, $method, $body, array $headers, $timeOut, $otherOptions = [])
    {
        if ($body && !is_string($body)) {
            throw new GuzzleHttpClientException(GuzzleHttpClientException::MSG_BODY, GuzzleHttpClientException::CODE_BODY);
        }
        $options = [
            'headers'         => $headers,
            'timeout'         => $timeOut,
            'connect_timeout' => 20,
        ];

        if (isset($headers['Content-Type']) && 'application/json' == $headers['Content-Type']) {
            $options['json'] = json_decode($body, true);
        } elseif (isset($headers['Content-Type']) && 'application/x-www-form-urlencoded' == $headers['Content-Type']) {
            parse_str($body, $content);
            $options['form_params'] = $content;
        } else {
            $options['body'] = $body;
        }
        if ($this->logger) {
            $this->logger->Info("url:" . $url);
            $this->logger->Info("method:" . $method);
            $this->logger->Info("options:" . print_r($options, 1));
        }
        try {
            $rawResponse = $this->guzzleClient->request($method, $url, $options);
        } catch (RequestException $e) {
            $rawResponse = $e->getResponse();
            if (!$rawResponse instanceof ResponseInterface) {
                throw new GuzzleHttpClientException($e->getMessage(), $e->getCode());
            }
        }
        $rawHeaders     = $this->getHeadersAsString($rawResponse);
        $rawBody        = $rawResponse->getBody()->getContents();
        $httpStatusCode = $rawResponse->getStatusCode();
        if ($this->logger) {
            $this->logger->Info("rawHeaders:" . print_r($rawHeaders, 1));
            $this->logger->Info("httpStatusCode:" . $httpStatusCode);
            $this->logger->Info("rawBody:" . $rawBody);
        }

        return new RawResponse($rawHeaders, $rawBody, $httpStatusCode);

    }

    /**
     * Returns the Guzzle array of headers as a string.
     *
     * @param ResponseInterface $response The Guzzle response.
     *
     * @return string
     */
    public function getHeadersAsString(ResponseInterface $response)
    {
        $headers    = $response->getHeaders();
        $rawHeaders = [];
        foreach ($headers as $name => $values) {
            $rawHeaders[] = $name . ': ' . implode(', ', $values);
        }

        return implode("\r\n", $rawHeaders);
    }
}
