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

class YunDunGuzzleHttpClient implements HttpClientInterface
{
    /**
     * @var \GuzzleHttp\Client The Guzzle client.
     */
    protected $guzzleClient;

    /**
     * @param \GuzzleHttp\Client|null The Guzzle client.
     * @param null|Client $guzzleClient
     */
    public function __construct(Client $guzzleClient = null)
    {
        $this->guzzleClient = $guzzleClient ?: new Client();
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
