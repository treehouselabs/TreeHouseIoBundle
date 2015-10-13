<?php

namespace TreeHouse\IoBundle\Tests\Scrape;

use TreeHouse\IoBundle\Entity\Scraper;
use TreeHouse\IoBundle\Scrape\ScrapedItemBag;

class ScrapedItemBagTest extends \PHPUnit_Framework_TestCase
{
    public function testItem()
    {
        $scraper = new Scraper();
        $url = 'http://example.org';
        $html = <<<HTML
<!doctype html>
<html>
<body>Ima document</body>
</html>
HTML;

        $item = new ScrapedItemBag($scraper, $url, $html);

        $this->assertInstanceOf(ScrapedItemBag::class, $item);
        $this->assertEquals($scraper, $item->getScraper());
        $this->assertEquals($url, $item->getOriginalUrl());
        $this->assertEquals($html, $item->getOriginalData());
        $this->assertNotEmpty($item->getOriginalId());
    }
}
