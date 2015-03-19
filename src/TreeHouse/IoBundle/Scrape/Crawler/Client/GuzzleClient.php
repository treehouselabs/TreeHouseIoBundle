<?php

namespace TreeHouse\IoBundle\Scrape\Crawler\Client;

use GuzzleHttp\ClientInterface as GuzzleClientInterface;
use GuzzleHttp\Event\BeforeEvent;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Message\ResponseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use TreeHouse\IoBundle\Scrape\Exception\CrawlException;

class GuzzleClient implements ClientInterface, LoggerAwareInterface
{
    /**
     * @var GuzzleClientInterface
     */
    protected $guzzle;

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
    public function setLogger(LoggerInterface $logger)
    {
        $this->guzzle->getEmitter()->on('before', function (BeforeEvent $e) use ($logger) {
            $logger->debug(sprintf('Crawling %s', $e->getRequest()->getUrl()));
        });

        $this->guzzle->getEmitter()->on('error', function (ErrorEvent $e) use ($logger) {
            $logger->debug(
                sprintf('Error crawling: %s', $e->getException()->getMessage()),
                ['url' => $e->getRequest()->getUrl()]
            );
        });
    }

    /**
     * @inheritdoc
     */
    public function fetch($url, $userAgent = null)
    {
        try {
            $response = $this->guzzle->get($url, $this->getRequestOptions($userAgent));

            return $this->transformResponse($response);
        } catch (RequestException $e) {
            if ($response = $e->getResponse()) {
                return $this->transformResponse($response);
            }

            throw new CrawlException($url, sprintf('Error crawling url: %s', $e->getMessage()), $e->getCode(), $e);
        }
    }

    /**
     * @param string $userAgent
     *
     * @return array
     */
    protected function getRequestOptions($userAgent = null)
    {
        $options = [];

        if ($userAgent) {
            $options['headers']['User-Agent'] = $userAgent;
        }

        return $options;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return array
     */
    protected function transformResponse(ResponseInterface $response)
    {
        return [
            $response->getEffectiveUrl(),
            new Response($response->getBody(), $response->getStatusCode(), $response->getHeaders())
        ];
    }
}
