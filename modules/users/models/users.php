<?php
# * * * * * * * * * * * * * * * * * * * * * *
# MODELE PRINCIPAL DU MODULE " UTILISATEURS "
# * * * * * * * * * * * * * * * * * * * * * *
namespace FrenchCoders\Modules\Users;
use \PDO;
use \Exception;
use \session_statuts;
use FrenchCoders\Core\Utils;

/**
  * Modèle principal du module " Utilisateurs " pour
  * la gestion basique et les opérations sur les données
  * des utilisateurs. */
class Users {

	# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	# PROPRIETES
	# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *


	/**
     * Objet PDO, instance valide de la classe PDO.
     * @var PDO|null */
	protected $PDO;


	/**
     * Chaine de caractères - nom de la table gérée par le modèle
     * @var string|string */
	protected $table = 'users';


	/**
	 * Chemin de la page indiquée pour les redirection lorsque les autorisations de l'utilisateur
	 * sont inssufisants pour afficher la page demandée.
	 * @var string|null */
 	protected $redirection = NULL;
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * *
	# INITIALISATION
	# * * * * * * * *
	public function __construct($_PDO) {

		# INSTANCE DE PDO
		if($_PDO instanceof PDO):
			$this->PDO = $_PDO;
		else:
			throw new Exception(get_class($this) . ' __construct error : Invalid instance of PDO');
		endif;

	}


	# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	#					RELATIF A LA CONNEXION DE L'UTILISATEUR
	# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

	/** Renvoi le status de la connexion.
	 *  @return bool Renvoi true si l'utilisateur est connecté, false s'il ne l'est pas **/
	public function status() {
    	return isset($_SESSION['id']) && isset($_SESSION['username']) ? 1:0;
    }


	/**
	  * Traiter la tentative de connexion de l'utilisateur
	  * @param string $username Nom de l'utlisateur
	  * @param string password  Mot de passe de l'utilisateur
	  * @return bool Echec / Success */
	public function login($username, $password) {

        $username = trim($username);
 		$password = trim($password);

		if(empty($username) | empty($password)):
			throw new Exception(get_class($this) . '::login() : Error, empty param(s).');
		endif;
		# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -


		# * * * * * * * * * * * * * * * * * *
		# DONNEES DE L'UTILISATEUR CONCERNE
		# * * * * * * * * * * * * * * * * * *
		if(filter_var($username, FILTER_VALIDATE_EMAIL)):

			# TENTATIVE DE CONNEXION PAR ADRESSE E-MAIL
			$SQL = 'SELECT email, password FROM ' . $this->table .
				   ' WHERE email = :username AND active = :active';

		else:

			# TENTATIVE DE CONNEXION PAR NOM D'UTILISATEUR
			$SQL = 'SELECT username, password FROM ' . $this->table .
				   ' WHERE username = :username AND active = :active';
		endif;

		$r = $this->PDO->prepare($SQL);
		$r->bindValue(':username', $username, PDO::PARAM_INT);
		$r->bindValue(':active', 1, PDO::PARAM_INT);
		$d = $r->execute();
		# - - - - - - - - -
		$usersDatas = $r->fetchAll(PDO::FETCH_ASSOC);
		$r->closeCursor();
		# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -


		# * * * * * * * * * * * * * * * * * *
		# EXPLOITER LE RESULTAT DE LA REQUETE
		# * * * * * * * * * * * * * * * * * *
	    if($userDatas !== false):
			if(!empty($userDatas)):

				if(self::password($password, $userDatas[0]['password'])):

                    # CONNEXION EFFECTIVE DE L'UTILISATEUR
                    $_SESSION['id'] = $userDatas[0]['id'];
                    $_SESSION['username'] = $userDatas[0]['username'];
                    return 1;
                else:
                    return 0;
                endif;

            else:
				# INEXISTANT EN BASE DE DONNEES
                return false;
            endif;

        else:
		    return -1;
        endif;
 	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	# DECONNEXION DE L'UTILISATEUR
	# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *

	/**
	  * Déconnexion de l'utilisateur.
	  * @return bool Succès / Echec **/
 	public function logout() {
    	    if($this->status()):
				session_destroy();
				return true;
			endif;
			return false;
 	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	# ENREGISTREMENT D'UN NOUVEL UTILISATEUR
	# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	public function register($email, $username, $password, $avatar = 0) {

		# @TODO : Appliquer des regexp pour valider le format des données
		# transmises par l'utilisateur.
		$email = trim($email);
		$username = trim($username);
		$password = trim($password);
		$avatar = ($avatar == 0) ? $avatar:NULL;
		# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

		# * * * * * * * *
		# ENREGISTREMENT
		# * * * * * * * *


		# Vérification de la disponibilité du nom d'utilisateur.
		$SQL = 'SELECT COUNT(username) AS nbr FROM ' . $this->table . ' WHERE username = :username';
		$r = $this->PDO->prepare($SQL);
		$r->bindValue(':username', $username, PDO::PARAM_INT);
		$d = $r->execute();
		$res = $r->fetch(PDO::FETCH_ASSOC);
		$r->closeCursor();
		# - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

		if(!$res['nbr']):

			# NOM D'UTILISATEUR DISPONIBLE, ENREGISTREMENT EFFECTIF
			$SQL = 'INSERT INTO ' . $this->table . ' VALUES(' . NULL . ',  :email, :username, :password, :avatar, :timestamp)';
			$r = $this->PDO->prepare($SQL);
			$r->bindValue(':email', $email, PDO::PARAM_STR);
			$r->bindValue(':username', $username, PDO::PARAM_STR);
			$r->bindValue(':password', Users::password($password), PDO::PARAM_STR);
			$r->bindValue(':avatar', $avatar, PDO::PARAM_STR);
			$r->bindValue(':timestamp', $timestamp, PDO::PARAM_STR);
			$d = $r->execute();
			$r->closeCursor();
			return $d;

		else:
			# NOM D'UTILISATEUR INDISPONIBLE
			return -1;
		endif;
	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	# -> SUPPORT BASIQUE DU NIVEAU DE PERMISSONS
	# @NOTE : Le système de permissions implémentés dans la classe est basique.
	# Il repose sur une seule colonne ' superuser ' dans la table et permet
	# de savoir si oui ou non l'utilisateur a les droits d'administration complets
	# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *


	/** Renvoit le status des droits d'administration.
	  * @return bool Renvoi true si l'utilisateur a les droits d'administration, false sinon. */
 	public function isAdmin() {

        if($this->status()):
             return $this->fetch($_SESSION['id'], ['superuser'])['superuser'];
        else:
			# L'utilisateur n'est pas connecté donc n'a en aucun cas les droits d'admins
			return false;
		endif;
	}



	/** Définir la page de redirection vers laquelle diriger l'utilisateur
	  * si l'utilisateur n'a pas les permissions suffisantes pour afficher
	  * la page.
	  * @param string|int $redirection Passer directement l'URL de redirection ou d'utiliser l'acesseur de la  classe dédié.
	  * @return void */
 	public function setRedirection($redirection) {

		$redirection = trim($redirection);
   		if(!empty($redirection)):
			$this->redirection = $redirection;
		endif;
	}


    /**
      * Forcer le redirection vers une page d'erreur si ce dernier n'est pas connecté.
      * Ceci permet de réserver certains espace de la plateforme aux utilisateurs
      * enregistrés et connectés.
      * La page d'erreur utilisée est la même que celle définie par la méthode setRedirection(),
      * et vers laquelle redirige la méthode isAdminWithRedirection() pour indiquer à l'utilisateur
      * une insuffisance de permission. Cette page doit faire la différence entre les deux types
      * d'erreur.
      * @return void */
    public function needLoggedIn() {

        if(!empty($this->redirection)):
            if(!isset($_SESSION['id']) || !isset($_SESSION['username'])):
                # L'utilisateur n'est pas connecté, on force la redirection
                header('Location:' . $this->redirection);
                exit();
            else:
                # L'utilisateur est connecté, il n'y a rien à faire
                return false;
            endif;
        else:
            throw new Exception('You\'ve to call the method setRedirection() to define the redirection page before calling needLoggedIn().');
        endif;

    }


	/**
	  * Rediriger proprement l'utilisateur si les permissions
	  * pour afficher la page demandée sont insufisante.
	  * @return void **/
	public function isAdminWithRedirection($redirection = 0) {

		if(!$redirection && $this->redirection == NULL):
        		return false;
		else:
   			$redirection = (($redirection != 0) ? $redirection:$this->redirection);
			if(!$this->isAdmin()):
			    # Insuffisance de permissions, redirection
      			header('Location: ' . $redirection);
      			exit(); # fin d'éxécution
   			endif;
		endif;
	}
	# = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =


	# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	# INCLASSABLESs
	# * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *


	/**
	  * Hashage des mots de passe et vérifications des hash.
	  * @param string $original Mot de passe fourni par l'utilisateur.
	  * @param string $toVerify Hash du mot de passe avec lequel vérifier la validité du mot de passe */
	public static function password($original, $toVerify = 0) {
		# @NOTE : l'interêt de détacher ces fonctionnalités dans une méthode statique qui
		# lui est propre est de pouvoir utiliser les mêmes algos de hashage pour tout ce qui
		# concerne les mots de passe des utilisateurs.
		if($toVerify === 0):
		  	# Générer le hash du mot de passe.
		  	return password_hash($original, PASSWORD_BCRYPT);

        else:
			# Vérifier que le mot de passe correspond au hash indiqué.
			return password_verify($original, $toVerify);
		endif;
    }
    # = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = = =

}
?>
