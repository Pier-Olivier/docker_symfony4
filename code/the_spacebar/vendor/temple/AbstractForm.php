<?php
namespace temple;

abstract class AbstractForm {
    
    protected $_liste_erreur = array();
    
    public function erreur() {
        return $this->_liste_erreur;
    }

    public function add_erreur($attribut,$message) {
        $this->_liste_erreur[$attribut] = $message;
    }

    /**
     * crée le : <form ...>
     * @param string $s_Id : ajouter un id="" utile pour JSConfirmation d bouton
     */

    static public function header(
        int $iAccess = 0,
        string $action = '',
        string $method = 'post',
        string $enctype = '',
        string $target = '',
        string $s_Id = ''
    ) {
        if ($iAccess > 0 || $_SESSION['fkpersonne_droit'] >= $iAccess) {
            echo PHP_EOL;
            if ($action)
                $action = 'action="'.$action.'" ';
            if ($enctype)
                $enctype = 'enctype="multipart/form-data"';
            if ($target)
                $target = 'target="'.$target.'" ';
            if ($s_Id)
                $s_Id = 'id="'.$s_Id.'" ';

            echo '<form method="' . $method . '" ' . $action . ' ' . $enctype . ' ' . $target . ' ' . $s_Id .' >';
            echo PHP_EOL;
        }
    }

    /**
     * pour créer des bouton HTML5
     * @param $sJSConfirmation : ouvre un pop-up qui demande de confirmer le click
     *        $sJSConfirmation : est le nom de la fonction JS qu'il faut aussi donner à header::$s_Id
     */

    static public function bouton_soumettre(
        int $iAccess = 0,
        string $name = 'soumettre_form',
        string $value = 'ok',
        string $retour = '',
        string $sJSConfirmation = '',
        string $sMessage     = 'Merci de confirmer votre décision'
    ) {
        if ($iAccess === 0 || $_SESSION['fkpersonne_droit'] >= $iAccess) {
            if ($retour==='T') echo '<td>';

            echo '<button id="' . $name . '" name="' . $name . '" ';
            if ($sJSConfirmation) {
                echo ' onclick="' . $sJSConfirmation . '()" ' . 'type="button" ';
            } else {
                echo 'type="submit" ';
            }
            echo '>' . $value . '</button>';

            if ($sJSConfirmation) {
                echo PHP_EOL;
                ?>
                <script>
                function <?=$sJSConfirmation?>() {
                    if (confirm("<?=$sMessage?>")) {
                        document.getElementById("<?=$sJSConfirmation?>").submit();
                    }
                }
                </script>
                <?php
            }

            if ($retour==='T') echo '</td>';
        }

    }

    static public function footer($iAccess = 0)
    {
        if ($iAccess > 0 || $_SESSION['fkpersonne_droit'] >= $iAccess) {
            echo '</form>';
            echo PHP_EOL;
        }
    }
}
