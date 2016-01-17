<?php
/**
 * Created by PhpStorm.
 * User: dss
 * Date: 15.01.16
 * Time: 21:23
 */

/**
 * require classes
 */
require(__DIR__ . '/Crawler.php');
require(__DIR__ . '/Application.php');
require(__DIR__ . '/Reporting.php');

echo('put the url and push the enter: ');

/** @var string $url */
$url = trim(fgets(STDIN));

/** @var Application $appl */
$appl = new Application();

/** run crawler */
$appl->run($url);