<?php
# * * * * * * * * * * * * * * * * * *
# FICHIER DE CONFIGURATION DU PROJET
# * * * * * * * * * * * * * * * * * *
use FrenchCoders\Core\Templates,
	FrenchCoders\Core\Langs,
	FrenchCoders\Core\Modules,
	FrenchCoders\Core\Utils;


# * * * * * *
# CONSTANTES
# * * * * * *
define('LIBRARIES_DIR_PATH', 'libs/');
define('MODULES_DIR_PATH', 'modules/');
define('STATIC_DIR_PATH', 'static/');

define('DEBUG', 1);
define('LOCALE', 'fr');

define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', 'm0li3re');
define('DB_DATABASE', 'frenchcoders');
# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


# * * * * * *
# LIBRAIRIES
# * * * * * *
include(LIBRARIES_DIR_PATH . 'templates.php');
include(LIBRARIES_DIR_PATH . 'langs.php');
include(LIBRARIES_DIR_PATH . 'modules.php');
include(LIBRARIES_DIR_PATH . 'utils.php');
# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =

# * * * * * * * *
# BASE DE DONNEES
# * * * * * * * *
try {

	$_PDO = new PDO('mysql:host=' . DB_HOST. ';dbname=' . DB_DATABASE . ';charset=utf8', DB_USERNAME, DB_PASSWORD,
					 [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'",
					  PDO::ATTR_EMULATE_PREPARES => 0,
					  PDO::ATTR_STRINGIFY_FETCHES => 0]
					);

	# MODE D'ERREURS
	if(DEBUG):
		$_PDO->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	endif;
}

catch (PDOException $e) {
	# Une erreur s'est produite.
	throw new Exception($e->getMessage());
}
# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


# * * * * *
# INSTANCES
# * * * * *
$_Modules = new Modules(MODULES_DIR_PATH);
$_Lang = new Langs(LOCALE, STATIC_DIR_PATH . 'lang/');

# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =

?>
