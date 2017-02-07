<?php
# * * * * * * * * * * * * * * * * * * * *
# CONTROLEUR PRINCIPAL DE LA PLATEFORME
# * * * * * * * * * * * * * * * * * * * *
include('config.php');
if(isset($_GET['module'])):

	if(is_dir(MODULES_DIR_PATH . $_GET['module'])):

		# * * * * * * * * * * * * * * * * * * * * * * * *
		# INCLURE LES MODELES ET LE CONTROLEUR SECONDAIRE
		# * * * * * * * * * * * * * * * * * * * * * * * *

		# Modèles
		foreach($_Modules->incModels($_GET['module']) as $v):
			include($v);
		endforeach;
		# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

		# Controleur secondaire
		include($_Modules->incCtrl($_GET['module']));
		# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

		# * * * * * * * * *
		# ENTREES DE LANGUE
		# * * * * * * * * *
		$_Lang->load(MODULES_DIR_PATH . $_GET['module'] . '/lang/');
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =
	
	else:

		# * * * * * * * * * * * * * * * *
		# LE MODULE N'A PAS ETRE TROUVE
		# * * * * * * * * * * * * * * * *
		throw new Exception('Can\'t find module ' . $_GET['module']);

	endif;

endif;
?>
