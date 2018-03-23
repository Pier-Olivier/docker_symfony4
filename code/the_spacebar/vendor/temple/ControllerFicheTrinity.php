<?php
namespace temple;

/**
 * generic controller extended by most Fiche controller
 */

abstract class ControllerFicheTrinity extends \temple\AbstractController {
    
    /**
     * hydrate Trinity with corresponding $_REQUEST fields and insert with Morpheus
     * @param Trinity $Trinity
     * @param Morpheus $Morpheus
     * @param type $prefix : usefull when you mixe in the same page Fiche and Liste
     * @param bool $autoIncrement : true = Incrementation by SQL
     * @param type $returnMax : true = send the last Id inserted
     * @return boolean
     */
    protected function insertTrinity(\nabucco\Trinity $Trinity, \nabucco\Morpheus $Morpheus, string $prefix = '', bool $autoIncrement=false, bool $returnMax=false)
    {
        $Trinity->rewind();
        while ($key = $Trinity->key()) {//on compare les saisies et les attributs               
            if (isset($_REQUEST[$prefix.$key]) && $_REQUEST[$prefix.$key] != $Trinity->current()) {
                try {
                    $Trinity->$key = trim($_REQUEST[$prefix.$key]);
                } catch (\nabucco\ErrorTrinity $ex) {
                    echo '<p class="alerte">' . $ex->getMessage() . '</p>';
                    return false;
                }
            }
            $Trinity->next();
        }
        try {
            $aResult['Inserted'] = $Morpheus->insertObjet($Trinity, $autoIncrement, $returnMax);
            return  $aResult;

        } catch (\nabucco\ErrorMorpheus $ex) {
            if (\DEBUG_MSSG)
                echo '<p class="alerte">' . $ex->getMessage() . '</p>';
            $aResult['MorpheusProblem'] = true;
            return  $aResult;
        } catch (\PDOException $ex) {
            if (\DEBUG_MSSG)
                echo '<p class="alerte">' . $ex->getMessage() . '</p>';

            return $this->PDOMessageParser($ex->getMessage(), $Trinity);
        }
    }

    /**
     * enable to update db with $_REQUEST
     * @param Morpheus $Morpheus
     * @param Trinity $Trinity
     * @param string $prefix : useful when you mixte Fiche and Liste on the same page
     * 2 last arguments are not used for the moment : , $indice = '', $attributRang = 0
     * @param int $indice : useful when you have several Trinity on the page
     * @param int $attributRang : useful when you when update several trinity at the same time
     * @param bool $bIdUpdated : true when an PK has been updated by user input
     * @return boolean
     */
    protected function updateTrinity($Trinity, $Morpheus, $prefix = '', $bIdUpdated = false)
    {
        $aUpdate['id'] = $Trinity->id();
        try {
            $Trinity->rewind();
            if ($bIdUpdated) {//update of primary key by user input
                $key = $Trinity->key();
                $id = $_REQUEST[$prefix.$key];
                try {
                    $Morpheus->update($Trinity->id(), $key, trim($_REQUEST[$prefixe.$key]));
                    $aUpdate[$prefixe.$key] = $key;
                } catch (\nabucco\ErrorTrinity $ex) {
                    if (\DEBUG_MSSG)
                        echo '<p class="alerte">' . $ex->getMessage() . '</p>';
                    $aResult['TrinityProblem'] = $prefix.$key;
                    return  $aResult;
                }
                $Trinity->next();
            } else {
                $id = $Trinity->id();
            }
            while ($key = $Trinity->key()) {//on compare les saisies et les attributs
                if (isset($_REQUEST[$prefix.$key]) && $_REQUEST[$prefix.$key] != $Trinity->current()) {
                    try {
                        $Morpheus->update($id, $key, trim($_REQUEST[$prefixe.$key]));
                        $aUpdate[$prefixe.$key] = $Trinity->$key();
                    } catch (\nabucco\ErrorTrinity $ex) {
                        if (\DEBUG_MSSG)
                            echo '<p class="alerte">' . $ex->getMessage() . '</p>';
                        $aResult['TrinityProblem'] = $prefix.$key;
                        return  $aResult;
                    } catch (\PDOException $ex){
                        if (\DEBUG_MSSG)
                            echo '<p class="alerte">' . $ex->getMessage() . '</p>';

                        return $this->PDOMessageParser($ex->getMessage(), $Trinity);
                    }
                }
                $Trinity->next();
            }
            $aResult['Updated'] = $aUpdate;
            return  $aResult;
        } catch (\nabucco\ErrorMorpheus $ex) {
            if (\DEBUG_MSSG)
                echo '<p class="alerte">' . $ex->getMessage() . '</p>';
             $aResult['MorpheusProblem'] = true;
            return  $aResult;
        } catch (\PDOException $ex){
            if (\DEBUG_MSSG)
                echo '<p class="alerte">' . $ex->getMessage() . '</p>';

            return $this->PDOMessageParser($ex->getMessage(), $Trinity);
        }
    }

    /**
     * NOT USED FOR THE MOMENT
     * @param type $Morpheus
     * @param type $id
     * @return boolean
     */
    protected function deleterTrinity($Morpheus, $id) {
            try{
                $Morpheus->start_transaction();
                $Morpheus->deleteParId($id);
                $Morpheus->commit();
                return TRUE;

            } catch (\nabucco\ErrorMorpheus $ex) {
                $Morpheus->rollback();
                echo '<p class="alerte">' . $ex->getMessage() . '</p>';
            } catch (\nabucco\ErrorTrinity $ex) {
                $Morpheus->rollback();
                echo '<p class="alerte">' . $ex->getMessage() . '</p>';
            } catch (\PDOException $ex){
                echo '<p class="alerte">' . $ex->getMessage() . '</p>';
            }
    }

    /**
     * Parse error message sent by PDO in order to be used by the controller
     * @param string $sPDOMessage
     * @return array
     */
    public function PDOMessageParser($sPDOMessage, $Trinity)
    {
        //check no Duplicate entry
        if (mb_substr($sPDOMessage, -6, 5) == \UNIQUE_ENDING) {
            $iPositionDuplicate = mb_strpos($sPDOMessage, 'Duplicate entry');
            $iPositionField = mb_strpos($sPDOMessage, '\' for key \'');
            $aResult['Duplicate'] = mb_substr($sPDOMessage, $iPositionField + 11, -6);
            return $aResult;
        //check respect of Pirmary Key contraint
        } elseif (mb_strpos($sPDOMessage, 'Duplicate entry') && mb_strpos($sPDOMessage, 'PRIMARY')) {
            $aResult['DuplicatePrimary'] = $Trinity->listeNomAttributs('0');
            return $aResult;
        //check foreign keys contrainte
        } elseif (mb_strpos($sPDOMessage, \FK_ENDING)) {
            $iPositionFOREIGN = mb_strpos($sPDOMessage, 'FOREIGN KEY (`');
            $sPariel1 = mb_substr($sPDOMessage, $iPositionFOREIGN);
            $aString = explode('`', $sPariel1);
            $aResult['ForeignKey'] = $aString[1];
            return $aResult;
        } 
        $aResult['PDOProblem'] = 'true';
        return $aResult;
    }
}
