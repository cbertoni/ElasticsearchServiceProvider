<?php

namespace xmarcos\Silex;

use Aws\Credentials\CredentialProvider;
use Aws\DoctrineCacheAdapter;
use Aws\Handler\GuzzleV6\GuzzleHandler;
use Aws\Signature\SignatureV4;
use Closure;
use Doctrine\Common\Cache\ApcuCache;
use Elasticsearch\ClientBuilder;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Ring\Future\CompletedFutureArray;
use GuzzleHttp\TransferStats;
use Exception;

class ElasticsearchClientAWSSignatureFactory
{
    public static function createClient(array $config = [])
    {
        if (empty($config['hosts'])) {
            throw new Exception("'hosts' configuration is needed!");
        }

        if (!is_array($config['hosts'])) {
            $config['hosts'] = [$config['hosts']];
        }

        if (empty($config['scheme'])) {
            throw new Exception("'scheme' configuration is needed!");
        }

        if (empty($config['aws']['region'])) {
            throw new Exception("'aws/region' configuration is needed!");
        }

        $credential_provider = CredentialProvider::cache(
            CredentialProvider::defaultProvider(),
            new DoctrineCacheAdapter(new ApcuCache())
        );

        $signer = new SignatureV4(
            'es',
            $config['aws']['region']
        );

        return self::buildClient(
            $credential_provider,
            $signer,
            $config
        );
    }

    public static function buildClient(
        Closure $credential_provider,
        SignatureV4 $signer,
        array $config
    ) {
        $psr7_handler = new GuzzleHandler();

        $handler = function (
            array $request
        ) use (
            $credential_provider,
            $signer,
            $config,
            $psr7_handler
        ) {
            // translate the URI
            $psr7_uri = (new Uri($request['uri']))
                ->withScheme($config['scheme'])
                ->withHost($config['host']);

            // clear host from headers
            unset($request['headers']['host']);

            // build a signed request
            $psr7_request = $signer->signRequest(
                new Request(
                    $request['http_method'],
                    $psr7_uri,
                    $request['headers'],
                    $request['body']
                ),
                call_user_func($credential_provider)->wait()
            );

            // promise of transfer_stats
            $stats_promise = new Promise();

            // send the request & resolve stats_promise when on_stats is invoked
            $psr7_response = $psr7_handler($psr7_request, [
                'on_stats' => function (TransferStats $stats) use ($stats_promise) {
                    $stats_promise->resolve(
                        $stats->getHandlerStats()
                    );
                },
            ])->otherwise(
                function (array $error) {
                    if (!empty($error['response'])) {
                        return $error['response'];
                    } else {
                        throw $error['exception'];
                    }
                }
            )->wait();

            // wait for the stats, when it's done, response will be also available
            return $stats_promise->then(
                function (array $stats) use ($psr7_response, $psr7_uri) {
                    // returns a phpRing response
                    return new CompletedFutureArray([
                        'status' => $psr7_response->getStatusCode(),
                        'headers' => $psr7_response->getHeaders(),
                        'body' => $psr7_response->getBody()->detach(),
                        'transfer_stats' => $stats,
                        'effective_url' => (string) $psr7_uri,
                    ]);
                }
            )->wait();
        };

        return ClientBuilder::create()
            ->setHandler($handler)
            /*
             * [TD] Allow bad versions of json-ext due the imposibility to satisfy
             * Elasticsearch Client PHP version requirements which is PHP >= 5.6
             * For more info please refer to https://github.com/elastic/elasticsearch-php/issues/534
             */
            ->allowBadJSONSerialization()
            ->build();
    }
}
