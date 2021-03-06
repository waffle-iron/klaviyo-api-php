<?php

namespace Klaviyo;

use Klaviyo\Exception\ApiException;
use Klaviyo\Exception\BadRequestApiException;
use Klaviyo\Exception\NotAuthorizedApiException;
use Klaviyo\Exception\NotFoundApiException;
use Klaviyo\Exception\ServerErrorApiException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;

/**
 * The main Klaviyo API class for communicating with the Klaviyo API.
 */
class KlaviyoApi {

  public static $endPoint = 'https://a.klaviyo.com';
  public static $dataMap = [
    '$list' => 'list',
    '$person' => 'person',
  ];

  protected $apiKey;
  protected $httpClient;
  protected $options;

  /**
   * The constructor for KlaviyoApi.
   *
   * @param ClientInterface $http_client
   *   The HTTP client used for communication with the API.
   * @param string $api_key
   *   The API key to use when communicating with the API.
   *
   * @throws ApiException
   */
  public function __construct(ClientInterface $http_client, $api_key = '', $options = []) {
    if (empty($api_key)) {
      throw new ApiException('You must supply a Klaviyo API key.');
    }

    $this->apiKey = $api_key;
    $this->httpClient = $http_client;

    $this->options = $options + [
      'records_per_page' => 50,
    ];
  }

  /**
   * Helper method for creating a new API object.
   *
   * @param string $api_key
   *   The API key to use when communicating with the API.
   *
   * @return KlaviyoApi
   *   An instance of the KlaviyoApi.
   */
  public static function create($api_key = '', $options = []) {
    $httpClient = new Client(['base_uri' => self::$endPoint]);

    return new KlaviyoApi($httpClient, $api_key, $options);
  }

  /**
   * Retrieve a specific option.
   *
   * @param string $option
   *   The option name to retrieve.
   *
   * @return mixed
   *   The value of the option requested.
   */
  public function getOption($option) {
    $value = NULL;

    if (!empty($this->options[$option])) {
      $value = $this->options[$option];
    }

    return $value;
  }

  /**
   * Set a specific option.
   *
   * @param string $option
   *   The option name to set.
   * @param mixed $value
   *   The value of the option to set.
   *
   * @return $this
   */
  public function setOption($option, $value) {
    $this->options[$option] = $value;

    return $this;
  }

  /**
   * Retrieve an an array of all available options.
   */
  public function getAllOptions() {
    return $this->options;
  }

  /**
   * Perform a request against the API.
   *
   * @param string $method
   *   The HTTP method to use for the request.
   * @param string $resource
   *   The path to the resource to access on the API.
   * @param array $options
   *   Additional options to pass on to the HTTP client.
   *
   * @return ResponseInterface
   *    The response of the request as provided by the HTTP client.
   */
  public function request($method, $resource, $options = [], $public = FALSE) {
    $response = NULL;

    try {
      $response = $this->httpClient->request($method, $resource, $this->prepareRequestOptions($method, $options, $public));
    }
    catch (ClientException $e) {
      switch ($e->getResponse()->getStatusCode()) {
        case '400':
          throw new BadRequestApiException($e->getMessage());

        case '401':
          throw new NotAuthorizedApiException($e->getMessage());

        case '404':
          throw new NotFoundApiException($e->getMessage());

        case '500':
          throw new ServerErrorApiException($e->getMessage());

        default:
          throw new ApiConnectionException($e->getMessage());
      }
    }

    return $response;
  }

  /**
   * Prepare the options array before use in the request.
   *
   * @param array $options
   *   Additional options to pass on to the HTTP client.
   *
   * @return array
   *   The prepared additional options to pass on to the HTTP client.
   */
  public function prepareRequestOptions($method, $options, $public = FALSE) {
    if ($method === 'GET') {
      if (empty($options['query']['api_key'])) {
        $options['query']['api_key'] = $this->apiKey;
      }

      if ($public) {
        $api_key = $options['query']['api_key'];
        unset($options['query']['api_key']);
        $options = ['query' => ['data' => base64_encode(json_encode(['token' => $api_key] + $options['query']))]];
      }
    }
    elseif (empty($options['api_key'])) {
      $options['api_key'] = $this->apiKey;

      if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
        $options = ['form_params' => $options];
      }
    }

    return $options;
  }

}
