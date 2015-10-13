<?php

namespace TreeHouse\IoBundle\Scrape\Crawler;

use Faker\Provider\UserAgent;
use Psr\Http\Message\ResponseInterface;
use TreeHouse\IoBundle\Scrape\Crawler\Client\ClientInterface;
use TreeHouse\IoBundle\Scrape\Crawler\Log\RequestLoggerInterface;
use TreeHouse\IoBundle\Scrape\Crawler\RateLimit\RateLimitInterface;
use TreeHouse\IoBundle\Scrape\Exception\NotFoundException;
use TreeHouse\IoBundle\Scrape\Exception\RateLimitException;
use TreeHouse\IoBundle\Scrape\Exception\UnexpectedResponseException;

abstract class AbstractCrawler implements CrawlerInterface
{
    /**
     * The client executing the http requests.
     *
     * @var ClientInterface
     */
    protected $client;

    /**
     * A logger that remembers crawled requests.
     *
     * @var RequestLoggerInterface
     */
    protected $logger;

    /**
     * The rate limit to apply when crawling.
     *
     * @var RateLimitInterface
     */
    protected $rateLimit;

    /**
     * Whether to randomize user agents on requests.
     *
     * @var bool
     */
    protected $randomizeUserAgent = false;

    /**
     * The response of the last crawled page.
     *
     * @var ResponseInterface
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
     * @param bool                   $randomizeUserAgent
     */
    public function __construct(ClientInterface $client, RequestLoggerInterface $logger, RateLimitInterface $ratelimit, $randomizeUserAgent = false)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->rateLimit = $ratelimit;
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
        if (!$this->response) {
            throw new \RuntimeException('Crawler has yet to make a request');
        }

        return $this->response;
    }

    /**
     * @inheritdoc
     */
    public function getLastUrl()
    {
        if (!$this->url) {
            throw new \RuntimeException('Crawler has yet to make a request');
        }

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

        list($this->url, $this->response) = $this->client->fetch($url, $this->getUserAgent($url));

        if ($this->response->getStatusCode() === 429) {
            throw new RateLimitException(
                $url,
                sprintf('Server replied with response %d (Too Many Requests)', 429),
                $this->getRetryAfterDate()
            );
        }

        if ($this->islastResponseNotFound()) {
            throw new NotFoundException($url, $this->response);
        }

        if (!$this->islastResponseOk()) {
            throw new UnexpectedResponseException($url, $this->response);
        }

        $body = $this->response->getBody();
        $contents = $body->getContents();

        // rewind stream, in case we need to use the last response
        if ($body->isSeekable()) {
            $body->rewind();
        }

        return $contents;
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
        if (null === $date = $this->response->getHeaderLine('Retry-After')) {
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

    /**
     * Returns whether the last response is a 200 OK.
     *
     * @return bool
     */
    protected function islastResponseOk()
    {
        return $this->getLastResponse()->getStatusCode() === 200;
    }

    /**
     * Returns whether the last response is not found. This includes checks for
     * soft 404's, redirects from what should be 404/410 responses to 200 OK
     * pages, and other tricks like that.
     *
     * In other words: returns true if the last response is not the actual page
     * that was requested.
     *
     * @return bool
     */
    protected function islastResponseNotFound()
    {
        return in_array($this->getLastResponse()->getStatusCode(), [404, 410]);
    }
}
