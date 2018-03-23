<?php
namespace nabucco;
/**
 * trace user actions
 */
class Bane
{
    protected $sTable;
    protected $oMorpheus;

    public function __construct($sTitle = 'title', $sAction = 'actions', $sDescription = '')
    {
    if (\TRACE_MODE) {
        $this->sTable = 'bane';
        try {
            $this->oMorpheus = new Morpheus($this->sTable);
            $this->insert($sTitle, $sAction, $sDescription);
        } catch (\Exception $ex) {
            if (\DEBUG_MSSG) {
                echo $ex->getMessage();
                echo '<hr />';
                echo $ex->getTraceAsString();
                echo '<hr />';
            }
        }
    }
    }

    /**
     * insert log in SQL.bane
     * @param string $sUrl
     * @param string $sTitre : small message
     * @param string $sDescription : longer than titre
     * @return boolean
     */
    public function insert($sTitle, $sAction, $sDescription)
    {
        if (is_array($sDescription)) {
            $sDescription = $this->array2stringRecursivly($sDescription);
        }
        
        if (isset($_SESSION['id_personne'])) {
            $sId_personne = $_SESSION['id_personne'];
        } else {
            $sId_personne = 'no_id_personne';
        }

        $aData = array(
            0, $sId_personne, session_id(),
            $_SERVER['HTTP_REFERER'], $_SERVER['REQUEST_URI'], $sTitle, $sAction, $_SERVER['PATH_INFO'],
            $sDescription, date('Y-m-d H:i:s'), $_SERVER['HTTP_USER_AGENT']
        );

        try {
            $oTrinity = new Trinity($this->sTable, $aData);
            $this->oMorpheus->insertObjet($oTrinity);
        } catch (\PDOException $ex) {
            if (\DEBUG_MSSG) {
                echo $ex->getMessage();
                echo '<hr />';
                echo $ex->getTraceAsString();
                echo '<hr />';
            }
            return true;
        }
        unset($oTrinity);
        return true;
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
