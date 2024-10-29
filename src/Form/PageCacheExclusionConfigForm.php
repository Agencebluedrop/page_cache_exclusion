<?php

namespace Drupal\page_cache_exclusion\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements  PageCacheExclusion config form.
 */
class PageCacheExclusionConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_cache_exclusion_config_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['page_cache_exclusion.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('page_cache_exclusion.settings');

    $form['page_query_parameters_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Exclude cache for page variation with query parameters'),
      '#default_value' => $config->get('page_query_parameters_list'),
      '#description' => $this->t('List of Pages to exclude from cache in case query parameters are present (one page per line).'),
    ];

    $form['page_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Paths to exclude'),
      '#default_value' => $config->get('page_list'),
      '#description' => $this->t('List of pages to exclude entirely (one page per line).'),
    ];

    $form['client_error_caching'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable caching 4xx responses'),
      '#default_value' => $config->get('client_error_caching') ?? FALSE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $pageExclusionList = array_map('trim', explode("\n", $form_state->getValue('page_list')));
    foreach ($pageExclusionList as $pathPageExclusion) {
      if (empty($pathPageExclusion) || $pathPageExclusion === '<front>' || str_starts_with($pathPageExclusion, '/')) {
        continue;
      }
      $form_state->setErrorByName('page_list', $this->t("The path %path requires a leading forward slash when used with the Paths to exclude setting.", ['%path' => $pathPageExclusion]));
    }

    $pageQueryParametersList = array_map('trim', explode("\n", $form_state->getValue('page_query_parameters_list')));
    foreach ($pageQueryParametersList as $path) {
      if (empty($path) || $path === '<front>' || str_starts_with($path, '/')) {
        continue;
      }
      $form_state->setErrorByName('page_query_parameters_list', $this->t("The path %path requires a leading forward slash when used with the Exclude cache for page variation with query parameters setting.", ['%path' => $path]));
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('page_cache_exclusion.settings');
    $config->set('page_query_parameters_list', $form_state->getValue('page_query_parameters_list'))
      ->set('page_list', $form_state->getValue('page_list'))
      ->set('client_error_caching', $form_state->getValue('client_error_caching'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
