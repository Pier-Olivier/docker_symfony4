<?php
/**
 * class générale pour inclure les fichiers
 * à la volée lors de new Class()
 */

class Autoloader_po
{
    /**
     * @param string $className : envoyé lors de l'appel de la class
     * @return boolean
     */
    public static function loader($className)
    {
        $generalPath2Class = DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
        $aFilename[] = CHEMIN . 'vendor' . $generalPath2Class;

        foreach ($aFilename as $filename) {
            if (file_exists($filename)) {
                include_once($filename);
                if (class_exists($className)) {
                    return true;
                }
            }
        }
        return false;
    }
}
