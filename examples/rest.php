<?php

error_reporting(E_ALL);

require '../vendor/autoload.php';
include '../src/autoload.php';

date_default_timezone_set('America/Los_Angeles');

\Nymph\Nymph::configure([
  'MySQL' => [
    'host' => '127.0.0.1',
    'database' => 'nymph_example',
    'user' => 'nymph_example',
    'password' => 'omgomg'
  ]
]);

$NymphREST = new \Nymph\REST();

try {
  if (in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'DELETE'])) {
    parse_str(file_get_contents("php://input"), $args);
    $NymphREST->run($_SERVER['REQUEST_METHOD'], $args['action'], $args['data']);
  } else {
    $NymphREST->run($_SERVER['REQUEST_METHOD'], $_REQUEST['action'], $_REQUEST['data']);
  }
} catch (\Nymph\Exceptions\QueryFailedException $e) {
  echo $e->getMessage()."\n\n".$e->getQuery();
}
