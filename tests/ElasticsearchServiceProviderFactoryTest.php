<?php

namespace xmarcos\Silex;

use ReflectionClass;
use Silex\Application;
use Elasticsearch\Client;
use PHPUnit_Framework_TestCase;

class ElasticsearchServiceProviderFactoryTest extends PHPUnit_Framework_TestCase
{
    public function testRegisterWithoutPrefix()
    {
        $app = new Application();
        $app->register(ElasticsearchServiceProviderFactory::createProvider());

        $this->assertTrue($app['elasticsearch'] instanceof Client);
    }

    public function testRegisterWithPrefix()
    {
        $app = new Application();
        $app->register(ElasticsearchServiceProviderFactory::createProvider('es'));

        $this->assertTrue($app['es'] instanceof Client);
    }

    public function testRegisterWithParams()
    {
        $app = new Application();
        $app->register(ElasticsearchServiceProviderFactory::createProvider(
            'elasticsearch',
            [
                'hosts' => 'myhost',
            ]
        ));

        $reflection = new ReflectionClass($app['elasticsearch']);

        $params = $reflection->getProperty('transport');
        $transport = $params->getValue($app['elasticsearch']);

        $this->assertEquals('http', $transport->getConnection()->getTransportSchema());
        $this->assertEquals('myhost:9200', $transport->getConnection()->getHost());
    }

    public function testRegisterWithParamsForAWS()
    {
        $app = new Application();
        $app->register(ElasticsearchServiceProviderFactory::createProvider(
            'elasticsearch',
            [
                'aws' => [ 'region' => 'foo' ],
                'hosts' => 'myhost',
                'scheme' => 'https',
            ]
        ));

        $reflection = new ReflectionClass($app['elasticsearch']);

        $params = $reflection->getProperty('transport');
        $transport = $params->getValue($app['elasticsearch']);

        $this->assertEquals('http', $transport->getConnection()->getTransportSchema());
        $this->assertEquals('localhost:9200', $transport->getConnection()->getHost());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testRegisterException()
    {
        $app = new Application();
        $app->register(ElasticsearchServiceProviderFactory::createProvider(mt_rand()));
    }
}
