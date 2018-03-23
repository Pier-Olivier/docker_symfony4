<?php
namespace temple;

trait TraitBDD {

    protected $_bdd;

    public function genererPDO()
    {
        $oBaze = new \PDO(\BASE_ADR, \BASE_LOG, \BASE_PWD);

        $oBaze->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $oBaze->exec("set names utf8");

        $this->_bdd = $oBaze;
    }
    
    public function get_BDD()
    {
        return $this->_bdd;
    }

    static function static_get_BDD()
    {
        $oBaze = new \PDO(\BASE_ADR, \BASE_LOG, \BASE_PWD);

        $oBaze->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $oBaze->exec("set names utf8");
        return $oBaze;
    }
}
