<?php

/**
 * @file
 * Contains Drupal\charitystock\Plugin\QueueWorker\CharityStockBase.php
 */

namespace Drupal\charitystock\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides base functionality for the NodePublish Queue Workers.
 */
abstract class CharityStockBase extends QueueWorkerBase implements ContainerFactoryPluginInterface {


  /**
   * The node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;


  /**
   * Creates a new CharityStockBase.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   The node storage.
   */
  public function __construct(EntityStorageInterface $node_storage) {
    $this->nodeStorage = $node_storage;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('entity.manager')->getStorage('node')
    );
  }


  /**
   * Publishes a node.
   *
   * @param NodeInterface $node
   * @return int
   */
  protected function publishNode($node) {
    $node->setPublished(TRUE);
    return $node->save();
  }


  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    /** @var NodeInterface $node */
    $node = $this->nodeStorage->load($data->nid);


    $shopID = $node->get('field_scan_shop')->first()
                          ->get('entity')
                          ->getTarget()
                          ->getValue()
                          ->id(); 

    $barcode = $node->getTitle();

    // Lookup ISBN in `book` table (if it's ISBN has been looked up before)
    $query = \Drupal::entityQuery('node')
                        ->condition('type', 'book')
                        ->condition('field_book_isbn', $barcode, '=');

    $nids = $query->execute();

    if( count($nids) ){

      $bookID = reset($nids);
      $bookNode = entity_load('node', $bookID );

    } else {

      // TODO: Lookup ISBN on API 

      // TODO: Create an entry in the Book table

    }


    // Check if a Stock Item exists for this Book in this Shop
    $query = \Drupal::entityQuery('node')
                        ->condition('type', 'stock_item')
                        ->condition('field_stock_item_book.target_id', $bookID, '=')
                        ->condition('field_stock_item_shop.target_id', $shopID, '=');

    $nids = $query->execute();

    if( count($nids) ){

      $stockItemID = reset($nids);

      $stockItemNode = entity_load('node', $stockItemID );

      // TODO: Change the last updated date to now
      //$stockItemNode->

    } else {

      // TODO: Create new stock item 

    }

    if (!$node->isPublished() && $node instanceof NodeInterface) {

      return $this->publishNode($node);
    }
  }

}
