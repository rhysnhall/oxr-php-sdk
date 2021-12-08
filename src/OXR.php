<?php

namespace OXR;

use OXR\Client;
use OXR\Exception\SdkException;
use OXR\Utils\Date as DateUtil;

class OXR {

  /**
   * @var OXR\Client
   */
  public static $client;

  /**
   * @var string
   */
  protected $base_currency = 'USD';

  /**
   * @var array
   */
  protected $show_alternative = false;

  public function __construct(
    string $app_id,
    array $config = []
  ) {
    static::$client = new Client($app_id);
    $check_config = ['show_alternative', 'base_currency'];
    foreach($check_config as $key) {
      if(isset($config[$key])) {
        $this->{$key} = $config[$key];
      }
    }
  }

  /**
   * Gets all currencies. Set the show_alternative config value to show alternative and digital currencies.
   *
   * @return array
   */
  public function getCurrencies() {
    $params = [
      'show_alternative' => $this->show_alternative
    ];
    $currencies = static::$client->get('/currencies.json', $params);
    return $currencies;
  }

  /**
   * Gets the exchange rates from the base currency.
   *
   * @param array|string $currencies
   * @return array
   */
  public function getLatestRates(
    $currencies = []
  ) {
    $params = [
      'base' => $this->base_currency,
      'show_alternative' => $this->show_alternative
    ];
    $rates = static::$client->get(
      'latest.json',
      $this->formatCurrencyParam($currencies, $params)
    );
    return $rates['rates'] ?? [];
  }

  /**
   * Get the exchange rates for a given date.
   *
   * @param mixed $date
   * @param array|string $currencies
   */
  public function getHistoricalRates(
    $date,
    $currencies = []
  ) {
    if(is_string($date)) {
      // Validate string date.
      if(!\DateTime::createFromFormat('Y-m-d', $date)) {
        throw new SdkException("{$date} is not a valid date. Valid format is YYYY-MM-DD.");
      }
    }
    // Format DateTime object.
    elseif($start_time instanceof \DateTime) {
      $date = $date->format('Y-m-d');
    }
    else {
      throw new SdkException("Historical date is invalid.");
    }
    $params = [
      'base' => $this->base_currency,
      'show_alternative' => $this->show_alternative
    ];
    // Get historial date with path format historial/YYYY-MM-DD.json
    $rates = static::$client->get(
      "historical/{$date}.json",
      $this->formatCurrencyParam($currencies, $params)
    );
    return $rates['rates'] ?? [];
  }

  /**
   * Get historical exchange rates per day between two dates.
   *
   * @param mixed $start
   * @param mixed $end
   * @param mixed currencies
   * @return array
   */
  public function getTimeSeries(
    $start,
    $end,
    $currencies = []
  ) {
    foreach(['start', 'end'] as $var) {
      $date = ${$var};
      if(is_string($date)) {
        // Validate string date.
        if(!\DateTime::createFromFormat('Y-m-d', $date)) {
          throw new SdkException("{$date} is not a valid {$var} date. Valid format is YYYY-MM-DD.");
        }
      }
      // Format DateTime object.
      elseif($start_time instanceof \DateTime) {
        $date = $date->format('Y-m-d');
      }
      else {
        throw new SdkException("Value for {$var} date is invalid.");
      }
    }
    $params = [
      'start' => $start,
      'end' => $end,
      'base' => $this->base_currency,
      'show_alternative' => $this->show_alternative
    ];
    $rates = static::$client->get(
      "time-series.json",
      $this->formatCurrencyParam($currencies, $params)
    );
    return $rates['rates'];
  }

  /**
   * Convert an amount from once currency to another.
   *
   * @param float $amount
   * @param string $to
   * @param string|null $from
   * @return array
   */
  public function convert(
    float $amount,
    string $to,
    $from = null
  ) {
    if(!is_string($from)) {
      $from = $this->base_currency;
    }
    // Set the path params.
    $path = "convert/{$amount}/{$from}/{$to}";
    $rates = static::$client->get($path);
    return [
      'converted_at' => $rates['meta']['timestamp'],
      'rate' => $rates['meta']['rate'],
      'value' => $rates['response']
    ];
  }

  /**
   * Get historical Open, High Low, Close (OHLC) and Average exchange rates for a given time period
   *
   * @param mixed $start_time
   * @param string $period
   * @return array
   */
  public function getOHLCRates(
    $start_time,
    string $period
  ) {
    if(is_string($start_time)) {
      if(!$start_time = DateUtil::createFromFormat(
        DateUtil::DATE_TIME_FORMAT,
        $start_time
      )) {
        throw new SdkException("{$start_time} is not a valid start time. Format must be YYYY-MM-DDThh:mm:00Z.");
      }
    }
    elseif(!DateUtil::isDateTime($start_time)) {
      throw new SdkException("Start time is invalid. Expects either a string or an instance of DateTime.");
    }
    $formatted_start_time = $start_time->format(DateUtil::DATE_TIME_FORMAT);

    // Validate the start time and time period.
    DateUtil::validateOHLC($formatted_start_time, $period);

    $params = [
      'start_time' => $formatted_start_time,
      'period' => $period,
      'base' => $this->base_currency,
      'show_alternative' => $this->show_alternative
    ];
    $rates = static::$client->get("ohlc.json", $params);
    return $rates['rates'];
  }

  public function getUsage() {
    $usage = static::$client->get('usage.json');
    return $usage;
  }

  /**
   * Sets the base currency.
   *
   * @param string $currency
   * @return OXR\Client
   */
  public function baseCurrency(string $currency) {
    $this->base_currency = $currency;
    return $this;
  }

  /**
   * Sets the show alternative flag. This allows alternative, black market and digital currency rates to be displayed.
   *
   * @param boolean $bool
   * @return OXR\Client
   */
  public function showAlternative($bool) {
    $this->show_alternative = $bool;
    return $this;
  }

  /**
   * Checks the format of the currencies and adds to the request params.
   *
   * @param mixed $currencies
   * @param array $params
   * @return array
   */
  private function formatCurrencyParam($currencies, $params) {
    // Check if currency is a string or array.
    $currencies = is_string($currencies)
      ? [$currencies]
      : $currencies;
    // Add the currencies to the request params.
    if(count($currencies)) {
      $params['symbols'] = implode(',', $currencies);
    }
    return $params;
  }

}
