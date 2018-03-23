<?php
namespace temple;
/**
 * vérifie les infos soumise au post
 * en fonction des contraintes SQL
 */
class JasonLock {

    protected $_Objet;
    protected $_erreur_liste;


    public function __construct(\nabucco\Trinity $Objet) {
        $this->_Objet = $Objet ;
        $this->_erreur_liste = array();
    }

    public function erreur_liste(){
        return $this->_erreur_liste;
    }
    
    public function erreur(){
        if ($this->_erreur_liste) return TRUE;
        else return FALSE;
    }
    
    /**
     * check each inputs
     * @param string $attribut : name of the field/attribut
     * @param type $prefixe : when prefix needed for input name
     * @param type $numeroAttribut : used when inputs are numerated.
     * exemple : $_REQUEST[nom1],$_REQUEST[nom2]. $numeroAttribut is then used in order to
     * find the rigth attribut where to find the formats for verification
     * @return boolean
     */

    public function check_input ($attribut,$prefixe='',$numeroAttribut=NULL){
        $erreur = FALSE;

    /*
    if ($attribut!=''){
    $attribut = $this->_Objet->listeNomAttributs()[$attribut];}
    on utilise alors $numeroAttribut pour repérer l'attribut depuis lequel on doit prendre les formats à vérifier
    */
        
    if ( !$this->_Objet->listeEmptynessAttributs()[$attribut] && empty ($_REQUEST[$prefixe.$attribut]) ){
        $this->_erreur_liste[$attribut] = 'champ non vide';
        $erreur = TRUE;
    }
//string
    else if ($this->_Objet->listeFormatAttributs()[$attribut] =='varchar' && $this->_Objet->listeSizeAttributs()[$attribut] !=0 && mb_strlen($_REQUEST[$prefixe.$attribut],'UTF-8')>$this->_Objet->listeSizeAttributs()[$attribut]){
        $this->_erreur_liste[$attribut] = 'champ de moins de '.$this->_Objet->listeSizeAttributs()[$attribut].' caracteres';
        $erreur = TRUE;
    }
//numeric    
    else if ($this->_Objet->listeFormatAttributs()[$attribut] =='int' && !is_numeric($_REQUEST[$prefixe.$attribut])){
        $this->_erreur_liste[$attribut] = 'champ est numeric';
        $erreur = TRUE;
    }
    else if ($this->_Objet->listeFormatAttributs()[$attribut] =='tinyint' && !is_numeric($_REQUEST[$prefixe.$attribut])){
        $this->_erreur_liste[$attribut] = 'champ est numeric';
        $erreur = TRUE;
    }
    else if ($this->_Objet->listeFormatAttributs()[$attribut] =='decimal' && !is_numeric($_REQUEST[$prefixe.$attribut])){
        $this->_erreur_liste[$attribut] = 'champ est numeric';
        $erreur = TRUE;
    }
    else if ($this->_Objet->listeFormatAttributs()[$attribut] =='int unsigned' && !is_numeric($_REQUEST[$prefixe.$attribut]) || $_REQUEST[$prefixe.$attribut]<0){
         $this->_erreur_liste[$attribut] = 'champ est numeric et >=0 ';
         $erreur = TRUE;
    }
    else if ($this->_Objet->listeFormatAttributs()[$attribut] =='tinyint unsigned' && !is_numeric($_REQUEST[$prefixe.$attribut]) || $_REQUEST[$prefixe.$attribut]<0){
         $this->_erreur_liste[$attribut] = 'champ est numeric et >=0 ';
         $erreur = TRUE;
    }
    else if ($this->_Objet->listeFormatAttributs()[$attribut] =='decimal unsigned' && !is_numeric($_REQUEST[$prefixe.$attribut]) || $_REQUEST[$prefixe.$attribut]<0){
         $this->_erreur_liste[$attribut] = 'champ est numeric et >=0 '; 
         $erreur = TRUE;
    }
//time
    else if ($this->_Objet->listeFormatAttributs()[$attribut] =='date' && !$this::verifier_format_date($_REQUEST[$prefixe.$attribut])){
         $this->_erreur_liste[$attribut] = 'champ au format yyyy-mm-dd ';
         $erreur = TRUE;
    }
    else if ($this->_Objet->listeFormatAttributs()[$attribut] =='time' && !$this::verifier_format_time($_REQUEST[$prefixe.$attribut])){
         $this->_erreur_liste[$attribut] = 'champ au format hh:mm:ss ';  
         $erreur = TRUE;
    }    

    return $erreur;
}

//methodes static
static public function verifier_format_date($dateVerif){

    if ($dateVerif=='0000-00-00')
        return TRUE;
 
    if (strlen($dateVerif)!=10)
        return FALSE;

    if ($dateVerif[4]!='-')
        return FALSE;
    if ($dateVerif[7]!='-')
        return FALSE;

    $mois = $dateVerif[5].$dateVerif[6];
    $jour = $dateVerif[8].$dateVerif[9];
    $annee = $dateVerif[0].$dateVerif[1].$dateVerif[2].$dateVerif[3];
 
    if (!is_numeric($annee) || !is_numeric($mois) || !is_numeric($jour) )
        return FALSE;
    if (!checkdate($mois, $jour, $annee))
        return FALSE;

    return TRUE;
}

static public function verifier_format_time($heureVerif){

 if ($heureVerif=='00:00:00' || $heureVerif=='00:00') return TRUE;
 
 if (strlen($heureVerif)!=8) return FALSE;

 if ($heureVerif[2]!=':') return FALSE;
 if ($heureVerif[5]!=':') return FALSE;

 $heure = $heureVerif[0].$heureVerif[1];
 $min = $heureVerif[3].$heureVerif[4];
 $sec = $heureVerif[6].$heureVerif[7];

 if ($heure < 0 || $heure > 23 || !is_numeric($heure)) return FALSE;
 if ($min < 0 || $min > 59 || !is_numeric($min)) return FALSE;
 if ($sec < 0 || $sec > 59 || !is_numeric($sec)) return FALSE;

 return TRUE;
}

}
