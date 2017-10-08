<?php

namespace xmarcos\Silex;

use Exception;
use InvalidArgumentException;

abstract class ElasticsearchServiceProviderFactory
{
    protected $prefix;
    protected $config;

    /**
     * @param string $prefix Prefix name used to register the service in Silex.
     */
    public function __construct($prefix = 'elasticsearch', array $config = [])
    {
        if (empty($prefix) || false === is_string($prefix)) {
            throw new InvalidArgumentException(
                sprintf('$prefix must be a non-empty string, "%s" given', gettype($prefix))
            );
        }

        $this->prefix = $prefix;
        $this->config = $config;
    }

    /**
     * Returns an instance of a Silex Provider for the version of Silex currently installed. For
     * the time being, we'll support Silex 1.x and 2.x. As per <this> changelog, we know that
     * we have Silex 1.x if Interface Silex\ServiceProviderInterface exists. In that case, we
     * return an instance of ElasticsearchServiceProviderSilexV1, otherwise, we return ElasticsearchServiceProviderSilexV2.
     *
     * @return An instance of a Silex service provider for Elasticserach for the correct version of Silex framework
     */
    public static function createProvider($prefix = 'elasticsearch', array $config = [])
    {
        if (interface_exists('Silex\ServiceProviderInterface')) {
            return new ElasticsearchServiceProviderSilexV1($prefix, $config);
        } elseif (interface_exists('Silex\Api\BootableProviderInterface')) {
            return new ElasticsearchServiceProviderSilexV2($prefix, $config);
        }

        throw new Exception("silex/silex dependency is needed to execute this flow.");
    }
}
