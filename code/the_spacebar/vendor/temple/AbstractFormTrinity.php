<?php
namespace temple;

abstract class AbstractFormTrinity extends \temple\AbstractForm {
    
    use \temple\TraitBDD;

    protected $_listeAttribut;
    protected $_Trinity;

    public function __construct (\nabucco\Trinity $Trinity)
    {
        $this->genererPDO();//\temple\TraitBDD;
        $this->_Trinity = $Trinity;
    }

    //vérifie que les données soumises par champs (Input, Select ...) sont conformes aux contraintes (SQL)
    public function set_erreur($prefixe='')
    {
        $JasonLock = new JasonLock($this->_Trinity);
        foreach ($this->_Trinity->listeNomAttributs() as $attribut) {
            if (isset($_REQUEST[$prefixe.$attribut])){
                if (!$JasonLock->check_input($attribut,$prefixe)) {
                    $_REQUEST[$prefixe.$attribut] = strip_tags($_REQUEST[$prefixe.$attribut]);
                }
            }
        }
        $this->_liste_erreur = $JasonLock->erreur_liste();
    }

    public function set_Trinity($Trinity)
    {
        $this->_Trinity = $Trinity;
    }

    /**
     * get the Trinity value
     * @return string
     */
    public function getValue($sAttribut)
    {
        return $this->_Trinity->{$sAttribut}();
    }

}
