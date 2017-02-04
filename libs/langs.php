<?php
# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
# GESTION DES ELEMENTS DE LANGAGE POUR l'INTERNATIONALISATION
# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
namespace FrenchCoders\Core;
use \Exception;

/*
 * Gestion des éléments de langues du projet.
 * Pour permettre de détacher les éléments de texte du code et de préparer l'internationalisation. */
class Langs {

	# * * * * * *
	# PROPRIETES
	# * * * * * *

	/**
  	  * Langue utilisée par l'instance codée sur deux caractères.
	  * @var string|null $locale */
	private $locale;

	/**
	  *  Tableau des entrées de langues relativement à la propriété Lang::locale
	  *   @var array|null $Lang */
	private $Lang;

	/**
	  * Tableau des entrées de langues supplémentaires d'un module
	  * @var array|int $Lang */
	private $extraModuleLangFrom = 0;

	/**
	  * Déterminer le préfixe à utiliser pour l'inclusion du fichier de langue principal
	  * @var string|string $prefix */
	 private $prefix = '';
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * *
	# INITIALISATION
	# * * * * * * * *

	/**
	  * Nouvelle instance.
	  * @param string $locale Langue à utiliser codée sur deux lettres
	  * @param string $prefix Si précisé, préfixe à utiliser lors de l'inclusion du fichier principal.
	  * @return void Langs */
	public function __construct($locale, $prefix = 0) {

		# * * * * * * * * * * * * * * * * * * * * * * * * * *
		# LOCALE A UTILISER POUR CETTE INSTANCE DE LA CLASSE
		# * * * * * * * * * * * * * * * * * * * * * * * * * *
		$locale = trim($locale);
		if(!empty($locale)):
			$this->locale = strtolower(trim($locale));
		else:
			throw new Exception('Lang - __construct() - Invalid parameter $locale !');
		endif;
		# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

		# * * * * * * * * * * * * * * * *
		# PREFIXE DES CHEMINS DE FICHIERS
		# * * * * * * * * * * * * * * * *
		if(is_string($prefix)):
			 $this->prefix = Utils::fpath($prefix);
		endif;
		# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -


		# * * * * * * * * * * * * * * * *
		# FICHIERS DES ENTREES GENERALES
		# * * * * * * * * * * * * * * * *
		if(!empty($this->locale)):

			if(is_file($this->prefix . $locale . '.php')):
				# Inclusion
				include($this->prefix .  $locale . '.php');
				# Stockage
				$this->Lang = $_L;

			else:
				throw new Exception('Lang - __construct() - Can\'t find ' . $this->prefix . $locale . '.php');
			endif;
		endif;

		return $this;
	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	# ENTREES DE LANGUE RELATIVES A UN MODULE EN PARTICULIER
	# * * * * * * * * * * * * * * * * * * * * * * * * * * * *

	/**
	  * Charger des entrées de langue supplémentaires depuis un répertoire de langue supplémentaire.
	  * @param string $filename Chemin du fichier supplémentaire à chager
	  * @return bool */
	public function load($path) {
		$filename = trim($path);

		if(!empty($path)):
			$path = Utils::fpath($path);

			if(is_dir($path)):

				$file = $path . $this->locale . '.php';
				if(is_file($file)):
					include($file);
					$this->Lang = array_merge($this->Lang, $_L); # les nouvelles entrées sont stockées

				else:
					throw new Exception('Can\'t open lang file ' . $file);
				endif;
			else:
				throw new Exception('Directory : ' . $path . ' - not found.');
			endif;
		endif;
	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * * * * *
	# ACESSEURS / MUTATEURS
	# * * * * * * * * * * *

	/**
	  * Renvoyer la locale définie pour l'instance.
	  * @return string Locale définie pour l'instance sur deux caractères. */
	public function getLocale() {
		return $this->locale;
	}


	/**
	  * Renvoyer l'élément de langue correspondant à la la clé
	  * @param $name Clé de l'élément dans le tableau des éléments de langue. */
	public function get($name) {
		# Renvoyer l'élément de langue
		if(isset($this->Lang[$name])):
			return $this->Lang[$name];
		else: return false; endif;
	}


	/**
	  * Renvoyer l'élément de langue correspondant à la clé en précisant
	  * le chemin du fichier de langue dans lequel chercher.
	  * @param $name Clé de l'élément dans le tableau des éléments de langue. */
	public function getFromFile($file, $key) {

		if(is_file($file)):
			include($file); # Inclure le fichier précisé
			# On tente de renvoyer la valeur de la clé précisée
			if(isset($_L) && is_array($_L)):
				return (isset($_L[$key])) ? $_L[$key]:false;
			else:
				throw new Exception(get_class($this) . '::getFromFile() : There is no $_L array declared in ' . $file . '\'s scope.');
			endif;
		else:
			throw new Exception(get_class($this) . '::getFromFile() : Can\'t locate file ' . $file);
		endif;
	}

}
?>
