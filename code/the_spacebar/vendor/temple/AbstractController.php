<?php
namespace temple;

/**
 * class dont héritent tous les controllers
 */

class AbstractController
{
    /**
     * permet de sérialiser $_REQUEST dans une page souvent en prévision
     * de transmettre à une autre page.
     * prévoir : possibilité de coder cette chaine
     * $modification permet de modifier $_RESQUEST
     * @param type $name Description: array $modifications
     * @return string
     */
    public function serialiseREQUEST(array $modifications = null)
    {
        foreach ($modifications as $key => $value) {
            if ($value === null) {
                unset($_REQUEST[$key]);
            } else {
                $_REQUEST[$key] = $value;
            }
        }
        return urlencode(serialize($_REQUEST));
    }

    /**
     * permet de récupérer la chaine sérialisée par serialiseREQUEST
     * on peut créer à la volée les variables qui seront accessibles
     * dans page de reception : $this->nom2varaible (pas orthodoxe)
     * prevoir la décodage si serialiseREQUEST() a codé le chaine
     * @param string $ping
     * @param bool $initialise_var
     * @return array
     */
    public function unserialiseREQUEST($ping, $initialise_var = false)
    {
        if ($initialise_var) {
            foreach (unserialize(urldecode($ping)) as $key => $value) {
                $this->$key = $value;
            }
            return null;
        }
        return unserialize(urldecode($ping));
    }

    /**
     * sanitize array of data. possible to exclude some fields
     * @param array $array
     * @param array $aIgnore
     * @return null
     * */
    public function sanitizeRecursivly(&$array, $aIgnore = null)
    {
        foreach ($array as $key => &$value) {
            if (is_string($value)) {
                if (!is_array($aIgnore) || is_array($aIgnore) && !in_array($key, $aIgnore)) {
                    $value = strip_tags($value);
                }
            } elseif (is_array($value)) {
                $this->sanitizeRecursivly($value, $aIgnore);
            }
        }
        unset($value);
    }

}
