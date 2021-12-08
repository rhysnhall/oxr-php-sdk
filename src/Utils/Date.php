<?php

namespace OXR\Utils;

use OXR\Exception\SdkException;

class Date {

  const DATE_TIME_FORMAT = "Y-m-dH\TH:i\Z";

  /**
   * Checks if the provided param is an instance of DateTime.
   *
   * @param mixed $date
   * @return boolean
   */
  public static function isDateTime($date) {
    return $date instanceof \DateTime;
  }

  /**
   * Creates a DateTime from a string using the given format.
   *
   * @param string $format
   * @param string $date
   */
  public static function createFromFormat(
    string $format,
    string $date
  ) {
    return \DateTime::createFromFormat(
      $format,
      $date
    );
  }

  /**
   * Checks the start time and period against OXR's restrictions.
   *
   * @param DateTime $start_time
   * @param $period
   */
  public static function validateOHLC($start_time, $period) {
    if($start_time->format('s') > 0) {
      throw new SdkException("Start time must always have zero seconds (i.e. hh:mm:00).");
    }
    if($start_time < \DateTime("December 19th 2016")) {
      throw new SdkException("Start date must be on or after December 19th 2016.");
    }
    switch ($period) {
      case '1m':
        $modifier = "+1 minute";
        if($start_time < new \DateTime("-1 hour")) {
          throw new SdkException("Start date cannot be older than 1 hour ago when using a time period of 1 minute.");
        }
        break;
      case '5m':
        $modifier = "+5 minutes";
        if($start_time < new \DateTime("-1 day")) {
          throw new SdkException("Start date cannot be older than 1 day ago when using a time period of 5 minutes.");
        }
        if($start_time->format('i') % 5) {
          throw new SdkException("Start time minutes must be aligned to 5 minutes (e.g. 00, 05, 10, 15, etc) when using a time period of 5 minutes.");
        }
        break;
      case '15m':
        $modifier = "+15 minutes";
        if($start_time < new \DateTime("-1 day")) {
          throw new SdkException("Start date cannot be older than 1 day ago when using a time period of 15 minutes.");
        }
        if($start_time->format('i') % 15) {
          throw new SdkException("Start time minutes must be aligned to 15 minutes (i.e. 00, 15, 30, 45) when using a time period of 15 minutes.");
        }
        break;
      case '30m':
        $modifier = "+30 minutes";
        if($start_time < new \DateTime("-32 days")) {
          throw new SdkException("Start date cannot be older than 32 days ago when using a time period of 30 minutes.");
        }
        if($start_time->format('i') % 30) {
          throw new SdkException("Start time minutes must be aligned to 30 minutes (i.e. 00, 30) when using a time period of 30 minutes.");
        }
        break;
      case '1h':
        $modifier = "+1 hour";
        if($start_time < new \DateTime("-32 days")) {
          throw new SdkException("Start date cannot be older than 32 days ago when using a time period of 1 hour.");
        }
        if($start_time->format('i') % 30) {
          throw new SdkException("Start time minutes must be aligned to 30 minutes (i.e. 00, 30) when using a time period of 1 hour.");
        }
        break;
      case '12h':
        $modifier = "+12 hours";
        if($start_time->format('i') % 30) {
          throw new SdkException("Start time minutes must be aligned to 30 minutes (i.e. 00, 30) when using a time period of 12 hours.");
        }
        break;
      case '1d':
        $modifier = "+1 day";
        if($start_time->format('i') % 30) {
          throw new SdkException("Start time minutes must be aligned to 30 minutes (i.e. 00, 30) when using a time period of 1 day.");
        }
        break;
      case '1w':
        $modifier = "+1 week";
        if($start_time->format('Hi') > 0) {
          throw new SdkException("Start date must be aligned to the start of a calendar day (i.e. 00:00) when using a time period of 1 week.");
        }
        break;
      case '1mo':
        $modifier = "+1 month";
        if($start_time->format('d') > 1) {
          throw new SdkException("Start date must be aligned to the start of the calendar month (i.e. YYYY-mm-01) when using a time period of 1 month.")
        }
        break;
      default:
        throw new SdkException("{$period} is not a valid time period. Allowed periods are: 1m, 5m, 15m, 30m, 1h, 12h, 1d, 1w, and 1mo.");
    }

    $end_time = clone $start_time;
    $end_time->modify($modifier);
    if($end_time > new \DateTime("now")) {
      throw new SdkException("The combination of start time and the time period must not produce an end time that is in the future (i.e. an incomplete period).");
    }
  }

}
