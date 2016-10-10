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



  /**
   * 
   */
  // public function hook_cron(){
  // 	//mail('martin@martinjoiner.co.uk', 'Hook Cron Ran', 'Hook cron function was executed');

  // 	$nids = \Drupal::entityQuery('node')
  // 	  ->condition('type', 'scan')
  // 	  ->execute(1);

  // 	$nodes = \Drupal::entityTypeManager()
		//   ->getStorage('node')
		//   ->loadMultiple($nids);



  // 	return array(
  //     '#type' => 'markup',
  //     '#markup' => t('Hello world ') . var_dump($nodes[12])
  //   );
  // }




}
