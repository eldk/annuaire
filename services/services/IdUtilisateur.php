<?php
/**
* PHP Version 5
*
* @category  PHP
* @package   annuaire
* @author    aurelien <aurelien@tela-botanica.org>
* @copyright 2010 Tela-Botanica
* @license   http://www.cecill.info/licences/Licence_CeCILL_V2-fr.txt Licence CECILL
* @version   SVN: <svn_id>
* @link      /doc/annuaire/
*/

Class IdUtilisateur extends JRestService {

	public function getElement($uid){

	    $mail_utilisateur = $uid[0];
	    $id_annuaire = Config::get('annuaire_defaut');

	    $controleur = new AnnuaireControleur();
	    $id	= $controleur->ObtenirIdParMail($id_annuaire,$mail_utilisateur);

	    $this->envoyer($id);
	}
}
?>