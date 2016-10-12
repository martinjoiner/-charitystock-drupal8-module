<?php

/**
 * @file
 * Contains \Drupal\charitystock\Form\SettingsForm.
 */

namespace Drupal\charitystock\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class SettingsForm extends ConfigFormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'charitystock_settings_form';
  }


  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'charitystock.settings',
    ];
  }
  

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('charitystock.settings');

    $form['isbndb_api_key'] = array (
      '#type' => 'textfield',
      '#title' => $this->t('ISBNdb API Key'),
      '#description' => $this->t('Obtain one of these by registering an account at ISBNdb.com'),
      '#default_value' => $config->get('isbndb_api_key'),
    );
    
    return parent::buildForm($form, $form_state);
  }
  

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = \Drupal::service('config.factory')->getEditable('charitystock.settings');

    $config->set('isbndb_api_key', $form_state->getValue('isbndb_api_key') )->save();

    parent::submitForm($form, $form_state);
  }

}
