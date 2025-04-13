<?php

namespace Drupal\content_access_limiter\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;

/**
 * Provides a form for configuring content access limiter settings.
 */
class LimitSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['content_access_limiter.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'content_access_limiter_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('content_access_limiter.settings');

    $form['limit_settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Content Access Limits'),
      '#description' => $this->t('Configure the maximum number of content items users can access per day.'),
    ];

    $form['limit_settings']['daily_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Daily Access Limit'),
      '#description' => $this->t('Maximum number of content items a user can access per day.'),
      '#default_value' => $config->get('daily_limit') ?? 10,
      '#min' => 1,
      '#required' => TRUE,
    ];

    $form['limit_settings']['bypass_roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles that bypass limits'),
      '#description' => $this->t('Select roles that should not be subject to access limits.'),
      '#options' => user_role_names(TRUE),
      '#default_value' => $config->get('bypass_roles') ?? [],
    ];

    // Get all content types.
    $content_types = NodeType::loadMultiple();
    $options = [];
    foreach ($content_types as $content_type) {
      $options[$content_type->id()] = $content_type->label();
    }

    $form['content_types'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Content Types'),
      '#description' => $this->t('Select which content types should be subject to access limits.'),
    ];

    $form['content_types']['enabled_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content Types'),
      '#description' => $this->t('Select content types that should be subject to access limits.'),
      '#options' => $options,
      '#default_value' => $config->get('enabled_types') ?? [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('content_access_limiter.settings')
      ->set('daily_limit', $form_state->getValue('daily_limit'))
      ->set('bypass_roles', array_filter($form_state->getValue('bypass_roles')))
      ->set('enabled_types', array_filter($form_state->getValue('enabled_types')))
      ->save();

    parent::submitForm($form, $form_state);
  }
}