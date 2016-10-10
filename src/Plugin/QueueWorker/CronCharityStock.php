<?php

namespace Drupal\charitystock\Plugin\QueueWorker;

/**
 * A Node Publisher that publishes nodes on CRON run.
 *
 * @QueueWorker(
 *   id = "cron_charitystock",
 *   title = @Translation("Cron Charity Stock"),
 *   cron = {"time" = 20}
 * )
 */
class CronCharityStock extends CharityStockBase {}