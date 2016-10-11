<?php

/**
 * @file
 * Contains Drupal\charitystock\Plugin\QueueWorker\CharityStockBase.php
 */

namespace Drupal\charitystock\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\node\Entity\Node;
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

    // Get the user who created this scan (author)
    $ownerID = $node->get('uid')->getValue()[0]['target_id'];

    // Get the shop associated with this user
    $query = \Drupal::entityQuery('node')
                        ->condition('type', 'shop')
                        ->condition('field_shop_user.target_id', $ownerID, '=');

    $nids = $query->execute();

    $shopID = reset($nids);

    // Associate this scan with the shop
    $node->field_scan_shop->target_id = $shopID;
    $node->save();

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

    // Generate a current date 
    $curDateTime = new \Drupal\Core\Datetime\DrupalDateTime();
    $curDateTime->setTimezone( $node->getOwner()->getTimezone() );

    if( count($nids) ){

      $stockItemID = reset($nids);

      $stockItemNode = entity_load('node', $stockItemID );

      // Set the confirmed date to now
      $stockItemNode->field_stock_item_confirmed = $curDateTime->format(DATETIME_DATETIME_STORAGE_FORMAT);

    } else {

      // Create new stock item 
      $values = array(
        'field_stock_item_book' => $bookID,
        'field_stock_item_shop' => $shopID,
        'field_stock_item_confirmed' => $curDateTime->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'type' => 'stock_item'
      );

      $stockItemNode = Node::create($values);

    }

    $stockItemNode->save();

    if (!$node->isPublished() && $node instanceof NodeInterface) {

      return $this->publishNode($node);
    }
  }

}
