<?php

namespace Scriptotek\GoogleBooks;

use GuzzleHttp\Client;

class GoogleBooks
{
    /**
     * @var string
     */
    protected $baseUri = 'https://www.googleapis.com/books/v1/';

    /**
     * @var integer (Number of results to retrieve per batch, between 1 and 40)
     */
    protected $batchSize = 40;

    /**
     * @var Client
     */
    protected $http;

    /**
     * @var Volumes
     */
    public $volumes;

    /**
     * @var Bookshelves
     */
    public $bookshelves;

    public function __construct($options = [])
    {
        $this->http = new Client([
            'base_uri' => $this->baseUri,
            'handler' => isset($options['handler']) ? $options['handler'] : null,
        ]);

        $this->volumes = new Volumes($this);
        $this->bookshelves = new Bookshelves($this);

        $this->batchSize = isset($options['batchSize']) ? $options['batchSize'] : 40;
    }

    protected function raw($endpoint, $params = [], $method='GET')
    {
        $response = $this->http->request($method, $endpoint, [
            'query' => $params,
        ]);

        return json_decode($response->getBody());
    }

    public function getItem($path)
    {
        return $this->raw($path);
    }

    public function listItems($endpoint, $params = [])
    {
        $params['maxResults'] = $this->batchSize;

        $i = 0;
        while (true) {
            $n = $i % $this->batchSize;
            if ($n == 0) {
                $params['startIndex'] = $i;
                $response = $this->raw($endpoint, $params);
            }
            if (isset($response->totalItems) && $i >= $response->totalItems) {
                return;
            }
            if (!isset($response->items[$n])) {
                return;
            }
            yield $response->items[$n];
            $i++;
        }
    }

}
