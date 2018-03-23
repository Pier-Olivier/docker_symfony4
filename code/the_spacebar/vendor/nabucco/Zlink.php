<?php
namespace nabucco;

/**
 * enable log in application and save them in Mysql
 * exemple :
 * new \nabucco\Zlink('titre', __FILE__ . '(l: ' . __LINE__ . ')', __NAMESPACE__ . '/' .  __CLASS__ . '/' . __TRAIT__ . '->' . __METHOD__, 'objet');
 */

class Zlink
{
    protected $sTable;
    protected $oMorpheus;

    public function __construct($sTitre, $sDescription = null, $sSource = null, $sObjet = null, $sCategorie = 'categorie', $bCallLog = true)
    {
    if (\DEBUG_MODE) {
        $this->sTable = 'zlink';
        $this->oMorpheus = new Morpheus($this->sTable);

        if ($bCallLog) {
            $this->log($sTitre, $sDescription, $sSource, $sObjet, $sCategorie);
        }
    }
    }

    public function __destruct()
    {
        unset($this->oMorpheus);
    }

    /**
     * insert log in SQL.zlink
     * @param string $sCategorie
     * @param string $sTitre : small message
     * @param string $sDescription : longer than titre
     * @return boolean
     */
    public function log($sTitre, $sDescription = null, $sSource = null, $sObjet = null, $sCategorie = 'general')
    {
    if (\DEBUG_MODE) {

        if (is_array($sDescription)) {
            $sDescription = $this->array2stringRecursivly($sDescription);
        }

        $aData = array(
        0, date('Y-m-d H:i:s'), $sSource, $sObjet,
        $sCategorie, $sTitre, $sDescription
        );
        $oTrinity = new Trinity($this->sTable, $aData);
        $this->oMorpheus->insertObjet($oTrinity, true);
        unset($oTrinity);
        return true;
    }
    }
    /**
     * sanitize array and convert in string
     * @param array $array
     * @return null
     * */
    public function array2stringRecursivly(&$array)
    {
        $sString = '';
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $cle = ' [vide] ';
                if (is_string($key)) {
                    $cle =' [[' . $key . ']] ==>>';
                }
                $sString .= addslashes(strip_tags(trim($cle  . $value))) . PHP_EOL;
            } elseif (is_array($value)) {
                $this->sanitizeRecursivly($value);
            }
        }
        return $sString;
    }

}
