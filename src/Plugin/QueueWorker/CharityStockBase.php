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
   * Queries API and creates a node of type 'book'.
   *
   * @param {number} $isbn
   * @return int The ID of the newly created node 
   */
  protected function queryISBNdbAPI( $isbn ) {

    $config = \Drupal::config('charitystock.settings');

    // Lookup ISBN on API 
    $apiurl = 'http://isbndb.com/api/v2/json/' . $config->get('isbndb_api_key') . '/book/' . $isbn;

    // Get and parse the JSON
    $json = file_get_contents($apiurl);
    $vals = json_decode($json);

    $book = $vals->data[0];

    // Create an entry in the Book table
    $bookVals = array(
      'title' => $book->title,
      'field_book_author_name' => $book->author_data[0]->name,
      'field_book_isbn' => $book->isbn13,
      'type' => 'book'
    );

    $bookNode = Node::create($bookVals);
    $bookNode->save();

    return $bookNode->nid->value;
  }


  /**
   * {@inheritdoc}
   */
  public function processItem($data) {

    /** @var NodeInterface $node */
    $node = $this->nodeStorage->load($data->nid);

    // Get the shop code recorded with this scan
    $shop_code = $node->field_scan_shop_code->getValue()[0]['value'];

    // Get the shop associated with this user
    $query = \Drupal::entityQuery('node')
                        ->condition('type', 'shop')
                        ->condition('field_shop_code', $shop_code, '=');

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

    } else {

      $bookID = $this->queryISBNdbAPI($barcode);

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

      // Get the user who created this scan (author)
      $ownerID = $node->get('uid')->getValue()[0]['target_id'];

      // Create new stock item 
      $values = array(
        'uid' => $ownerID,
        'field_stock_item_book' => $bookID,
        'field_stock_item_shop' => $shopID,
        'field_stock_item_confirmed' => $curDateTime->format(DATETIME_DATETIME_STORAGE_FORMAT),
        'type' => 'stock_item'
      );

      $stockItemNode = Node::create($values);

    }

    $stockItemNode->save();

  }

}
