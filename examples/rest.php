<?php

error_reporting(E_ALL);

require '../vendor/autoload.php';
require '../src/autoload.php';

\SciActive\R::_('NymphConfig', [], function(){
	$nymph_config = include(__DIR__.DIRECTORY_SEPARATOR.'../vendor/sciactive/nymph/conf/defaults.php');

	$nymph_config->MySQL->database['value'] = 'nymph_test';
	$nymph_config->MySQL->user['value'] = 'nymph_test';
	$nymph_config->MySQL->password['value'] = 'omgomg';

	return $nymph_config;
});

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