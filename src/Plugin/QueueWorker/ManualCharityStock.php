<?php

namespace Drupal\charitystock\Plugin\QueueWorker;

/**
 * A Node Publisher that publishes nodes via a manual action triggered by an admin.
 *
 * @QueueWorker(
 *   id = "manual_charitystock",
 *   title = @Translation("Manual Charity Stock"),
 * )
 */
class ManualCharityStock extends CharityStockBase {}