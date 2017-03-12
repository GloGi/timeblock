<?php

namespace Drupal\timeblock\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'TimeBlock' block.
 *
 * @Block(
 *  id = "time_block",
 *  admin_label = @Translation("Time block"),
 * )
 */
class TimeBlock extends BlockBase implements ContainerFactoryPluginInterface {
  protected $form_builder;

  /**
   * TimeBlock constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, FormBuilderInterface $form_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->form_builder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    // Disable caching for this block.
    $build['#cache']['max-age'] = 0;
    $api_key = $this->configuration['google_timezone_api_key'];
    $default_address = $this->configuration['default_address'];
    $error_message = [];


    if (empty($api_key)) {
      $error_message[] = $this->t('Google API key not found for timeblock.');
    }
    if (empty($default_address)) {
      $error_message[] = $this->t('Default address not placed for timeblock');
    }

    if (!empty($error_message)) {
      foreach ($error_message as $message) {
        drupal_set_message($message);
      }
      return $build;
    }

    $build['time_block'] = $this->form_builder->getForm('Drupal\timeblock\Form\TimeBlockForm', $api_key, $default_address);
    return $build;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['google_timezone_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Timezone API key'),
      '#default_value' => $this->configuration['google_timezone_api_key'],
      '#required' => TRUE,
    ];
    $form['default_address'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default address'),
      '#description' => $this->t('Default address for time search block'),
      '#default_value' => $this->configuration['default_address'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['google_timezone_api_key'] = $form_state->getValue('google_timezone_api_key');
    $this->configuration['default_address'] = $form_state->getValue('default_address');
  }
}
