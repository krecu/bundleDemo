<?php
namespace FreshDocBundle\Transport;

use Guzzle\Common\Event;
use Guzzle\Service\Client;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * Class FreshDocTransport
 * @package FreshDocBundle\Transport
 */
class FreshDocTransport
{
    /** @var array Config data */
    protected $config = [];

    /** @var array access_token, refresh_token and authorize_token */
    public $tokens = [];

    /** @var GuzzleClient  */
    public $client;

    /**
     * FreshDocTransport constructor.
     * @param $params
     * @param $guzzleClient
     */
    public function __construct(array $params, Client $guzzleClient)
    {
        $this->client = $guzzleClient;

        $this->config = [
            'login' => $params['login'],
            'password' => $params['password'],
            'client_id' =>  $params['client_id'],
            'client_secret' => $params['client_secret'],
        ];

        $this->client->setDefaultOption('headers', [
            'Content-Type' => 'application/json',
        ]);

        $this->client->setDefaultOption('ssl.certificate_authority', false);

        // вешаем лиснер на ошибки
        $this->client->getEventDispatcher()->addListener(
            'request.error',
            function (Event $event) {
                /** @var \Guzzle\Http\Message\Response $response */
                $response = $event['response'];

                switch ($response->getStatusCode()) {
                    case 200:
                        /* Do nothing */
                        break;

                    case 403:
                    case 401:
                        // @todo: пока по непонятно причине при отправке сразу же второго запроса
                        // приложение падает, потомучто неможет авторизоваться, почему непонятно но при
                        // перезапуске авторизируеться... это странно но пока что оставляю именно так
                        $this->refreshToken();
                        /** @var \Guzzle\Http\Message\Request $request */
                        $request = clone $event['request'];
                        $originUrl = $request->getUrl();
                        $parsedUrl = parse_url($originUrl);
                        if (!empty($parsedUrl['query'])) {
                            parse_str($parsedUrl['query'], $queryParams);
                            if (!empty($queryParams) && isset($queryParams['access_token'])) {
                                $request->setUrl(str_replace($queryParams['access_token'], $this->tokens['access_token'], $originUrl));
                            }
                        }
                        $event['response'] = $request->send();
                        $event->stopPropagation();
                        break;

                    default:
                        $event->stopPropagation();
                        break;
                }
            }
        );

        $this->authorize();
    }

    /**
     * @param string $key config key
     * @return mixed Config value
     * @throws \Symfony\Component\Validator\Exception\MissingOptionsException
     */
    public function getConfig($key)
    {
        if (!isset($this->config[$key])) {
            throw new MissingOptionsException("Missing parameter '{$key}'", []);
        }

        return $this->config[$key];
    }

    /**
     * Freshdoc OAuth2 authorization
     *
     * It works in background with setting access token to default query string
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var \Guzzle\Http\Message\Request $request */
        $request = $this->client->get('oauth', null, ['query' => [
            'login'         => $this->getConfig('login'),
            'password'      => $this->getConfig('password'),
            'client_id'     => $this->getConfig('client_id'),
            'client_secret' => $this->getConfig('client_secret'),
        ]]);

        $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYHOST, false);
        $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYPEER, false);

        /** @var \Guzzle\Http\Message\Response $response */
        $response = $request->send();

        if ($response->getStatusCode() != 200) {
            return false;
        }

        $this->tokens = $response->json();

        /* Setting access_token query parameter on all future requests */
        $this->client->setDefaultOption('query', [
            'access_token' => $this->tokens['access_token'],
        ]);

        return true;
    }


    /**
     * Обновление токена при разрыве или неудачной попытки
     *
     * @return bool
     */
    private function refreshToken()
    {
        if (!isset($this->tokens['refresh_token']) || empty($this->tokens['refresh_token'])) {
            return $this->authorize();
        }

        /** @var \Guzzle\Http\Message\Request $request */
        $request = $this->client->get('oauth/refresh', null, ['query' => [
            'refresh_token' => $this->tokens['refresh_token'],
            'client_id' => $this->getConfig('client_id'),
            'client_secret' => $this->getConfig('client_secret')
        ]]);

        $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYHOST, false);
        $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYPEER, false);

        $this->client->setDefaultOption('query', []);
        /** @var \Guzzle\Http\Message\Response $response */
        $response = $request->send();

        if ($response->getStatusCode() != 200) {
            return false;
        }

        $this->tokens = $response->json();

        /* Setting access_token query parameter on all future requests */
        $this->client->setDefaultOption('query', [
            'access_token' => $this->tokens['access_token']
        ]);

        return true;
    }

    /**
     * Получаем access_token для приложения
     *
     * @return bool success
     */
    public function getAuthorizeToken()
    {
        if (!isset($this->tokens['access_token']) || empty($this->tokens['access_token'])) {
            if (!$this->authorize()) {
                return false;
            }
        }

        /** @var \Guzzle\Http\Message\Request $request */
        $request = $this->client->get('oauth/authorize');

        $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYHOST, false);
        $request->getCurlOptions()->set(CURLOPT_SSL_VERIFYPEER, false);

        /** @var \Guzzle\Http\Message\Response $response */
        $response = $request->send();

        if ($response->getStatusCode() != 200) {
            return false;
        }

        $json = $response->json();

        if (!empty($json) && isset($json['authorize_token'])) {
            $this->tokens['authorize_token'] = $json['authorize_token'];

            return true;
        }

        return false;
    }
}