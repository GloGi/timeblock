<?php
namespace Drupal\timeblock\Services;

use Exception;
use GoogleMapsTimeZone;

class GoogleTimezoneService extends GoogleMapsTimeZone {
  protected $response;
  protected $drupalDateTime;

  public function __construct($latitude = 0, $longitude = 0, $timestamp = 0, $format = self::FORMAT_JSON) {
    parent::__construct($latitude, $longitude, $timestamp, $format);
    $this->response = [];
  }

  /**
   * @return static
   * @throws \Exception
   */
  public function getDateTime() {
    // This method is meant for after api has been queried return FALSE is no response is available.
    $response = $this->getResponse();
    if (empty($response)) {
      throw new Exception('No response available');
    }

    $datetime = $this->getDefaultDateTime();

    // Use DateTimeZone to localize time properly.
    // This can also be done with offsets provided by response but this is simpler way of doing it.
    $datetime->setTimezone(new \DateTimeZone($response['timeZoneId']));

    return $datetime;
  }

  /**
   * @return \DateTime
   */
  protected function getDefaultDateTime() {
    $datetime = new \DateTime(null, new \DateTimeZone('UTC'));

    // Fetch timestamp.
    $timestamp = $this->getTimestamp();

    if (!empty($timestamp)) {
      $datetime->setTimestamp($timestamp);
    }

    return $datetime;
  }

  /**
   * @return array
   */
  public function getResponse() {
    return $this->response;
  }

  /**
   * @param bool $raw
   * @param null $context
   * @return array|\SimpleXMLElement|string
   */
  public function queryTimeZone($raw = false, $context = null) {
    $this->response = parent::queryTimeZone($raw, $context);
    return $this->response;
  }

  /**
   * Helper method to create dateintervals with second offsets.
   * @param $seconds
   * @return string
   */
  protected function createDateInterval($seconds) {
    $invert = 0;
    if ($seconds < 0) {
      $seconds = abs($seconds);
      $invert = 1;
    }

    $offset = sprintf('PT%sS', $seconds);
    $dateinterval = new \DateInterval($offset);
    $dateinterval->invert = $invert;

    return $dateinterval;
  }
}
