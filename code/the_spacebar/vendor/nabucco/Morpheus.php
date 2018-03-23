<?php
namespace nabucco;

class ErrorMorpheus extends \ErrorException {

}

class Morpheus {

    use \temple\TraitBDD;
    use \nabucco\TraitListeNomAttributs;

    protected $_listeNomChamps = array();

    //utilisé pour les jointures
    protected $_listeTablesJointes = array();
    protected $_listeTablesOrigines = array();

    protected $_listeChampsOrigine = array();
    protected $_listeChampsJoin = array();

    protected $_listeTablesConversion = array();
    protected $_listeChampsAConvertir = array();
    protected $_listeChampsConverted = array();

    protected $_jointure;
    protected $_alias2jointure;

    public function __construct(string $table, bool $start_transaction=false){
        $this->genererPDO();//\temple\TraitBDD;
        $this->set_table($table);
        $this->set_listeNomAttributs();
        $this->set_listeNomChamps();

        if ($start_transaction) $this->start_transaction();
    }

    /**
     * get fields name for dataBase or cache (disque or redis)
     * ands set of name of attributs
     */
    final protected function set_listeNomAttributs()
    {
        $cacheNom  = \CACHE_MODEL_DISK_PATH.'nabucco_'.$this->_table.'listeNomAttributs.k';
        //when cache is stored on hard disque
        if (\CACHE_MODEL === 'disque') {

            /* if you when to set a time limite of the cache
             * $expire = time() - 3600 ;
             * && filemtime($cacheNom) > $expire
             */
            if(file_exists($cacheNom)) {
                $this->_listeNomAttributs = unserialize(file_get_contents($cacheNom));
            } else {
                $q = $this->_bdd->query('DESCRIBE '.$this->_table);
                $this->_listeNomAttributs = $q->fetchAll(\PDO::FETCH_COLUMN,0);
                file_put_contents($cacheNom, serialize($this->_listeNomAttributs));
            }
        } elseif (\CACHE_MODEL === 'redis') {
            try {
                $oRedis = new \Redis();
                $oRedis->connect(\REDIS_PHP);
                $serialized = $oRedis->get($cacheNom);
                if ($serialized){
                    $this->_listeNomAttributs = unserialize($serialized);
                } else {
                    $q = $this->_bdd->query('DESCRIBE '.$this->_table);
                    $this->_listeNomAttributs = $q->fetchAll(\PDO::FETCH_COLUMN,0);
                    $oRedis->set($cacheNom, serialize($this->_listeNomAttributs));
                }
                unset($oRedis);
            } catch (\Exception $exc) {
                echo $exc->getTraceAsString();
            }

        }else {
            $q = $this->_bdd->query('DESCRIBE '.$this->_table);
            $this->_listeNomAttributs = $q->fetchAll(\PDO::FETCH_COLUMN,0);
        }
    }

    //entour les nomAttribut de ` ` pour les requêtes SQL
    final protected function set_listeNomChamps()
    {
       foreach($this->listeNomAttributs() as $nomAttributs){
           $this->_listeNomChamps[]='`'.$nomAttributs.'`';
       }
   }

   public function listeNomChamps($rang='array')
   {
        if ($rang=='array') {
            return $this->_listeNomChamps;
        } else {
            $rang = (int) $rang;
            return $this->_listeNomChamps[$rang];
        }
   }

    // les transactions de SQL
    public function start_transaction()
    {
        $this->_bdd->beginTransaction();
    }

    public function commit()
    {
        $this->_bdd->commit();
    }

    public function rollback()
    {
        $this->_bdd->rollBack();
    }

    //prépater la jointure avec d'autres tables
    public function add_jointure(string $champOrigine, string $tableJointe, string $champJoin='', int $rangTable=0)
    {
        // JOIN $tableJointe ON $champOrigine = $tableJointe.$champJoin
        //$rangTable permet de faire une jointure en cascade A->B->C

        $this->_listeChampsOrigine[]=$champOrigine;
        $this->_listeTablesJointes[]=$tableJointe;

        if ($rangTable!=0) $this->_listeTablesOrigines[]='table'.$rangTable;
        else $this->_listeTablesOrigines[]=$this->_table;

        if ($champJoin=='') $this->_listeChampsJoin[]='id_'.$tableJointe;
        else $this->_listeChampsJoin[]=$champJoin;
    }

    protected function set_jointure(){
       $this->_jointure = '';
       $n=0;//CREATION du SQL de jointure
       $t=1;

       foreach ($this->_listeTablesJointes as $tableJointe){
            $this->_jointure .= ' JOIN '.$tableJointe.' AS table'.$t.' ON '.$this->_listeTablesOrigines[$n].'.'.$this->_listeChampsOrigine[$n].' = table'.$t.'.'.$this->_listeChampsJoin[$n] ;
            $n++;
            $t++;
        }
    }

    protected function set_alias2jointure()
    {
        if ($this->_listeChampsConverted){//si on add_champConverted()
            $this->_alias2jointure = ' , ';
            $n=0;
            foreach ($this->_listeChampsConverted as $champsConverted){
                $this->_alias2jointure .= ' table'.$this->_listeTablesConversion[$n].'.'.$champsConverted.' AS '.$n.'_alias, ';
                $n++;
            }
            $this->_alias2jointure = substr($this->_alias2jointure, 0, -2);
       }
       else{
            $this->_alias2jointure='';
       }
    }

//permet de définir les champs qui vont être convertis dans selectConverted
    public function add_champConverted($champConverted, $champsAConvertir='', $rangTable=NULL)
    {

        //$champConverted est la valeur qui va être renvoyé par $Objet->get('$champsAConvertir)

        $this->_listeChampsConverted[] = $champConverted;
        if ($champsAConvertir!=''){
            $this->_listeChampsAConvertir[]=$champsAConvertir;
        }
        else{
            $r = count($this->_listeChampsOrigine)-1;
            $this->_listeChampsAConvertir[] = $this->_listeChampsOrigine[$r];
        }

        if ($rangTable==NULL) $rangTable = count ($this->_listeTablesJointes);
        $this->_listeTablesConversion[]=$rangTable;
    }

//SELECT depuis la table et retourne un OBJET dont on peut converti certains champs grace à add_jointure() et add_champConverted()
    public function select($id, $champs='' )
    {
        // !!! ne pas mettre de quote (ex :`champ`) cette methode les gère automatiquement
        if(!$champs) {
            $champs = $this->_listeNomChamps[0];
        } else {
            $champs='`'.$champs.'`';
        }
        $this->set_jointure();
        $this->set_alias2jointure();
        $q = $this->_bdd->query( "SELECT ".implode(",",$this->_listeNomChamps )." ".$this->_alias2jointure." FROM ".$this->_table.' '.$this->_jointure." WHERE ".$champs." = '".$id."'");
        $donnees = $q->fetch();
        if($donnees) {
            $table = str_replace('`','',$this->_table);//set_table($table) met des ` il faut donc les retirer sinon elles sont doublées
            if ($this->_listeChampsConverted)
                return new Trinity ($table, $donnees,$this->_listeChampsAConvertir);
            else
                return new Trinity ($table, $donnees);
        } else {
            throw new ErrorMorpheus ('<span class="alerte">!!! '.__CLASS__.'('.$this->_table.')::select : $id inexistant!!!</span>');
            return NULL;
        }

    }

//SELECT depuis la table et retourne un array d'OBJETS, on peut changer l'ordre du retour.
    public function selectObjets(string $where = '', string $order='')
    {
        $liste = array();
        if ($order=='') $order = $this->_listeNomChamps[0];

        $this->set_jointure();
        $this->set_alias2jointure();


        $q = $this->_bdd->query('SELECT '.implode(",",$this->_listeNomChamps)." ".$this->_alias2jointure." FROM ".$this->_table.$this->_jointure.' '.$where.' ORDER BY '.$order);

        while ($donnees = $q->fetch()){
            $table = str_replace('`','',$this->_table);//set_table($table) met des ` il faut donc les retirer
            if (count($this->_listeChampsConverted)) $liste[] = new Trinity ($table, $donnees, $this->_listeChampsAConvertir);
            else $liste[] = new Trinity ($table, $donnees);
        }

        return $liste;
    }

    public function insertObjet(Trinity $objet, bool $autoIncrement=false, bool $returnMax=false)
    {
    //$whereIdMax permet d'avoir le dernier id en fonction de critère si besoin (exemple facture/avoir)
        $listeAttributs = array();

        if ($autoIncrement){//pour laisser SQL gérer AUTOINCREMENT
            $n=0;
            foreach ($this->_listeNomChamps as $champ){
                if ($n>0){
                    $listeAttributs[]= $this->listeNomAttributs($n);
                    $listeChamps[]=$champ;
                }
                $n++;
            }
        }
        else{
            $listeAttributs = $this->listeNomAttributs();
            $listeChamps=$this->_listeNomChamps;
        }

        //constition de la requette SQL
        $requette='';
        $n=0;
        foreach ($listeChamps as $champ){
            $requette .= $champ.' = :'.$listeAttributs[$n].', ' ;
            $n++;
        }
        $requette = substr($requette, 0, -2);//suppression de dernière virgule

        $q = $this->_bdd->prepare('INSERT INTO '.$this->_table.' SET '.$requette);
        foreach ($listeAttributs as $attribut){
            $q->bindValue(':'.$attribut, $objet->get($attribut));
        }

        $q->execute();

        if ($returnMax) {
            if (empty($whereIdMax)) {
                return $this->_bdd->lastInsertId();
            } else {
                return $this->maxId($whereIdMax);
            }
        } else {
            return true;
        }

    }

    /**
     * retourne le plus grand ID (utilisé par ->insertObjet)
     * $indice permet de préciser le champs pour la choix
     */
    public function maxId(string $where = '', int $indice = 0)
    {
        $q = $this->_bdd->query('SELECT MAX(' . $this->_listeNomChamps[$indice] . ') AS idMax FROM ' . $this->_table . ' ' . $where);
        $donnees = $q->fetch();
        return $donnees['idMax'];
    }

    //retourne la plus grande valeur de la table (utilisé par ->insertObjet)
    public function selectMax($where = '', $indice = 0)
    {
        $q = $this->_bdd->query('SELECT MAX('.$this->_listeNomChamps[$indice].') AS retourMax FROM '.$this->_table.' '.$where);
        $donnees = $q->fetch();
        return $donnees['retourMax'];
    }

    //efface une entrée de table en F° d'un id, la variable $champs permet de delete des fkid
    public function deleteParId($id, $champs = 0, string $sAddWhere = '')
    {
        if (is_int ($champs) && $champs >= 0)
            return $this->_bdd->exec('DELETE FROM ' . $this->_table . ' WHERE ' . $this->_listeNomChamps[$champs].' = ' . "'$id' " . $sAddWhere);
        else
            throw new ErrorMorpheus ('!!! '.__CLASS__.'->supprimeParId() : $champ est entier > 0 !!!');
    }

    //efface une entrée de table en F° d'un objet
    public function deleteParObjet($Objet){
        return $this->_bdd->exec('DELETE FROM '.$this->_table.' WHERE '.$this->_listeNomChamps[0].' = '.$Objet->get($this->listeNomAttributs('0')));
    }

    /*met à jour un champ de la table (utilisé par updatePost2Table)
     * $attributRang permet de changer le champ sur le 1er WHERE du UPDATE --
     * -- (utile pour la MAJ de plusieurs entrées avec un fk, relation OneToMany)
     * $indiceChampsAnd='' permet d'ajouter une condition après le WHERE de l'UPDATE
     */
    public function update($id, $attributNom, $valeur, $attributRang = null, $indiceChampsAnd = 0, $valeurAnd='', $comparateurWHERE = '=', $comparateurAND = '=')
    {
        //$comparateurWHERE et $comparateurAND permettent de changer les conditions du WHERE et AND (exemple champ>:champs)
        // !!! ne pas mettre de quote (ex :`champ`) cette methode les gère automatiquement
        if ($attributRang === null) {
            $attributRang = 0;
        }

        if (is_int($attributRang)) {
            if ($attributRang === 0) {
                $attribut2selection = $this->listeNomAttributs('0');
            } elseif ($attributRang > 0) {
                $attribut2selection = $this->listeNomAttributs($attributRang);
            } else {
                throw new ErrorMorpheus ('!!! '.__CLASS__.'->update ($attributRang) est int >=0');
            }
        } elseif (is_string($attributRang)) {
            $attribut2selection = $attributRang;
        } else {
            throw new ErrorMorpheus ('!!! '.__CLASS__.'->update (...$attributRang est un int >=0 ou un string');
        }

        try {
            //verifie si l'entrée exsite
            $Objet = $this->select($id, $attribut2selection);
            //vérifie si la valeur est au format
            $Objet->verifierFormat($attributNom, $valeur);

            $and = '';
            if ($indiceChampsAnd != 0) {
                $and = ' AND '.$this->_listeNomChamps[$indiceChampsAnd].' '.$comparateurAND.' :attribut_and';
            }
    /* PROBLEME SUR UPDATE DANS RELATION ONE TO MANY
     *
     */
            //j'ajoute _where et _and à la requêtte pour les cas où il y a l'utilisation du même champs dans SET, WHERE ou AND.
            $q = $this->_bdd->prepare('UPDATE '.$this->_table.' SET `'.$attributNom.'` = :'.$attributNom.' WHERE `'.$attribut2selection.'` '.$comparateurWHERE.' :attribut_where'.$and);
            $q->bindValue( ':'.$attributNom, $valeur );
            $q->bindValue( ':attribut_where', $Objet->get($attribut2selection));
            if ($indiceChampsAnd!=0) $q->bindValue( ':attribut_and',$valeurAnd);
            $q->execute();

            return true;
        } catch (ErrorMorpheus $ex) {
            //echo '<pre>';
            //echo '<p class="alerte">'.$ex->getTraceAsString().'</p>';
            //echo '</pre>';
            throw new ErrorMorpheus (
            '<span class="alerte">!!!! ' . $id . ' est un id inexistant de '.__CLASS__.'->update('.$this->_table.') !!!!</span>'
            );
            return false;
        } catch (\nabucco\ErrorTrinity $ex) {
            throw new \nabucco\ErrorTrinity (
            '<span class="alerte">!!!! pb avec format de ' . $valeur . ' de '.__CLASS__.'->update('.$this->_table.') !!!!</span>'
            );
            return false;
        } catch (\PDOException $ex) {
            throw new \PDOException (
            '<span>!!!! PDO pb ' . __CLASS__ . '->update(' . $this->_table . ') !!!! ' . $ex->getMessage() . '</span>'
            );
            return false;
        } catch (\Exception $ex) {
            throw new \Exception (
            '<span class="alerte">!!!! pb de '.__CLASS__.'->update('.$this->_table.') !!!!</span>'
            );
            return false;
        }
    }

//compter les objets en fonction de critère avec possibilité de joindre une table
	public function countEntree($where='',$join='')
        {
		$q = $this->_bdd->query("SELECT COUNT(*) AS total FROM ".$this->_table." ".$join." ".$where);
		$donnees = $q->fetch();
		return $donnees['total'];
	}

//fait la somme des quantity  d'objets en fonction de critère avec possibilité de joindre une table
	public function sumEntree($champ, $where='',$join='')
        {
		$q = $this->_bdd->query("SELECT SUM(".$champ.") AS somme FROM ".$this->_table." ".$join." ".$where." ");
		$donnees = $q->fetch();
		return $donnees['somme'];
	}

//convertir automatiquement les POST qui ont été convertis par add_champConverted
        public function convertirPOST(Trinity $Objet)
        {
            $listeChampsAconvertir = array();

            $n=0;//identifie les POST à convertir
            foreach ($this->_listeChampsOrigine as $attribut){
                foreach ($_REQUEST as $cle =>$post){
                    if ($attribut==$cle) $listeChampsAconvertir[$n]=$attribut;
                }
                $n++;
            }

            foreach ($listeChampsAconvertir as $cle=>$post){//conversion

                if ($_REQUEST[$post]!=$Objet->get($post))// si on change la saisie on convertie
                    $_REQUEST[$post] = $this->convertir($this->_listeChampsJoin[$cle],$this->_listeTablesJointes[$cle],$this->_listeChampsConverted[$cle],$_REQUEST[$post],$this->_bdd);
                else //sinon on utilise la valeur stockée dans l'objet
                    $_REQUEST[$post] = $Objet->convertedAttribut($post);

                //protection quand le mustMatch n'a pas le temps d'agir et pas de conversion
                if ($_REQUEST[$post] == NULL) $_REQUEST[$post]=false;
            }
        }

        /**
         * $sRetour the converted $sValeur by selection it in a $sTable
         * @param string $sRetour
         * @param string $ChampNom
         * @param string $sValeur
         * @param string $sTable
         * @return string
         */
	public function convertir($sRetour, $ChampNom, $sValeur, $sTable = null)
        {
            if (!$sTable) {
                $sTable = $this->_table;
            }
            $req = $this->_bdd->query("SELECT $sRetour FROM $sTable WHERE $ChampNom='$sValeur'");
            $rslt = $req->fetch();
            return $rslt[$sRetour];
	}
}

/*UPDATE tous et seulement les champs de la table qui ont été modifiés par le POST correspondant
 *$indice,$indiceChampsAnd sont utilisés pour ajouter une condition après le WHERE de l'UPDATE de public function update()
 *l'id de l'objet n'étant pas suffisant pour mettre à jour, souvent quand les inputs sont générés dynamiquement avec en  plus des indices.
 * exemple j'ai un tableau qui liste des objets et je veux pouvoir modifier l'attribut du 3eme objet de cette liste.
 */
/* OBSOLETE, utilisé plutôt : updateRequest2Table
    public function updatePost2Table($Objet,$indice='',$attributRang=0){

        //creation des POST non initialisés
        $Objet->rewind();
        while ($attribut = $Objet->key()){
            if (!isset($_REQUEST[$attribut]) ){
                $_REQUEST[$attribut] = $Objet->$attribut();
            }
            $Objet->next();
        }
        foreach ($Objet->listeNomAttributs() as $attribut){
            if ($Objet->get($attribut)!= $_REQUEST[$attribut.$indice]){
                if ($indice=='')$this->update($Objet->get($Objet->listeNomAttributs('0')), $attribut, $_REQUEST[$attribut]);
                else $this->update($Objet->get($Objet->listeNomAttributs('0')), $attribut, $_REQUEST[$attribut.$indice], $attributRang);
            }
        }
    }
*/
    /** OBSOLETE : cette methode devrait être supprimée
     * ce n'est pas le rôle de Morpheus
     * Elle est utilisée juste pour faire des testes : MorpheusTest l16 -> MorpheusMouse l61

    public function updateRequest2Table($Objet, $prefixe = '', $indice='',$attributRang=0){

        //creation des $_REQUEST non initialisées
        $Objet->rewind();
        while ($attribut = $Objet->key()){
            if (!isset($_REQUEST[$prefixe.$attribut]) ){
                $_REQUEST[$prefixe.$attribut] = $Objet->$attribut();
            }
            $Objet->next();
        }
        foreach ($Objet->listeNomAttributs() as $attribut){
            if ($Objet->get($attribut) !=  $_REQUEST[$prefixe.$attribut.$indice]) {
                if ($indice=='')
                    $this->update($Objet->get($Objet->listeNomAttributs('0')), $attribut, $_REQUEST[$prefixe.$attribut]);
                else
                    $this->update($Objet->get($Objet->listeNomAttributs('0')), $attribut, $_REQUEST[$prefixe.$attribut.$indice], $attributRang);
            }
        }
    }
     */