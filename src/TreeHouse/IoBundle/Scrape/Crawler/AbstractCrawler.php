<?php

namespace TreeHouse\IoBundle\Scrape\Crawler;

use Faker\Provider\UserAgent;
use Symfony\Component\HttpFoundation\Response;
use TreeHouse\IoBundle\Scrape\Crawler\Client\ClientInterface;
use TreeHouse\IoBundle\Scrape\Crawler\Log\RequestLoggerInterface;
use TreeHouse\IoBundle\Scrape\Crawler\RateLimit\RateLimitInterface;
use TreeHouse\IoBundle\Scrape\Exception\RateLimitException;
use TreeHouse\IoBundle\Scrape\Exception\UnexpectedResponseException;

abstract class AbstractCrawler implements CrawlerInterface
{
    /**
     * The client executing the http requests
     *
     * @var ClientInterface
     */
    protected $client;

    /**
     * A logger where crawled
     *
     * @var RequestLoggerInterface
     */
    protected $logger;

    /**
     * The rate limit to apply when crawling
     *
     * @var RateLimitInterface
     */
    protected $rateLimit;

    /**
     * Whether to randomize user agents on requests
     *
     * @var boolean
     */
    protected $randomizeUserAgent = false;

    /**
     * The response of the last crawled page
     *
     * @var Response
     */
    protected $response;

    /**
     * The last crawled url. When following redirects, the url is updated with the effective url.
     *
     * @var string
     */
    protected $url;

    /**
     * @param ClientInterface        $client
     * @param RequestLoggerInterface $logger
     * @param RateLimitInterface     $ratelimit
     * @param boolean                $randomizeUserAgent
     */
    public function __construct(ClientInterface $client, RequestLoggerInterface $logger, RateLimitInterface $ratelimit, $randomizeUserAgent = false)
    {
        $this->client             = $client;
        $this->logger             = $logger;
        $this->rateLimit          = $ratelimit;
        $this->randomizeUserAgent = $randomizeUserAgent;
    }

    /**
     * @inheritdoc
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @inheritdoc
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @inheritdoc
     */
    public function getRateLimit()
    {
        return $this->rateLimit;
    }

    /**
     * @inheritdoc
     */
    public function getLastResponse()
    {
        return $this->response;
    }

    /**
     * @inheritdoc
     */
    public function getLastUrl()
    {
        return $this->url;
    }

    /**
     * @inheritdoc
     */
    public function crawl($url)
    {
        $this->response = null;

        if ($this->rateLimit->limitReached()) {
            throw new RateLimitException(
                $url,
                sprintf('Reached the rate limit of %s', $this->rateLimit->getLimit()),
                $this->rateLimit->getRetryDate()
            );
        }

        $this->logger->logRequest($url, new \DateTime());

        list ($this->url, $this->response) = $this->client->fetch($url, $this->getUserAgent($url));

        if ($this->response->getStatusCode() === Response::HTTP_TOO_MANY_REQUESTS) {
            throw new RateLimitException(
                $url,
                sprintf('Server replied with response %d (Too Many Requests)', Response::HTTP_TOO_MANY_REQUESTS),
                $this->getRetryAfterDate()
            );
        }

        if ($this->response->getStatusCode() !== Response::HTTP_OK) {
            throw new UnexpectedResponseException($url, $this->response);
        }

        return $this->response->getContent();
    }

    /**
     * @inheritdoc
     */
    abstract public function getNextUrls();

    /**
     * @param string $url
     *
     * @return string|null
     */
    protected function getUserAgent($url)
    {
        if (!$this->randomizeUserAgent) {
            return null;
        }

        return UserAgent::userAgent();
    }

    /**
     * @return \DateTime
     */
    protected function getRetryAfterDate()
    {
        if (null === $date = $this->response->headers->get('Retry-After')) {
            return null;
        }

        if (is_numeric($date)) {
            return new \DateTime(sprintf('+%d seconds', $date));
        } else {
            if (false !== $date = \DateTime::createFromFormat(DATE_RFC2822, $date)) {
                return $date;
            }
        }

        return null;
    }
}
