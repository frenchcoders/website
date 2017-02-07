<?php
# * * * * * * * * * * * * * * * * * * * * * * * * * * * *
# CONTROLEUR SECONDAIRE DE LA HOMEPAGE " FRENCHCODERS "
# * * * * * * * * * * * * * * * * * * * * * * * * * * * *
use FrenchCoders\Core\Templates;

# * * * * * * *
# CONFIGURATION
# * * * * * * *

define('MODULE_NAME', 'home');
# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


$Template = new Templates(['static/templates/header.tpl.php',
						  'modules/' . MODULE_NAME . '/templates/homepage.tpl.php',
					  	  'static/templates/footer.tpl.php']);

$Template->render();
?>
