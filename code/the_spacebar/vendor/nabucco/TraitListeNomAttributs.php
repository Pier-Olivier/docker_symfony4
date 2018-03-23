<?php
namespace nabucco;

/**
 * trait commun a trinity et morpheus
 */
    trait TraitListeNomAttributs
    {

    protected $_table;
    protected $_listeNomAttributs = array();

    /**
     * set table name
     * @param string $table
     */
    public function set_table($table){
        if (is_string($table)){
            $this->_table = '`'.$table.'`';
        }
        else{
            throw new \ErrorException ('<span class="alerte">!!!! '.__CLASS__.' ->set_table() attend un string !!!!</span>');
        }
    }

    /**
     * get table name
     * @return string : name of the table
     */
    public function table(){
        return $this->_table;
    }

    public function listeNomAttributs($rang='array'){
     if ($rang=='array')
        return $this->_listeNomAttributs;
     else {
        $rang = (int) $rang;
        return $this->_listeNomAttributs[$rang];
     }
    }

}
