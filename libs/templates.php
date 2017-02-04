<?php
# * * * * * * * * * *
# MOTEUR DE TEMPLATES
# * * * * * * * * * *
namespace FrenchCoders\Core;
use \Exception;

/**
  * Moteur de template devant sous-tendre tous les frontend du programme. */
class Templates {

	# * * * * * *
	# PROPRIETES
	# * * * * * *

	/**
 	  * Variables disponibles dans le scope du template
	  * sous forme d'association clé valeur.
	  * @var array|array $vars */
	private $vars = []; # tableau des variables

	/**
	  * Variables disponible dans le scope de tous les templates
	  * sous forme d'association clé valeur. */
	private static $globals = [];

	/**
	  * Tableau contenant les chemins des fichiers de templates à traiter dans cette instance.
	  * @var array|array $files */
	private $files = []; # fichier de template en cours de traitement

	/**
	  * Instance de la classe Langs pour l'accès aux entrées de langues
	  * @var Langs|null $LangObj */
	private $langObj;

	/**
	  * Nom de fichier de cache pour le template a indiqué pour la génération.
	  * @param int|string */
	private $cacheFileName = 0;

	/**
	  * Indiquer qu'il est nécéssaire de charger le fichier de template depuis le cache, car les conditions
	  * sont remplies pour le faire et qu'un fichier de cache semble exister pour l'instance.
	  * @param int */
	private $loadFromCache = 0;
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * *
	# INITIALISATION
	# * * * * * * * *

	/**
	  * Nouvelle instance de la classe.
	  * @param array|string $file Nom de fichier sous forme d'une chaine de caractère ou tableau de noms de fichiers.
	  * @return void */
	public function __construct($file, $cacheFilename = 0) {

		$this->cacheFileName = $cacheFilename; # @NOTE : Si précisé et si le cache est actif, alors il sera tenté
											   # le chargement des données du template depuis le cache.

		# L'INSTANCE POSSEDE-ELLE UN FICHIER DE CACHE ?
		$supposedCacheFilePath = TEMPLATES_CACHE_DIR . $this->cacheFileName;
		$hasCacheFile = is_file($supposedCacheFilePath);
		# - - - - - - - - - - -

		if((!TEMPLATES_CACHE && !$this->cacheFileName) || !$hasCacheFile):

			# * * * * * * * * * * * * * * * * *
			# GENERATION STANDARD DU TEMPLATE
			# * * * * * * * * * * * * * * * * *

			# Prise en charge de l'instance de la classe Langs propre à la configuration de la plateforme
			# pour gérer proprement l'internationalisation dans les templates.
			global $_Lang;
			$this->LangObj = $_Lang;
			# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

			# CHEMINS DE FICHIERS
			if(is_string($file) && is_file($file)):
	            array_push($this->files, $file); # n'utiliser qu'un seul fichier.
	    	elseif(is_array($file)):
	        	# Utiliser plusieurs fichiers.
	            foreach($file as $v):
	                if(is_file($v)):
	                    array_push($this->files, $v);
	                endif;
				endforeach;
			else:
				throw new Exception('Template : __construct() - Invalid $file argument!');
			endif;

			# DEBUT DE LA TAMPORISATION
			ob_start(array($this, '_parse'));;

		else:
			# * * * * * * * * * * * * * *
			# CHARGEMENT DEPUIS LE CACHE
			# * * * * * * * * * * * * * *
			$this->loadFromCache = 1;
		endif;
    }
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * * * * * * * * * * * * * * * * *
	# VALEURS ACCESSIBLES DANS LE SCOPE DU TEMPLATE
	# * * * * * * * * * * * * * * * * * * * * * * *

    /**
      * Préciser une nouvelle valeur accessible dans le scope du template.
      * @param string $name Nom de la future variable contenant la valeur.
	  * @param string $value Valeur de la future variable
	  * @return Templates  */
    public function set($name, $value) {
 	    $this->vars[trim($name)] = $value;
        return $this;
    }


    /**
      * Préciser une nouvelle variable accessible dans le scope de tous les templates.
      * Ce mécanime permet de traiter proprement le cas de variables propres au projet
      * qu'il est usuel de rendre disponible sur les templates.
      * Pour ne pas compromettre un bon fonctionnement par réecriture, les variables
      * de templates " globales " ayant le même nom que celles précisées par la méthode
      * Templates::set() seront écrasée.
      * @param string $name Nom de la variable
      * @param sting Valeur
      * @return Templates */
    public static function scope($name, $value) {
        self::$globals[trim($name)] = $value;
    }
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * * * * * * * * * * * * * * * * * *
	# CALLBACK LORS DES OPERATIONS DE REMPLACEMENTS
	# * * * * * * * * * * * * * * * * * * * * * * * *

    /**
	  * Callback pour le remplacement des tag de langue dans les fichiers de template.
	  * @param $matches Tableau de valeurs matchées par preg_replace_callback
	  * @return Valeur de l'entrée de langue correspondant à la clé matchée */
    private function _lang($matches) {
        # @NOTE : Retourner la valeur correspondante au tag à pour effet
        # le remplacement du tag par sa valeur lorsque utilisé en fonction de
        # de retour lors de l'appel à preg_replace_callback()
        if($this->LangObj->get($matches[1]) !== false):
			return $this->LangObj->get($matches[1]);
		else: return '[Lang element not found]'; endif;
    }


    /**
     * Callback pour le remplacement des tags d'URL dans les fichier de template.
     * @param $matches Tableau des valeurs matchées par preg_replace_callback
     * @return URL générée depuis la classe Modules. */
    public function _url($matches) {
		global $_Modules;
		# @FIX : Remplacer les simple quotes par des doubles quotes pour
		# dans les données de paramètres GET pour permettre le parsage en temps
		# que données JSON -
		if(isset($matches[5])):
			$matches[5] = str_replace("'", '"', $matches[5]);
		endif;

		return $_Modules->url($matches[1], # Nom du module
							  $matches[2], # Bool admin ou client-side
							  (isset($matches[3]) ? $matches[3]:''), # Contexte (optionel)
							  (isset($matches[4]) ? $matches[4]:0), # Prise en charge du subcontext DELETE (opt)
							  (isset($matches[5]) ? $matches[5]:0), # Id d'un élément précis (optionel)
							  (isset($matches[6]) ? json_decode($matches[6], true):[]) # Tableau de paramètres au format JSON (opt)
						  	 );
	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * * * * * * * * * *
	# PARSER LE CONTENU DU TEMPLATE
	# * * * * * * * * * * * * * * * *

    /**
      * Callback appellé à la libération du tampon de façon à pouvoir parser des éléments
	  * spécifiques dans les templates.
	  * @param string $buffer Tampon sous forme d'une chaine de caractères
	  * @return string Tampon sous forme d'une chaine de caractères traité par le parseur. */
    public function _parse($buffer) {

		# * * * * * * * * * * * * * * * * * * * * * * *
		# TRAITEMENT DES TAGS SPECIFIQUES AUX TEMPLATES
		# * * * * * * * * * * * * * * * * * * * * * * *
        $langPattern = '#_l\(([a-z0-9-_]+)\)#isU'; # pattern d'un élément de langue

        # Pattern d'un élément d'url
        $urlPattern = '#_url\( {0,}'
					. '([a-z0-9-_]+) {0,}, {0,}' # Nom du module
					. '([0-9]+)' # Bool admin ou client-side
					. '(?: {0,}, {0,}([a-z0-9-_]+)){0,1}' # Contexte (optionel)
					. '(?: {0,}, {0,}([0-9]+)){0,1}' # Prise en charge subcontext DELETE
					. '(?: {0,}, {0,}([0-9]+)){0,1}' # Id d'un élément précis (optionel)
					. '(?: {0,}, {0,}(\{(?:.+)\})){0,1}' # Tableau de paramtres au format JSON
					. ' {0,}\)#isU';

		# Capture et remplacements
		$buffer = preg_replace_callback($langPattern, [$this, '_lang'], $buffer); #  _l(name)
		$buffer = preg_replace_callback($urlPattern, [$this, '_url'], $buffer);

		# SI LES CONDITIONS SONT REMPLIES, ON MET EN CACHE LES ELEMENTS GENERES
		# POUR MNIMISER LE TEMPS DE GENERATION DES PAGES
		if(TEMPLATES_CACHE && $this->cacheFileName !== 0 && $this->loadFromCache == 0):
			$this->setCache($buffer); # déclancher la mise en cache des données parsées
		endif;
		return $buffer;
	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * * * * * *
	# COMPOSITION DU TEMPLATE
	# * * * * * * * * * * * *

	/**
	  * Opère le rendu du template représenté par l'instance.
	  * @TODO Revoir le système de l'overwrite pour qu'il soit défini en statique et le même pour toutes
	  * les instances via le fichier config.ini de la librairie templates.
	  * */
	public function render() {

		if(!TEMPLATES_CACHE || !$this->cacheFileName || !$this->loadFromCache):

			# * * * * * * * * * * * *
			# GENERATION DU TEMPLATE
			# * * * * * * * * * * * *

			# Variables calquées sur les données prévues pour être accessibles aux templates
			extract(self::$globals);
			extract($this->vars);

			# Exploiter tous les fichiers dans l'ordre des déclarations
			# On décompose le chemin du template pour ne garder que le nom de fichers
			foreach($this->files as $v):

				$parts = explode('/', $v);
				$tplName = $parts[count($parts)-1];
				$excluded = ['header.tpl.php', 'footer.tpl.php', 'nav.tpl.php']; # fichiers exclus et ne pouvant pas être écrasés

				if(!in_array($tplName, $excluded)):

					# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
					# SUPPORT DE L'OVERWRITE DES FICHIERS DE TEMPLATES PREDEFINIS
					# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
					$supposed = 'templates/overwrite/' . $tplName;
					if(is_file($supposed)):
						# Un fichier de template overwrite le fichier original
						include($supposed);
					else:
						# Sinon, on charge le fichier original
						include($v);
					endif;
				else:
					# On charge un fichier de structure (header, footer, nav..)
					include($v);
				endif;
			endforeach;

			# @NOTE : Après l'inclusion de tous les fichiers, on libère le tampon
			# ce qui a pour effet d'appeller la fonction de callback Templates::_parse()
			# pour déclancher les opérations de remplacement.
			ob_end_flush();

		else:
			# * * * * * * * * * * * * * *
			# CHARGEMENT DEPUIS LE CACHE
			# * * * * * * * * * * * * * *
			echo $this->getCache();
		endif;
	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	/* * * * * * * * * * * * * * * * *
	 * MISE EN CACHE DES TEMPLATES
	 * * * * * * * * * * * * * * * * */

	/** Retourne le contenu d'un fichier de cache dans le fichier dont le nom est précisé par l'instance.
 	  * @return string Contenu du fichier de cache. */
	public function getCache() {

		if($this->cacheFileName !== 0):
			# Il est absolument nécéssaire qu'un nom de fichier de cache soit explicité
			$supposedToReadFile = TEMPLATES_CACHE_DIR . $this->cacheFileName;
			if(is_file($supposedToReadFile)):
				return file_get_contents($supposedToReadFile);
			endif;

		else:
			return 0;
		endif;

	}

	/** Mettre en cache le contenu précisé par $content dans le fichier précisé par la variable $content
	  * dans le fichier dont le nom est précisé par l'instance.
	  * @return bool Success / Echec */
	public function setCache($content) {

		if($this->cacheFileName):
			$supposedToWriteFile = TEMPLATES_CACHE_DIR . $this->cacheFileName;
			$handler = fopen($supposedToWriteFile, 'w');
			$d = fwrite($handler, $content); # écriture
			fclose($handler);
			return $d;
			
		else:
			return 0;
		endif;
	}
}
