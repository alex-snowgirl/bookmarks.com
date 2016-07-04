<?php
/**
 * Created by PhpStorm.
 * User: snowgirl
 * Date: 7/4/16
 * Time: 2:59 PM
 */

ini_set('display_errors', 'Off');
ini_set('error_log', __DIR__ . '/../php.log');

require_once '../app.php';

(new App($_SERVER, $_REQUEST))->run();