<?php

namespace Drupal\timeblock\Form;

use Drupal\timeblock\GoogleTimezonePlus;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Geocoder\Provider\GoogleMaps;

/**
 * Class TimeBlockForm.
 *
 * @package Drupal\timeblock\Form
 */
class TimeBlockForm extends FormBase {
  protected $geocoder;
  protected $dateFormatter;

  /**
   * TimeBlockForm constructor.
   * @param \Geocoder\Provider\GoogleMaps $geocoder
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   * @internal param \Drupal\timeblock\Services\GoogleTimezoneService $googletimezone
   */
  public function __construct(GoogleMaps $geocoder, DateFormatterInterface $date_formatter) {
    $this->geocoder = $geocoder;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('timeblock.googlemaps'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'time_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $api_key = NULL, $default_address = 'Helsinki') {
    $form = [];
    // Save api_key to form_state for later use.
    $form_state->set('api_key', $api_key);
    $local_time = new \DateTime('now');

    $form['local_time'] = [
      '#type' => 'container',
      '#theme' => 'timeblock_data',
      '#timedata' => $this->buildTimeDataArray($local_time),
    ];

    $address = $form_state->getValue('address', $default_address);

    $form['address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City / Address'),
      '#default_value' => $address,
      '#attributes' => ['autocomplete' => 'off'],
      '#ajax' => [
        'wrapper' => 'time-container',
        'event' => 'change',
        'callback' => '::getTimeCallback',
        'method' => 'replace',
        'prevent' => 'submit'
      ],
    ];

    $datetime = $this->getLocationTime($address, $api_key);

    $form['time_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'time-container'],
      '#theme' => 'timeblock_data',
      '#timedata' => [],
    ];

    if (!$datetime) {
      return $form;
    }

    $form['time_wrapper']['#timedata'] = $this->buildTimeDataArray($datetime, $address);

    $form['#attributes'] = array('onsubmit' => 'return false');

    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return mixed
   */
  public function getTimeCallback(array $form, FormStateInterface $form_state) {
    return $form['time_wrapper'];
  }

  /**
   * Returns timedata in mapped array compatible with timeblock_data theme hook.
   *
   * @param \DateTime $datetime
   * @param null $address
   * @return array
   */
  protected function buildTimeDataArray(\DateTime $datetime, $address = NULL) {
    $timedata = [
      'date' => $this->renderDatetime($datetime),
      'dateiso' => $datetime->format(DATE_ISO8601),
      'timezone' => $datetime->getTimeZone()->getName(),
      'address' => $address
    ];
    return $timedata;
  }

  /**
   * Makes API queries and returns datetime object with correct time.
   *
   * @param $address
   * @param $api_key
   * @return mixed
   * @throws \Exception
   */
  public function getLocationTime($address, $api_key) {
    try {
      $addresses = $this->geocoder->geocode($address);
    }
    catch (\Exception $e) {
      return NULL;
    }

    $address = $addresses->first();
    $google_timezone = new GoogleTimezonePlus($address->getLatitude(), $address->getLongitude());
    $google_timezone->setApiKey($api_key);

    try {
      $google_timezone->queryTimeZone();
    }
    catch (\Exception $e) {
      throw new \Exception($e->getMessage());
    }

    return $google_timezone->getDateTime();
  }

  /**
   * Helper method to render datetime object with dateFormatter.
   *
   * @param $datetime
   * @param string $type
   * @param string $format
   * @param null $langcode
   * @return string
   */
  protected function renderDatetime(\DateTime $datetime, $type = 'medium', $format = '', $langcode = NULL) {
    $tz = $datetime->getTimezone();
    return $this->dateFormatter->format($datetime->getTimestamp(), $type, $format, $tz->getName(), $langcode);
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
