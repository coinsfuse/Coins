<?php

namespace CoinsFuse\Coins;

use GuzzleHttp\Client;

class CoinBackend
{
    // Configuration options

    /**
     * The username to use
     * @var string
     */
    protected $username;

    protected $password;
    protected $host;
    protected $port;
    protected $proto;
    protected $certificate;
    protected $verifySSL;
    protected $useTestNet;

    /**
     * URL path.
     *
     * @var string
     */
    protected $path = '/';

    // Information and debugging
    public $status;
    public $error;
    public $raw_response;
    public $response;
    private $id = 0;

    public function __construct(string $username, string $password, string $host = 'localhost', int $port, int $testNetPort,
    $useTestNet = false)
    {
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
        $this->testNetPort = $testNetPort;
        $this->useTestNet = $useTestNet;
        $this->url = $url;

        if (!$this->useTestNet) {
            $this->port = $port;
        } else {
            $this->port = $testNetPort;
        }

        //set defaults
        $this->proto = 'http';
        $this->certificate = null;
        $this->verifySSL = false;
    }

    public function setSSL($certificate = null)
    {
        // force HTTPS
        $this->proto = 'https';
        $this->certificate = $certificate;
    }

    public function setTestNet($useTestNet) {
        $this->useTestNet = $useTestNet;
    }

     /**
     * Sets wallet for multi-wallet rpc request.
     *
     * @param string $name
     *
     * @return self
     */
    public function wallet(string $name) : self
    {
        $this->path = "/wallet/$name";
        return $this;
    }

    public function __call($method, $params) {
        $this->status = null;
        $this->error = null;
        $this->raw_response = null;
        $this->response = null;

        // If no parameters are passed, this will be an empty array
        $params = array_values($params);

        // The ID should be unique for each call
        $this->id++;

        // Build the request, it's ok that params might have an empty array
        $request = json_encode([
            'method' => $method,
            'params' => $params,
            'id' => $this->id
        ]);

        // create the instance of GuzzleHttp Client
        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => "{$this->proto}://{$this->host}:{$this->port}",

            // default timeout
            'timeout' => 2.0
        ]);

        //todo: HTTPS implementation
        $options = ['auth' => [$this->username, $this->password], 'json' => $request];

        if ($this->proto == 'https')
        {
            // if certificate was specified, we set the path for it
            if (!empty($this->certificate)) {
                $options['verify'] = $this->certificate;
            } else {
                // if not, we can't veify the certificate
                $options['verify'] = false;
            }
        }

        $this->raw_response = $client->post("{$this->path}", $options);

        // get the status
        $this->status = $this->raw_response->getStatusCode();

        // decode the response
        $this->response = json_decode($this->raw_response, TRUE);

        if ($this->response['error']) {
            // if bitcoind returned an error, put that in $this->error
            $this->error = $this->response['error']['message'];
        } elseif($this->status != 200) {
            // if bitcoind didn't return a nice error message, return the reason phrase
            return $this->raw_response->getReasonPhrase();
        }

        if ($this->error) {
            return false;
        }

        return $this->response['result'];
    }
}