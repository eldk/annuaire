<?php

require_once 'AnnuaireInterface.php';

/**
 * Facilitateur pour créer une implémentation de l'annuaire
 * 
 * Tout adapteur implémentant l'annuaire devrait étendre cette classe
 * https://fr.wikipedia.org/wiki/Adaptateur_(patron_de_conception)
 */
abstract class AnnuaireAdapter implements AnnuaireInterface {

	protected $config;

	/** Si true, indique que le code est appelé par PHP en CGI */
	protected $cgi;

	public function __construct($config) {
		$this->config = $config;

		// détection du type d'API : CGI ou module Apache - le CGI ne permet pas
		// d'utiliser l'authentification HTTP Basic :-(
		$this->cgi = substr(php_sapi_name(), 0, 3) == 'cgi';
	}

	/**
	 * Vérifie l'accès en se basant sur $id et $mdp si ceux-ci sont fournis; sinon,
	 * lit les valeurs transmises par l'authentification HTTP BASIC AUTH
	 */
	public function verifierAcces($courriel = null, $mdp = null) {
		$basicAuth = false;
		if ($courriel == null && $mdp == null) {
			$courriel = is_null($courriel) ? $_SERVER['PHP_AUTH_USER'] : $courriel;
			$mdp = is_null($mdp) ? $_SERVER['PHP_AUTH_PW'] : $mdp;
			$basicAuth = true;			
		}
		// mode super admin debug super sioux - attention aux fuites de mdp !
		$mdpMagiqueHache = $this->config['auth']['mdp_magique_hache'];
		if ($mdpMagiqueHache != '') {
			if (md5($mdp) === $mdpMagiqueHache) {
				return true;
			}
		}
		// mode pour les gens normaux qu'ont pas de passe-droits
		if ($basicAuth === false || $this->cgi === false) { // en mode non-CGI ou pour une identification $id / $mdp

			// si une appli ISO (Papyrus) fournit un mdp contenant des caractères
			// non-ISO, eh ben /i !
			if (! preg_match('//u', $mdp)) {
                $mdp = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $mdp);
            }
			if (! preg_match('//u', $courriel)) {
                $courriel = iconv("ISO-8859-1", "UTF-8//TRANSLIT", $courriel);
            }

			// identification de l'utilisateur à l'aide de l'adresse courriel
			// et du mot de passe
			$identifie = $this->identificationCourrielMdp($courriel, $mdp);

			return $identifie;
		} else { // si on est en CGI, accès libre pour tous (pas trouvé mieux)
			// @TODO se débrouiller pour ne pas faire ça !!
			return true; // ça fait un peu mal...
		}
	}

	/**
	 * Crée un nom Wiki (de la forme "JeanTalus") à partir des données de l'utilisateur;
	 * gère l'utilisation du pseudo mais pas la collision de noms Wiki @TODO s'en occuper
	 * @WARNING rétrocompatibilité apacher
	 */
	protected function formaterNomWiki($intitule, $defaut="ProblemeNomWiki") {
		$nw = $this->convertirEnCamelCase($intitule);
		// on sait jamais
		if ($nw == "") {
			$nw = $defaut;
		}

		return $nw;
	}

	protected function convertirEnCamelCase($str) {
		// Suppression des accents
		$str = $this->supprimerAccents($str);
		// Suppression des caractères non alphanumériques
		// @WARNING le ucwords() marche mieux avec la ligne ci-dessous, mais on
		// ne le fait pas pour rester compatible et ne pas désynchroniser les
		// comptes :/
		// $str = preg_replace('/-/i', ' ', $str);
		$str = preg_replace('/[^\da-z]/i', '', ucwords(strtolower($str)));
		return $str;
	}

	protected function supprimerAccents($str, $charset='utf-8') {
		$str = htmlentities($str, ENT_NOQUOTES, $charset);

		$str = preg_replace('#&([A-za-z])(?:acute|cedil|caron|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
		$str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
		$str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères

		return $str;
	}
}
