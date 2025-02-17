<?php

namespace Linker\Api\Client;

use GuzzleHttp\Client;
use JMS\Serializer\SerializerInterface;
use GuzzleHttp\Exception\BadResponseException;
use Linker\Api\LinkerClientInterface;
use Linker\Api\Model\Order;
use Linker\Api\Model\OrderInterface;
use Linker\Api\Model\OrderList;
use Linker\Api\Model\StockList;
use Linker\Api\Model\TrackingNumber;
use Linker\Api\Model\SupplierOrderInterface;
use Linker\Api\Model\SupplierOrder;
use Linker\Api\Model\SupplierOrderList;

class HttpApiClient implements LinkerClientInterface
{
    /** @var Client */
    protected $client;
    /** @var string */
    protected $endpoint;
    /** @var string */
    protected $apiKey;
    /** @var SerializerInterface */
    protected $serializer;
    /** @var array */
    protected $headers;

    /**
     * @param Client              $client
     * @param SerializerInterface $serializer
     * @param string              $endpoint
     * @param string              $apiKey
     */
    public function __construct(
        Client $client,
        SerializerInterface $serializer,
        $endpoint,
        $apiKey
    )
    {
        $this->client     = $client;
        $this->endpoint   = $endpoint;
        $this->serializer = $serializer;
        $this->apiKey     = $apiKey;
        $this->headers    = [
            'Accept'       => 'application/json',
            'Content-Type' => 'application/json',
            'apikey'       => $apiKey,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getOrders($limit = 10, $offset = 0, array $filters = [], $sortColumn = 'created_at', $sortDir = 'ASC')
    {
        if ($limit < 0) {
            $limit = 10;
        }

        $query = '';
        foreach ($filters as $key => $val) {
            $query .= '&filters[' . $key . ']=' . $val;
        }

        $sortDir  = ($sortDir == 'ASC') ? 'ASC' : 'DESC';
        $endpoint = $this->endpoint . '/orders?limit=' . $limit .
            '&offset=' . $offset . '&sortCol=' . $sortColumn . '&sortDir=' . $sortDir;

        $response = $this->client->request('GET', $endpoint . '&apikey=' . $this->apiKey);

        $body = $response->getBody();
        return $this->serializer->deserialize($body, OrderList::class, 'json');
    }

    /**
     * {@inheritDoc}
     */
    public function getStocks()
    {
        $endpoint = $this->endpoint . '/stocks?';

        $response = $this->client->request('GET', $endpoint . '&apikey=' . $this->apiKey);

        $body = $response->getBody();
        return $this->serializer->deserialize($body, StockList::class, 'json');
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder($id)
    {
        $endpoint = $this->endpoint . '/orders/' . $id;
        $options  = [
            'headers' => $this->headers
        ];
        $response = $this->client->request('GET', $endpoint, $options);

        $body = $response->getBody();
        return $this->serializer->deserialize($body, Order::class, 'json');

    }

    /**
     * {@inheritDoc}
     */
    public function createOrder(OrderInterface $order)
    {
        $order->setOrigin('LinkerAPI');
        $endpoint = $this->endpoint . '/orders?apikey=' . $this->apiKey;
        $content  = $this->serializer->serialize($order, 'json');
        $options  = [
            'headers' => $this->headers,
            'body'    => $content
        ];
        try {
            $response = $this->client->request('POST', $endpoint, $options);
            return $this->serializer->deserialize((string)$response->getBody(), Order::class, 'json');
        } catch (BadResponseException $e) {
            throw new ApiException($e->getResponse()->getReasonPhrase(), $e->getResponse()->getStatusCode(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function updateOrder($id, OrderInterface $order)
    {
        $endpoint = $this->endpoint . '/orders/' . $id . '?apikey=' . $this->apiKey;
        $content  = $this->serializer->serialize($order, 'json');
        $options  = [
            'headers' => $this->headers,
            'body'    => $content
        ];

        try {
            return $response = $this->client->request('POST', $endpoint, $options);

        } catch (BadResponseException $e) {
            throw new ApiException($e->getResponse()->getReasonPhrase(), $e->getResponse()->getStatusCode(), $e);
        }
    }


    /**
     * @param $id
     * @param TrackingNumber $trackingNumber
     * @return OrderInterface
     * @throws ApiException
     */
    public function setTrackingNumber($id, TrackingNumber $trackingNumber)
    {
        $endpoint = $this->endpoint . '/orders/' . $id . '/trackingnumber?apikey=' . $this->apiKey;
        $content  = $this->serializer->serialize($trackingNumber, 'json');
        $options  = [
            'headers' => $this->headers,
            'body'    => $content
        ];
        try {
            return $this->client->request('PUT', $endpoint, $options);
        } catch (BadResponseException $e) {
            throw new ApiException($e->getResponse()->getReasonPhrase(), $e->getResponse()->getStatusCode(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getSupplierOrders($limit = 10, $offset = 0, array $filters = [], $sortColumn = 'created_at', $sortDir = 'ASC')
    {
        if ($limit < 0) {
            $limit = 10;
        }

        $query = '';
        foreach ($filters as $key => $val) {
            $query .= '&filters[' . $key . ']=' . $val;
        }

        $sortDir  = ($sortDir == 'ASC') ? 'ASC' : 'DESC';
        $endpoint = $this->endpoint . '/supplierorders?limit=' . $limit .
            '&offset=' . $offset . '&sortCol=' . $sortColumn . '&sortDir=' . $sortDir;

        $response = $this->client->request('GET', $endpoint . '&apikey=' . $this->apiKey);

        $body = $response->getBody();
        return $this->serializer->deserialize($body, SupplierOrderList::class, 'json');
    }

    /**
     * {@inheritDoc}
     */
    public function getSupplierOrder($id)
    {
        $endpoint = $this->endpoint . '/supplierorders/' . $id;
        $options  = [
            'headers' => $this->headers
        ];
        $response = $this->client->request('GET', $endpoint . '?apikey=' . $this->apiKey, $options);

        $body = $response->getBody();
        return $this->serializer->deserialize($body, SupplierOrder::class, 'json');

    }

    /**
     * {@inheritDoc}
     */
    public function createSupplierOrder(SupplierOrderInterface $order)
    {
        $endpoint = $this->endpoint . '/supplierorders?apikey=' . $this->apiKey;
        $content  = $this->serializer->serialize($order, 'json');
        $options  = [
            'headers' => $this->headers,
            'body'    => $content
        ];
        try {
            $response = $this->client->request('POST', $endpoint, $options);
            return $this->serializer->deserialize((string)$response->getBody(), SupplierOrder::class, 'json');
        } catch (BadResponseException $e) {
            throw new ApiException($e->getResponse()->getReasonPhrase(), $e->getResponse()->getStatusCode(), $e);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function updateSupplierOrder($id, SupplierOrderInterface $order)
    {
        $endpoint = $this->endpoint . '/supplierorders/' . $id . '?apikey=' . $this->apiKey;
        $content  = $this->serializer->serialize($order, 'json');
        $options  = [
            'headers' => $this->headers,
            'body'    => $content
        ];

        try {
            return $response = $this->client->request('POST', $endpoint, $options);

        } catch (BadResponseException $e) {
            throw new ApiException($e->getResponse()->getReasonPhrase(), $e->getResponse()->getStatusCode(), $e);
        }
    }

}
