<?php
namespace ZakupkiBundle\Service;

use Guzzle\Service\Client;
use Guzzle\Common\Event as HttpEvent;

/**
 * Class ExportToZakupkiService
 * @package ZakupkiBundle\Service
 */
class ExportToZakupkiService
{
    /**
     * @var \Guzzle\Service\Client
     */
    private $client;

    /**
     * @var string
     */
    private $uploadUrl;

    /**
     * @param \Guzzle\Service\Client $client
     * @param string                 $uploadUrl
     */
    public function __construct(Client $client, $uploadUrl)
    {
        $this->client    = $client;
        $this->uploadUrl = $uploadUrl;
    }

    /**
     * Upload data to zakupki
     *
     * @return void
     */
    public function export()
    {
        $this->client->setDefaultOption('headers', [
            'Content-Type' => 'multipart/form-data',
        ]);

        $body = new GuzzleHttp\Post\PostBody();
        $body->forceMultipartUpload(true);
        $body->replaceFields(['foo' => 'bar']);
        $options = ['body' => $body];
        $request = $this->client->createRequest('POST', 'api/endpoint', $options);

//        $this->client->setDefaultOption('ssl.certificate_authority', false);

//        $this->client->getEventDispatcher()->addListener(
//            'request.error',
//            function (HttpEvent $event) {
//                /** @var \Guzzle\Http\Message\Response $response */
//                $response = $event['response'];
//
//                switch ($response->getStatusCode()) {
//                    case 200:
//                        dump($response);
//                        break;
//                    default:
//                        dump($response);
//                        $event->stopPropagation();
//                        break;
//                }
//            }
//        );

        $request = $this->client->get($this->uploadUrl, null, ['query' => [
            'login'         => $this->getConfig('login'),
            'password'      => $this->getConfig('password'),
            'client_id'     => $this->getConfig('client_id'),
            'client_secret' => $this->getConfig('client_secret'),
        ]]);

        $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYHOST, false);
        $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYPEER, false);

        /** @var \Guzzle\Http\Message\Response $response */
        $response = $request->send();

        dump($response);

//        if ($response->getStatusCode() != 200) {
//            return false;
//        }
    }
}
