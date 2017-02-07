<?php
# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
# ACQUERIR DES INFORMATIONS SUR LES MODULES DISPONIBLES POUR LE PROJET
# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
namespace FrenchCoders\Core;
use \Exception;

/**
* Acquérir des informations sur les modules disponibles pour le projet et fournir un cadre
* de travail clair pour le développement de nouveaux modules.  */
class Modules {

	# * * * * * * * * * * * * * * * * * * * * *
	# RELATIF A LA CONFIGURATION DE L'INSTANCE
	# * * * * * * * * * * * * * * * * * * * * *

	/**
	  * Chemin du répertoire contenant les modules, précisé par le constructeur
	  * @var string|string $moduleDirectory */
	private $modulesDirectory = '';

	/**
	  * Liste des modules présents dans le dossier des modules *
	  * @var array|array $modules */
	private $modules = [];

	/**
	  * Chemins des modules relatifs à la racine du projet acessibles par noms de modules
	  * @var array|array $modulesPaths */
	private $modulesPaths = [];
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = == = = = = = =

	# * * * * * * * *
	# INITIALISATION
	# * * * * * * * *

	/**
	  * Nouvelle instance de la classe
	  * @param string $moduleDirectory Chemin du répertoire des modules relativement au fichier index.php du projet */
	public function __construct($modulesDirectory) {

		# * * * * * * * * * * * *
		# REPERTOIRE DES MODULES
		# * * * * * * * * * * * *
		if(!is_dir($modulesDirectory)):
			throw new Exception(get_class($this) . '::__construct() : Error - modules\' directory not found.');
		endif;
		$this->modulesDirectory = Utils::fpath(trim($modulesDirectory)); # stockage du répertoire indiqué pour les modules
		# - - - - - - - - - - - - - - - - -- - - - - - - - - -- - - - - - - - -

		# * * * * * * * * * * * * * * *
		# LISTE DES MODULES DISPONIBLES
		# * * * * * * * * * * * * * * *
		$modules = glob($this->modulesDirectory . '*', GLOB_ONLYDIR);

		if($modules !== false):

			# @NOTE : On stocke indépendement les noms de modules et leurs chemins.
			# Les chemins sont indéxés par les noms de modules.
			foreach($modules as $v):
				$parts = explode('/', $v);
				$moduleName = $parts[count($parts)-1]; # le nom du module est le dernières partie du chemin.
				array_push($this->modules, $moduleName);
				$this->modulesPaths[$moduleName] = $v;
			endforeach;

		else:
			# Une erreur s'est produite.
			throw new Exception(get_class($this) . '::__construct() : Error while trying to open modules directory.');
		endif;
	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	# COMPARER LE MODULE CONCERNE PAR LA DEMANDE A UNE CHAINE DE CARACTERES
	# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	/**
	  * Comparer le nom textuel du module concerné par la demande à une chaine
	  * de caractères.
	  * @ return bool Resultat de la comparaison*/
	public function is($module) {
		return !isset($_GET['module']) ? 0:strtolower(trim($_GET['module'])) == strtolower(trim($module)) ? 1:0;
	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * * * * * * * * * * * * * * *
	#          FICHIERS DES MODULES
	# * * * * * * * * * * * * * * * * * * * * *

	# * * * *
	# MODELES
	# * * * *

	/** Inclure tous les modèles appartenant à $module, ou seulement le / les modèle(s)
	  * précisés par $modelName. Si aucun modèle n'est précisé, alors tous les modèles seront chargés.
	  * @param string $module Chaine de caractère réprésentant le nom du module concern é
	  * @param string|array Noms des modèles concernés correspondant au nom du fichier sans l'extension .php
	  * @return array|string Fichier(s) à inclure */
	public function incModels($module, $modelName = 0) {

		$module = trim($module);
		$modulePath = $this->modulesDirectory . '/' . $module; # chemin supposé du module
		if(!is_dir($modulePath)):
			# Le module pour lequel il est demander d'inclure les modèles DOIT exister.
			throw new Exception(get_class($this) . '::incModels can\'t find module ' . $module);
		endif;
		# - - - - - - - - - - - - - - - - - -- - - - - - - -- - - - - - - - - -

		# * * * * * * * * * * * * * * *
		# CHEMIN DES MODELES A INCLURE
		# * * * * * * * * * * * * * * *
		if(!$modelName):

			# TOUS LES FICHIERS DE MODELES DU MODULE SONT PREVUS POUR INCLUSION
			$modelsFiles = glob('modules/' . $module . '/models/*.php');
			$modelsFilesPath = array();
			foreach($modelsFiles as $v):
				array_push($modelsFilesPath, $v);
			endforeach;

			return $modelsFilesPath;

		else:
			# UN OU PLUSIEURS FICHIERS PRECIS SONT DEMANDES POUR INCLUSION
			if(is_array($modelName)): # Plusieurs fichiers sont indiqués

				$modelsFiles = [];
				foreach($modelName as $v):
					# Chemin supposé du fichier de module
					$supposed = $this->modulesDirectory .'/' . Utils::fpath($module) . $v . '.php';
					if(is_file($supposed)):
						# @NOTE : N'est retourné que si le ficher de modèle existe
						array_push($modelsFiles, $supposed);
					endif;
				endforeach;

				return $modelsFiles;

			elseif(is_string($modelName)): 	# Un seul fichier est indiqué

				$supposed = $this->modulesDirectory .'/' . Utils::fpath($module) . $v . '.php';
				if(is_file($supposed)):
					# Retour des chemin des modèles qu'ils est possible d'inclure pour ce module
					return $supposed;
				endif;
			endif;
		endif;
	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * * * * * *
	# CONTROLEURS SECONDAIRES
	# * * * * * * * * * * * *

	/** Inclure le controleur secondaire correspondant au module précisé par $module.
	  * @param string $module Chaine de caractère qui représente le module concerné.
	  * @return string Chemin du fichier à inclure */
	public function incCtrl($module) {
		$module = trim($module);
		$modulePath = Utils::fpath($this->modulesDirectory) . $module;

		if(!is_dir($modulePath)):
			throw new Exception(get_class($this) . '::incCtrl can\'t find module ' . $module);
		endif;
		# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

		return Utils::fpath($modulePath) . 'controller.php';
	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * * * * * *
	# EXISTANCE D'UN MODULE ?
	# * * * * * * * * * * * *

	/**
	  * Déterminer si un module est disponible localement pour le projet.
	  * @param string $moduleName Chaine de caractère représentant le nom du module
	  * @return bool */
	public function exists($moduleName) {
		return (!empty($moduleName) ? (in_array(trim($moduleName), $this->modules)) ? true:false:false);
	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =

}
