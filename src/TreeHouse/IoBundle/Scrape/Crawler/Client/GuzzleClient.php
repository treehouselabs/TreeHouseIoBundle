<?php

namespace TreeHouse\IoBundle\Scrape\Crawler\Client;

use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TreeHouse\IoBundle\Scrape\Exception\CrawlException;

class GuzzleClient implements ClientInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var GuzzleClientInterface
     */
    protected $guzzle;

    /**
     * @var string[]
     */
    protected $effectiveUrls = [];

    /**
     * @param GuzzleClientInterface $guzzle
     */
    public function __construct(GuzzleClientInterface $guzzle)
    {
        $this->guzzle = $guzzle;
    }

    /**
     * @inheritdoc
     */
    public function fetch($uri, $userAgent = null)
    {
        $this->setEffectiveUri($uri, $uri);

        try {
            $response = $this->guzzle->request('GET', $uri, $this->getRequestOptions($userAgent));

            return $this->transformResponse($uri, $response);
        } catch (RequestException $e) {
            $this->logger->error(
                sprintf('Error crawling: %s', $e->getMessage()),
                ['url' => (string) $e->getRequest()->getUri()]
            );

            if ($response = $e->getResponse()) {
                return $this->transformResponse($uri, $response);
            }

            throw new CrawlException($uri, sprintf('Error crawling url: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * @param string $uri
     * @param string $effectiveUri
     */
    public function setEffectiveUri($uri, $effectiveUri)
    {
        $this->effectiveUrls[$uri] = $effectiveUri;
    }

    /**
     * @param string $userAgent
     *
     * @return array
     */
    protected function getRequestOptions($userAgent = null)
    {
        $options = [
            RequestOptions::ON_STATS => function (TransferStats $stats) {
                $this->setEffectiveUri($stats->getRequest(), $stats->getEffectiveUri());
            },
        ];

        if ($userAgent) {
            $options[RequestOptions::HEADERS]['User-Agent'] = $userAgent;
        }

        return $options;
    }

    /**
     * @param string            $uri
     * @param ResponseInterface $response
     *
     * @return array
     */
    protected function transformResponse($uri, ResponseInterface $response)
    {
        return [
            isset($this->effectiveUrls[$uri]) ? $this->effectiveUrls[$uri] : $uri,
            $response,
        ];
    }
}
