<?php
namespace nabucco;

class ErrorTrinity extends \ErrorException {
}

/* seul les formats
* (unsigned) (small)(tiny)int, decimal, date,
* time, datetime, timestamp, text, varchar sont gérés
*/
class Trinity implements \Iterator {

use \temple\TraitBDD;
use \nabucco\TraitListeNomAttributs;
  
protected $_listeAttributs = array();

protected $_listeSizeAttributs = array();
protected $_listeFormatAttributs = array();
protected $_listeEmptynessAttributs = array();

//rempli en cas de table2objet_manager->selectConverted()
protected $_listeConvertedAttributs = array();

public function __construct(string $table, array $donnees, array $listeChampsAConvertir=array()){

    $this->set_table($table);

    $this->set_listeAttributs();

    $this->set_PropertiesAttributs();
    $this->hydrate($donnees);

    if (!empty($listeChampsAConvertir)){//rempli en cas de table2objet_manager->selectConverted()

        $n=1;
        foreach ($listeChampsAConvertir as $champConverti=>$champAConvertir ){
            //capture les valeurs d'origine
            $this->set_convertedAttribut($champAConvertir, $this->get($champAConvertir));
            //set de la nouvelle valeur de l'attribut
            $this->setAttributNoVerif($champAConvertir, $donnees[$champConverti.'_alias']);
            $n++;
        }
   }
}

protected function set_listeAttributs(){//on déclare les attributs et les protège
    foreach ($this->listeNomAttributs() as $attribut){
        $this->_listeAttributs[$attribut]=NULL;
    }
}

public function listeAttributs(){
    return $this->_listeAttributs;
}

public function listeFormatAttributs(){
    return $this->_listeFormatAttributs;
}

public function listeEmptynessAttributs(){
    return $this->_listeEmptynessAttributs;
}

public function hydrate(array $donnees){
    $n=0;
    foreach ($this->listeNomAttributs() as $attribut){
        $this->set($attribut, $donnees[$n]);
        $n++;
    }
}

public function listeSizeAttributs(){
  return $this->_listeSizeAttributs;
}

///----------------------serialiser

public function __sleep() {
    $liste_attributs = get_object_vars($this);
    return array_keys($liste_attributs);
}

///----------------------Iterator
    public function valid(){
        return array_key_exists(key($this->_listeAttributs), $this->_listeAttributs);
    }

    public function next(){
        next($this->_listeAttributs);
    }

    public function rewind(){
        reset($this->_listeAttributs);
        return $this;
    }

    public function key(){
        return key($this->_listeAttributs);
    }

    public function current(){
        if ($this->valid())
            return current($this->_listeAttributs);
        else
            return NULL;
    }

///-------------------SETTER et GETTER GENERIQUE


public function __call($attribut, $liste_valeur) {
       
    if (array_key_exists ($attribut,$this->_listeAttributs)) {
        
        return $this->_listeAttributs[$attribut];
 /*Pour le moment on ne permet pas d'attribut sur le __call type : eRST (cf table2objet)           
        if (count ($liste_valeur) == 0) {
            return $this->getAttribut($attribut);
        }
        else if (count ($liste_valeur) == 1) {
            return $this->getAttribut($attribut, $liste_valeur[0]);
        }
        else {
            throw new ErrorException ('<span class="alerte"> GETTER ('.$attribut.') de '.__CLASS__.'('.$this->_table.') ne comporte que 0 ou 1 attribut</span>');
        }
*/
    }
    else {
        throw new ErrorTrinity ('<span class="alerte">'.__CLASS__.'('.$this->_table.') ne comporte pas l\'attribut : '.$attribut.'</span>');
    }

}

public function __get($key) {
    throw new ErrorTrinity ('<span class="alerte">On en peut acceder aux attributs : de '.__CLASS__.'('.$this->_table.') ainsi.</span>');
}

public function __set($attribut, $valeur) {

    if (array_key_exists ($attribut,$this->_listeAttributs))
        $this->set($attribut, $valeur);
        
    else {
        throw new ErrorTrinity ('<span class="alerte">attribut : '.$attribut.' de '.__CLASS__.'('.$this->_table.') est invalide</span>');
    }
}
///----------------------------------------

public function verifierFormat($attribut, $valeur)
{
    $sFormatAttribut = $this->_listeFormatAttributs[$attribut];

    if ($valeur === null) {
        if ($this->_listeEmptynessAttributs[$attribut] == false ) {
            throw new ErrorTrinity ('<span class="alerte">!!!! '.$attribut.' of '.__CLASS__.'('.$this->_table.') cannot be null !!!!</span>');
            return false;
        } else {
            return true;
        }
    } elseif ($sFormatAttribut === 'text') {
        return true;
    } elseif ($sFormatAttribut === 'enum') {
        return true;
    } elseif ($sFormatAttribut === 'varchar') {
        if (!is_string($valeur) ) {
            throw new ErrorTrinity ('<span class="alerte">!!!! pb avec format string de '.$attribut.' de '.__CLASS__.'('.$this->_table.') !!!!</span>');
            return false;
        } elseif ( mb_strlen($valeur,'UTF-8') > $this->_listeSizeAttributs[$attribut] ) {
            throw new ErrorTrinity ('<span class="alerte">!!!! pb avec size ('.$this->_listeSizeAttributs[$attribut].') string de '.$attribut.' de '.__CLASS__.'('.$this->_table.') !!!!</span>');
            return false;
        }
        return true;
    } elseif ($sFormatAttribut ==='decimal' || $sFormatAttribut ==='int' || $sFormatAttribut ==='tinyint' || $sFormatAttribut ==='smallint') {
        if (!is_numeric($valeur)) {
            throw new ErrorTrinity ('<span class="alerte">!!!! pb avec format numeric de '.$attribut.' de '.__CLASS__.'('.$this->_table.') !!!!</span>');
            return false;
        } elseif ($sFormatAttribut ==='tinyint' && ($valeur>127 || $valeur<-128)) {
            throw new ErrorTrinity ('<span class="alerte">!!!! pb avec valeur tinyint de '.$attribut.' de '.__CLASS__.'('.$this->_table.') : 127 < -128 !!!!</span>');
            return false;
        }
        elseif ($sFormatAttribut ==='smallint' && ($valeur>32767 || $valeur<-32768)) {
            throw new ErrorTrinity ('<span class="alerte">!!!! pb avec valeur smallint de '.$attribut.' de '.__CLASS__.'('.$this->_table.') : 32767 < -32768 !!!!</span>');
            return false;
        }
        return true;
    } elseif ($sFormatAttribut ==='decimal unsigned' || $sFormatAttribut ==='int unsigned' || $sFormatAttribut ==='tinyint unsigned' || $sFormatAttribut ==='smallint unsigned') {
        if (!is_numeric($valeur)) {
            throw new ErrorTrinity ('<span class="alerte">!!!! pb avec format numeric de '.$attribut.' de '.__CLASS__.'('.$this->_table.') !!!!</span>');
            return false;
        }  elseif ($valeur<0) {
            throw new ErrorTrinity ('<span class="alerte">!!!! pb avec valeur de '.$attribut.' de '.__CLASS__.'('.$this->_table.') > 0 !!!!</span>');
            return false;
        } elseif ($sFormatAttribut ==='tinyint unsigned' && $valeur>255) {
            throw new ErrorTrinity ('<span class="alerte">!!!! pb avec valeur tinyint unsigned de '.$attribut.' de '.__CLASS__.'('.$this->_table.') < 255 !!!!</span>');
            return false;
        } elseif ($sFormatAttribut ==='smallint unsigned' && $valeur>65535) {
            throw new ErrorTrinity ('<span class="alerte">!!!! pb avec valeur smallint de '.$attribut.' de '.__CLASS__.'('.$this->_table.') < 65535!!!!</span>');
            return false;
        }
        return true;
    } elseif ($sFormatAttribut ==='time') {
        if (!$this->verifier_format_time($valeur)) {
            throw new ErrorTrinity ('<span class="alerte">!!!! pb avec format time de '.$attribut.' de '.__CLASS__.'('.$this->_table.') = 00:00:00 !!!!</span>');
            return false;
        }
        return true;
    } elseif ($sFormatAttribut ==='timestamp') {
        $aValeur = explode(' ',$valeur);
        if(!($this->verifier_format_date($aValeur[0]) && $this->verifier_format_time($aValeur[1])) ) {
            throw new ErrorTrinity ('<span class="alerte">!!!! pb avec format timestamp de '.$attribut.' de '.__CLASS__.'('.$this->_table.') = 0000-00-00 00:00:00 !!!!</span>');
            return false;
        }
    } elseif ($sFormatAttribut ==='date') {
        if (!$this->verifier_format_date($valeur)) {
            throw new ErrorTrinity ('<span class="alerte">!!!! pb avec format date de '.$attribut.' de '.__CLASS__.'('.$this->_table.') = 0000-00-00 !!!!</span>');
            return false;
        }
        return true;
    } elseif ($sFormatAttribut ==='datetime') {
        $aValeur = explode(' ',$valeur);
        if(!($this->verifier_format_date($aValeur[0]) && $this->verifier_format_time($aValeur[1])) ) {
            throw new ErrorTrinity ('<span class="alerte">!!!! pb avec format datetime de '.$attribut.' de '.__CLASS__.'('.$this->_table.') = 0000-00-00 00:00:00 !!!!</span>');
            return false;
        }
        return true;
    } else {//unknown format
        return false;
    }
}

public function set($attribut, $valeur)
{
    if ($this->verifierFormat($attribut, $valeur)) {
        $this->_listeAttributs[$attribut] = $valeur;
        return true;
    } else {
        throw new ErrorTrinity ('<span class="alerte">!!!! le format de '.$attribut.' de '.__CLASS__.'('.$this->_table.') non defini dans set_attribut !!!!</span>');
    }
}

public function id(){//retourne la valeur de l'id de l'objet
 return $this->_listeAttributs[$this->_listeNomAttributs[0]];    
}

public function get($attribut, $format=''){
 switch ($format) {
   case "e": echo $this->_listeAttributs[$attribut]; break;
   case "R": echo $this->_listeAttributs[$attribut].'<br />'; break;
   case "S": echo '<span class="'.$attribut.'">'.$this->_listeAttributs[$attribut].'</span>'; break;
   case "T": echo '<td>'.$this->_listeAttributs[$attribut].'</td>'; break;
   case "size": return $this->_listeSizeAttributs[$attribut]; break;
   default: return $this->_listeAttributs[$attribut];
 }
}


//utilisé quand table2objet_manager->selectConverted()
public function convertedAttribut($attribut=''){
    if ($attribut=='') return $this->_listeConvertedAttributs;
    else return $this->_listeConvertedAttributs[$attribut];
}

public function set_convertedAttribut($attribut,$valeur){
    $this->_listeConvertedAttributs[$attribut]=$valeur;
}

public function setAttributNoVerif($attribut, $valeur){
    $this->_listeAttributs[$attribut] = $valeur;
}
// FIN utilisé quand table2objet_manager->set_jointure()


//capture les valeurs dans SQL et intialise les protected array
final protected function set_PropertiesAttributs(){
    
    $cache_format    = \CACHE_MODEL_DISK_PATH . 'nabucco_' . $this->_table . 'listeFormatAttributs.k';
    $cache_size      = \CACHE_MODEL_DISK_PATH . 'nabucco_' . $this->_table . 'listeSizeAttributs.k';
    $cache_emptyness = \CACHE_MODEL_DISK_PATH . 'nabucco_'.$this->_table . 'listeEmptynessAttributs.k';
    $cache_nom       = \CACHE_MODEL_DISK_PATH . 'nabucco_'.$this->_table . 'listeNomAttributs.k';

    $cached = false;

//$expire = time() - 3600 ;
//&& filemtime($cache) > $expire
    if(\CACHE_MODEL==='disque' &&
            file_exists($cache_format) &&
            file_exists($cache_size) &&
            file_exists($cache_emptyness)
            && file_exists($cache_nom)) {
        $this->_listeFormatAttributs = unserialize(file_get_contents($cache_format));
        $this->_listeSizeAttributs = unserialize(file_get_contents($cache_size));
        $this->_listeEmptynessAttributs = unserialize(file_get_contents($cache_emptyness));
        $this->_listeNomAttributs = unserialize(file_get_contents($cache_nom));
    } elseif (\CACHE_MODEL==='redis'){
        try {
            $oRedis = new \Redis();
            $oRedis->connect(\REDIS_PHP);

            $s_cache_format = $oRedis->get($cache_format);
            $s_cache_size = $oRedis->get($cache_size);
            $s_cache_emptyness = $oRedis->get($cache_emptyness);
            $s_cache_nom = $oRedis->get($cache_nom);
            
            if ($s_cache_format &&
                $s_cache_size &&
                $s_cache_emptyness &&
                $s_cache_nom ){
                    
                    $this->_listeFormatAttributs = unserialize($s_cache_format);
                    $this->_listeSizeAttributs = unserialize($s_cache_size);
                    $this->_listeEmptynessAttributs = unserialize($s_cache_emptyness);
                    $this->_listeNomAttributs = unserialize($s_cache_nom);

                    $cached = true;
                }

            unset($oRedis);

        } catch (\Exception $exc) {
            echo $exc->getTraceAsString();
        }
        
    }
    
    if ($cached===false) {//on importe les infos de SQL
        $this->genererPDO();// methode from \temple\TraitBDD;
        $q = $this->_bdd->query('DESCRIBE '.$this->_table);
        $listeBrute = $q->fetchAll(\PDO::FETCH_ASSOC);
        $i=0;
        foreach($listeBrute as $listeBrute_propriete) {

            $this->_listeNomAttributs[] = $listeBrute_propriete['Field'];

            $format = explode('(',$listeBrute_propriete['Type']);
            $this->_listeFormatAttributs[$this->listeNomAttributs()[$i]] = $format[0];

            if($listeBrute_propriete['Type']!='date' && $listeBrute_propriete['Type']!='time' && 
               $listeBrute_propriete['Type']!='text' && $listeBrute_propriete['Type']!='datetime' && 
               $listeBrute_propriete['Type']!='timestamp' ){

                $sizeTempo[] = $format[1];  
            }
            else {
                $sizeTempo[] = NULL;
            }
            $i++;
        }
        $n = 0;

        foreach ($sizeTempo as $sizeBrute){
            $formatedSize = explode(')', $sizeBrute);

            if(isset($formatedSize[1])) {
                if($formatedSize[1] == ' unsigned'){
                    $this->_listeFormatAttributs[$this->listeNomAttributs()[$n]].=' unsigned';
                }
            }
            $this->_listeSizeAttributs[$this->listeNomAttributs()[$n]]=$formatedSize[0];
            $n++;
        }

        $i=0;//On récupère les informations concernant "la nullité" de l'attribut
        foreach($listeBrute as $listeBrute_propriete){
            if($listeBrute_propriete['Null']==='YES')
                $valeur_booleenne = TRUE;
            else
                $valeur_booleenne = FALSE;

            $this->_listeEmptynessAttributs[$this->listeNomAttributs()[$i]] = $valeur_booleenne;
            $i++;
        }
        if (\CACHE_MODEL==='disque'){
            file_put_contents($cache_nom, serialize($this->_listeNomAttributs));
            file_put_contents($cache_format, serialize($this->_listeFormatAttributs));
            file_put_contents($cache_size, serialize($this->_listeSizeAttributs));
            file_put_contents($cache_emptyness, serialize($this->_listeEmptynessAttributs));
        } elseif (\CACHE_MODEL==='redis'){
            try {
                $oRedis = new \Redis();
                $oRedis->connect(\REDIS_PHP);
                $oRedis->set($cache_nom, serialize($this->_listeNomAttributs));
                $oRedis->set($cache_format, serialize($this->_listeFormatAttributs));
                $oRedis->set($cache_size, serialize($this->_listeSizeAttributs));
                $oRedis->set($cache_emptyness, serialize($this->_listeEmptynessAttributs));
            } catch (\Exception $exc) {
                echo $exc->getTraceAsString();
            }
        }
    }
}

public function verifier_format_date($dateVerif){
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

public function verifier_format_time($heureVerif){

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
?>