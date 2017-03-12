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
    $api_key = $this->configuration['time_block_google_api'];
    if (empty($api_key)) {
      $build['time_block'] = $this->t('Google API key not found.');
    }
    $build['time_block'][] = $this->form_builder->getForm('Drupal\timeblock\Form\TimeBlockForm', $api_key, 'San Fransisco');
    return $build;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $form['time_block_google_api'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google API key'),
      '#default_value' => $this->configuration['time_block_google_api'],
    ];
    return $form;
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['time_block_google_api'] = $form_state->getValue('time_block_google_api');
  }
}
