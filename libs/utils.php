<?php
# * * * * * * * * * * * * * * * * * *
# FONCTIONS UTILITAIRES INCLASSABLES
# * * * * * * * * * * * * * * * * * *
namespace FrenchCoders\Core;
use \Exception;

/**
  * Diverses fonctions utilitaires inclassables */
class Utils {


	# * * * * * * * * * * * * * * * * * * * * * * * *
	# INTERFACE ENTRE PHP ET LA CONSOLE DU NAVIGATEUR
	# * * * * * * * * * * * * * * * * * * * * * * * *

	/**
	  * Logger les entrées de déboguage dans la console du navigateur
	  * @param string $log Messsage à afficher dans la console du navigateur
	  * @param $toggle Variable de référence par l'activation / désactivation du message
	  * @return void */
	public static function log($log, $toggle = 0) {
		if(intval($toggle)):
			echo '<script> console.log("' . addslashes($log) . '")</script>' . PHP_EOL;
		endif;
	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	/**
	  * Upload d'une image par le biais d'un formulaire HTTP
	  * @param array $FILE tableau $_FILES['name'] envoyé sur $_POST et correspondant aux données de l'image
	  * @param int $MAX_SIZE Taille maximale de l'image
	  * @param string $output Chemin de dossier de sortie de l'image i
	  * @return string|bool Renvoit le chemin de l'image, ou false si une erreur survient */
	public static function imageUpload($FILE, $MAX_SIZE, $output) {

		$allowed_ext = array('jpg', 'jpeg', 'png', 'gif'); # extensions autorisées
		# @NOTE L'extension est déterminée en fonction du dernier point dans le nom du fichier.
		$extension_upload = strtolower(substr(strchr($FILE['name'], '.'), 1)); # extension de l'upload courrant

		if($FILE['size'] > $MAX_SIZE): # fichier trop lourd
			throw new Exception('Uploaded file\'s size is too big!');
		elseif($FILE['error'] > 0): # une erreur s'est produite
			throw new Exception('An error occured during file upload'); # une erreur s'est produit.
		else:
			# Aucune erreur ne s'est produite, on continue
			if(in_array($extension_upload, $allowed_ext)): # L'extension du fichier est correcte
				$filename = $output . md5(uniqid(rand(), true)) . '.' . $extension_upload; # Nom du fichier
				$d = move_uploaded_file($FILE['tmp_name'], $filename);
				# - - - - -
				return $filename;
			else:
				# une erreur s'est produite
				throw new Exception('Can\'t upload file \'cause of an invalid extension');
			endif;
		endif;
	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


   /**
	 * Ecriture propre d'un fichier sur le serveur.
	 * @param string $fileName Chemin du fichier à écrire
	 * @param string $dataToSave Données à écrire dans le fichier
	 * @return bool */
   public static function safeFileWrite($fileName, $dataToSave) {

		if($fp = fopen($fileName, 'w')):
			$startTime = microtime(TRUE);
			do {
				$canWrite = flock($fp, LOCK_EX);
				// If lock not obtained sleep for 0 - 100 milliseconds, to avoid collision and CPU load
				if(!$canWrite) usleep(round(rand(0, 100)*1000));
			}
			while ((!$canWrite)and((microtime(TRUE)-$startTime) < 5));

			 // file was locked so now we can store information
			 if($canWrite):
				 $d = fwrite($fp, $dataToSave);
				 flock($fp, LOCK_UN);
				 return true;
			endif;
			fclose($fp);
		endif;

  }
  # = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


  /**
	* Convertir un tableau PHP dans le format standard d'un fichier .ini
	* @param array $a Tableau original à convertir
	* @param array $parent [opt] tableau parent dans lequel ecrire les sous-sections précisées par $a */
	# Crédits goes to : http://stackoverflow.com/questions/17316873/php-array-to-a-ini-file
  public static function arrayToIni(array $a, array $parent = []) {
	$out = '';
	foreach ($a as $k => $v):
		if(is_array($v)):
			// subsection case
			// merge all the sections into one array...
			$sec = array_merge((array) $parent, (array) $k);
			// add section information to the output
			$out .= '[' . join('.', $sec) . ']' . PHP_EOL;
			//recursively traverse deeper
			$out .= arr2ini($v, $sec);
		else:
			// plain key->value case
			$out .= "$k=\"$v\"" . PHP_EOL;
		endif;
	endforeach;
	return $out;
  }

  /**
	* Réecriture des fichiers de configuration .ini
	* @param string $path Chemin du fichier à réecrire
	* @param string $section Section de la valeur à réecrire dans le fichier de configuration
	* @param string $key Clé de l'option à réecrire dans la section indiquée du fichier de configuration
	* @param string $value Nouvelle valeur de l'élément de configuration. */
  public static function writeIni($path, $section, $key, $value) {

	# Nouvelle valeur
	$datas = parse_ini_file($path, true);

	if(isset($datas[$section][$key])):
		$datas[$section][$key] = $value;

		# Formatter le tableau pour réecrire le fichier
		# - - - - - - - - - - - - - - - - - - - - - - -
		$content = '';
		foreach ($datas as $key=>$elem):

			$content .= "[".$key."]\n";

			foreach ($elem as $key2 => $elem2):

				if(is_array($elem2)):
					for($i=0;$i<count($elem2);$i++):
						$content .= $key2."[] = \"".$elem2[$i]."\"\n";
					endfor;

						   elseif(is_int($elem2)):
						 $content .= $key2." = " .$elem2. "\n";
						 else: $content .= $key2." = \"".$elem2."\"\n"; endif;
					endforeach;

		endforeach;

		 # Réecriture
		 $d = self::safefilerewrite($path, $content);

	endif;
	# - - - - - - - - - - -
	  return $d;
  }

  /**
	* Terminer le chemin d'un répertoire par le caractère '/' si nécéssaire
	* ce qui permet d'éviter des incohérences sur les chemins de fichiers
	* lors de concaténations hasardeuses.
	* @param string $path Chaine de caractères - chemin du répertoire
	* @return string Chemin du caractère avec l'assurance que le dernier caractère est '/' */
	public static function fpath($path) {
		$path = trim($path);
		if(!empty($path)):
			if($path[strlen($path)-1] == '/'):
				return $path;
				else:
					return $path . '/';
				endif;

		endif;
	}


 }
