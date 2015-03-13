<?php

namespace TreeHouse\IoBundle\Import\Feed;

use Symfony\Component\HttpFoundation\ParameterBag;
use TreeHouse\Feeder\Transport\FileTransport;
use TreeHouse\Feeder\Transport\FtpTransport;
use TreeHouse\Feeder\Transport\HttpTransport;
use TreeHouse\Feeder\Transport\TransportInterface;

class TransportFactory
{
    const TYPE_FILE = 'file';
    const TYPE_HTTP = 'http';
    const TYPE_FTP  = 'ftp';

    /**
     * @param array $config
     *
     * @throws \LogicException
     *
     * @return TransportInterface
     */
    public static function createTransportFromConfig(array $config)
    {
        $config = new ParameterBag($config);

        switch ($config->get('type')) {
            case self::TYPE_HTTP:
                $transport = static::createTransportFromUrl(
                    $config->get('url'),
                    $config->get('user'),
                    $config->get('pass')
                );

                return $transport;
            case self::TYPE_FTP:
                return FtpTransport::create(
                    $config->get('host'),
                    $config->get('user'),
                    $config->get('pass'),
                    $config->get('file'),
                    [
                        'mode' => $config->get('mode', 'binary'),
                        'pasv' => $config->get('pasv', true)
                    ]
                );
            case self::TYPE_FILE:
                return static::createTransportFromFile($config->get('file'));
            default:
                throw new \LogicException(sprintf('Unsupported transport type "%s"', $config->get('type')));
        }
    }

    /**
     * @param string $file
     *
     * @throws \RuntimeException When the file does not exist
     *
     * @return FileTransport
     */
    public static function createTransportFromFile($file)
    {
        if (false === file_exists($file)) {
            throw new \RuntimeException(sprintf('File "%s" does not exist', $file));
        }

        return FileTransport::create($file);
    }

    /**
     * @param string $url
     * @param string $user
     * @param string $pass
     *
     * @return HttpTransport
     */
    public static function createTransportFromUrl($url, $user = null, $pass = null)
    {
        return HttpTransport::create($url, $user, $pass);
    }

    /**
     * @param TransportInterface $transport
     *
     * @throws \LogicException
     *
     * @return array
     */
    public static function createConfigFromTransport(TransportInterface $transport)
    {
        if ($transport instanceof FileTransport) {
            return static::createConfigFromFile($transport->getConnection()['file']);
        }

        if ($transport instanceof HttpTransport) {
            return static::createConfigFromUrl($transport->getUrl(), $transport->getUser(), $transport->getPass());
        }

        if ($transport instanceof FtpTransport) {
            return [
                'type' => self::TYPE_FTP,
                'host' => $transport->getHost(),
                'user' => $transport->getUser(),
                'pass' => $transport->getPass(),
                'file' => $transport->getConnection()['file'],
                'mode' => $transport->getMode(),
                'pasv' => $transport->getPasv(),
            ];
        }

        throw new \LogicException(sprintf('Unable to create config for transport of type "%s"', get_class($transport)));
    }

    /**
     * @param string $file
     *
     * @return array
     */
    public static function createConfigFromFile($file)
    {
        return ['type' => self::TYPE_FILE, 'file' => $file];
    }

    /**
     * @param string $url
     * @param string $user
     * @param string $pass
     *
     * @return array
     */
    public static function createConfigFromUrl($url, $user = null, $pass = null)
    {
        $config = ['type' => self::TYPE_HTTP, 'url' => $url];

        if (null !== $user) {
            $config['user'] = $user;
        }

        if (null !== $pass) {
            $config['pass'] = $pass;
        }

        return $config;
    }
}
