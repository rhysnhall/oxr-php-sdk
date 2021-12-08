<?php

namespace OXR;

use GuzzleHttp\Client as GuzzleHttpClient;
use OXR\Exception\RequestException;
use OXR\Utils\Request as RequestUtil;

class Client {

  const API_URL = "https://openexchangerates.org/api/";

  protected $app_id;
  protected $headers = [];

  public function __construct(
    string $app_id
  ) {
    $this->app_id = $app_id;
  }

  /**
   * Create a new instance of GuzzleHttp Client.
   *
   * @return GuzzleHttp\Client
   */
  public function createHttpClient() {
    return new GuzzleHttpClient();
  }

  public function __call($method, $args) {
    if(!count($args)) {
      throw new RequestException("No URI specified for this request. All requests require a URI and optional options array.");
    }
    $valid_methods = ['get'];
    if(!in_array($method, $valid_methods)) {
      throw new RequestException("{$method} is not a valid request method.");
    }
    $uri = $args[0];
    if($method == 'get') {
      $uri .= "?".RequestUtil::prepareParameters(
        array_merge(['app_id' => $this->app_id], $args[1] ?? [])
      );
    }
    $opts['headers'] = $this->headers;
    try {
      $client = $this->createHttpClient();
      $response = $client->{$method}(self::API_URL.'/'.$uri, $opts);
      $response = json_decode($response->getBody(), true);
      return $response;
    }
    catch(\Exception $e) {
      $response = $e->getResponse();
      $body = json_decode($response->getBody(), false);
      $status_code = $response->getStatusCode();
      throw new RequestException(
        "Received HTTP status [{$status_code} {$body->message}] with error \"{$body->description}\"."
      );
    }
  }

}
