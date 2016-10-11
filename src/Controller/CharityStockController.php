<?php

/**
 * @file
 * Contains \Drupal\charitystock\Controller\CharityStockController.
 */

namespace Drupal\charitystock\Controller;

use Drupal\Core\Controller\ControllerBase;


/**
 * CharityStockController.
 */
class CharityStockController extends ControllerBase {

  public function content() {
    return array(
      '#type' => 'markup',
      '#markup' => t('ISBN Sightings')
    );
  }

}
