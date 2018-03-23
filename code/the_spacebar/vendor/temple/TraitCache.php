<?php
/*
 * Gestion de la mise en cache de
 * page html dynamique en page statiques
 */
namespace temple;

Trait TraitCache {

    protected $_cache_adresse;
    protected $_cache_var_adresse;

    /**
     * permit to decide if file in cache is still valide.
     * @param string $nom2fichier : file which must be analyzed
     * @param int $expire_time : time after which cache must be refreshed
     * @param string $repertoire2cache : directory where cache is stored
     * @return boolean
     */
    public function cache_valide($nom2fichier, $expire_time = null, $repertoire2cache='cache')
    {
        if (!\CACHE_HTLM){
            return false;
        }
        
        $this->_cache_adresse = $repertoire2cache.'/html_'.$nom2fichier.'.html';
        
        if(file_exists($this->_cache_adresse)){
            //si $expire_time est null le cache n'expire jamais
            if ($expire_time===null)
                $cache_valide = true;
            else// 3600 = une heure
                $cache_valide = filemtime($this->_cache_adresse) > time() - $expire_time;
                                
            if ($cache_valide){
                readfile($this->_cache_adresse);
                return true;
            }
            else
                return false;
        }
        else
            return false;
	
        return false;
    }
    
    /**
     * in order to record a dynamique generated html page by PHP
     * and make it a registered static page
     * @param Vue $Objet
     * @param String $methode : called by $Objet to generate the html page
     */
    public function cache_html_creation($Objet, $methode='html') {
            ob_start();

            $Objet->$methode(); 
            $page = ob_get_contents();
            ob_end_clean();

            if (\CACHE_HTLM)
                file_put_contents($this->_cache_adresse, $page);
            echo $page;
    }
 
    /**
     * permit to decide if var in cache is still valide.
     * @param string $nom2fichier
     * @param int $expire_time
     * @param string $repertoire2cache
     * @return boolean
     */
    public function cache_variable_check($nom2fichier,$expire_time = null,$repertoire2cache='cache'){
        if (!\CACHE_HTLM)
            return false;

        $this->_cache_var_adresse = $repertoire2cache.'/var_'.$nom2fichier.'.v';

        if(file_exists($this->_cache_var_adresse)){
            //si $expire_time est null le cache n'expire jamais
            if ($expire_time===null)
                $cache_valide = true;
            else// 3600 = une heure
                $cache_valide = filemtime($this->_cache_var_adresse) > time() - $expire_time;
                                
            if ($cache_valide){
                return true;
            }
            else
                return false;
        }
        else
            return false;

        return false;       

   }

    public function cache_variable_set($valeur) {
       $tmp = $this->_cache_var_adresse.uniqid('', true).'.tmp';
       file_put_contents($tmp, serialize($valeur), LOCK_EX);
       rename($tmp,$this->_cache_var_adresse);
    }

    public function cache_variable_get($nom2fichier,$repertoire2cache='cache') {
        return unserialize(file_get_contents($repertoire2cache.'/var_'.$nom2fichier.'.v'));
    }

}