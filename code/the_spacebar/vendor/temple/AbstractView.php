<?php
namespace temple;

class ErrorAbstractView extends \ErrorException
{
}


class AbstractView
{
    
    protected $_variables2controller;
    protected $_liste_css;
    
    protected $_fichier2cache;
    protected $_expire_time;
    protected $_repertoire2cache;

    public function __construct($variables2controller=array())
    {
        $this->set_variables2controller($variables2controller);
        extract($this->_variables2controller);
    }
    
    protected function set_variables2controller($variables2controller)
    {
        $this->_variables2controller= $variables2controller;     
    }
    
    public function cache_control($max_age=86400) {
        header("Cache-Control: max-age=$max_age, must-revalidate");
        header("Pragma: public");
    }
    
    public function add_liste_css($css,$repertoire='')
    {//permet d'ajouter des feuilles de style personnalisée pour chaque page
        $this->_liste_css [] = $repertoire.$css;
    }
    
    public function header2cache_configuration($fichier2cache,$expire_time = NULL,$repertoire2cache='cache/html_')
    {
        $this->_fichier2cache = $fichier2cache;
        $this->_expire_time = $expire_time;
        $this->_repertoire2cache = $repertoire2cache;
    }
    
    public function header2cache($title, $css = 'defaut.css', $no_robot = TRUE, $charset= 'utf-8', $lang = 'fr')
    {
        if (isset($this->_fichier2cache) && isset($this->_fichier2cache) && isset($this->_fichier2cache)) {

            $cache = $this->_repertoire2cache.$this->_fichier2cache.'.html';
            $dynamique = FALSE;
            
            if(file_exists($cache)){
                //si $expire_time est NULL le cache n'expire jamais
                if ($this->_expire_time===NULL)
                    $cache_valide = 1;
                else// 3600 = une heure
                    $cache_valide = filemtime($cache) > time() - $this->_expire_time;
                                
                if ($cache_valide)
                    readfile($cache);
                else
                    $dynamique = TRUE;
            } else {
                $dynamique = TRUE;
            }
            
            if ($dynamique) {
                ob_start();
                $this->header($title,$css,$no_robot,$charset,$lang);
                $page = ob_get_contents();
                ob_end_clean();
                if (\CACHE_HTLM)
                        file_put_contents($cache, $page);

                echo $page;                
            }

        } else {
            throw new ErrorAbstractView ('<span class="alerte"> avant usage de '.__CLASS__.'->header2cache() il faut : ->header2cache_configuration() </span>');
        }
    }

    public function header($title, $css='defaut.css', $no_robot = TRUE, $charset='utf-8', $lang='fr')
    {
        echo '<!DOCTYPE html><html lang="'.$lang.'"><head>'.PHP_EOL;
        echo '<meta charset="'.$charset.'">'.PHP_EOL;
        echo '<title>'.$title.'</title>'.PHP_EOL;

        if ($no_robot)
            echo '<meta name="ROBOTS" content="NOINDEX, NOFOLLOW"/>'.PHP_EOL;
        
        if (is_string($css))
            echo '<link rel="stylesheet" href="'. \RACINE_PROJET . \CSS .$css.'">'.PHP_EOL;
        else if (is_array($css)){
            foreach ($css as $css_plus){
                echo '<link rel="stylesheet" href="'. \RACINE_PROJET . \CSS .$css_plus.'">'.PHP_EOL;
            }
        }
        if ($this->_liste_css){
            foreach ($this->_liste_css as $css_plus){
                echo '<link rel="stylesheet" href="'. \RACINE_PROJET . \CSS .$css_plus.'">'.PHP_EOL;
            }
        }
        
        echo '</head><body>'.PHP_EOL;

    }

    public function footer()
    {
        echo PHP_EOL;
        echo '</body></html>';
    }
    
    static public function racine2controleur()
    {
        $url = explode('/',$_SERVER['REQUEST_URI']);
        return $url[0].'/'.$url[1].'/'.$url[2].'/'.$url[3].'/'.$url[4];
    }
}
