<?php

namespace TreeHouse\IoBundle\Test;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class TestCase extends WebTestCase
{
    /**
     * Shortcut to the Container's get method.
     *
     * @param string $serviceId
     *
     * @return object
     */
    public function get($serviceId)
    {
        return $this->getContainer()->get($serviceId);
    }

    /**
     * @param array $options Kernel options
     *
     * @return ContainerInterface
     */
    public static function getContainer(array $options = [])
    {
        if (null === static::$kernel) {
            static::$kernel = static::createKernel($options);
        }

        static::$kernel->boot();

        return static::$kernel->getContainer();
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->getContainer()->get('doctrine')->getManager();
    }

    /**
     * Returns fixtures of a type.
     *
     * @param string $type
     *
     * @return array
     */
    public function getFixtures($type)
    {
        $loader = new Yaml();

        return $loader->parse(sprintf(__DIR__ . '/../Resources/fixtures/%s.yml', $type));
    }
}
