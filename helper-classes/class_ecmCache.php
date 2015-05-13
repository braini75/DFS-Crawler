<?php

/**
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 * Description of class_ecmCache
 *
 * @author Thomas Koch <thomas.koch@clondiag.com>
 */
include_once 'tools/class_ecmCacheFile.php';

class ecmCache {
    private static $instance;        

    VAR $kstl	  = array();    // array of (kostenstelle , 'leader' = array(leiter, stellvertreter), 'name'=>'NAME of Kostenstelle' )
    VAR $projects = array();    // array of (Projektnummer, 'leader' = array(leiter, stellvertreter), 'kstl_id'=>kostenstelle, 'name'=>'NAME des Projekctes');    
    VAR $users 	  = array();    // array of (nick, 'vacation' = array(anspruch, verbraucht));
         
    private function __construct()
    {
        $ecmCacheFile = new ecmCacheFile('.');
        if (isset($_SESSION['config']["ecm_url"])) $ecmCacheFile->ecm_url=$_SESSION['config']["ecm_url"];                        // override default
        /*
            if (!get_headers($ecmCacheFile->ecm_url)) {
            htmlShowError("ECM-Server nicht verf&uuml;gbar!<br>URL: ".$ecmCacheFile->ecm_url);
            die;            
        }   */     
        if (isset($_SESSION['sessvars']["ecm.cache"])) $ecmCacheFile->ecm_cache_file=$_SESSION['sessvars']["ecm.cache"];                        // override default

        if (file_exists($ecmCacheFile->ecm_cache_file)) {  
        	showDebug($this->ecm_cache_file, "ECM-Cache-File ".$ecmCacheFile->ecm_cache_file." exist.");
            $ecmCacheFile->read_json();
            $this->kstl=$ecmCacheFile->kstl;
            $this->projects=$ecmCacheFile->projects;
            $this->users=$ecmCacheFile->users;

        } else {
            //create json file
            showDebug($ecmCacheFile->ecm_cache_file, "ECM-Cache-File not found! ");
            htmlShowError('ECM-Cache-File "'.$this->ecm_cache_file.'" not exists. Please Inform Admin.');
            return;
            /*
            $ecmCacheFile->create_json();
            $this->kstl=$ecmCacheFile->kstl;
            $this->projects=$ecmCacheFile->projects;
            */
        }        
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance     = new ecmCache();                
        }
        return self::$instance;
    }
    
    private function ecmToArray($url){
            $returnArray    = array();        
            $ecm_return     = file_get_contents($url);        
            $ecm_content    = json_decode($ecm_return,true);


            if (isset($ecm_content['data'])){
                foreach ($ecm_content['data'] as $val){
                                                            
                    array_push($returnArray, ltrim($val,'0'));
                }                        
            }               
            return $returnArray;
    }

/*   function read_json(){
        $ecm_cache_file=$this->ecm_cache_file;
        $jsonContent = file_get_contents($ecm_cache_file);
        $this->json_set=json_decode($jsonContent,true);
        $this->kstl=$this->json_set['kstl'];
        $this->projects=$this->json_set['projects'];          
    }

       
    function fecho($string) {
        echo $string;
        flush();
    }
  */        
    
    /**
    * Liefert ein Array von KST-Leiter und KST-Vertretung
    * 
    * @param type $kostenstellennummer
    * @return int
    */
    public function getLeaderOfKst( $kostenstellennummer )
    {
        /*
         * $leader[12010]= ARRAY ("Matthias Kreling", "Markus Bentke");
         * $leader[12020]= ARRAY ("Thomas Krusch", "Markus Bentke");
         * $leader[12030]= ARRAY ("Matthias Kummer", "Bärbel Rudakoff");
         * $leader[13000]= ARRAY ("Eugen Ermantraut", "Torsten Schulz");
         * $leader[111]= ARRAY ("Thomas Koch", "Thomas Koch");
         */
        if ($kostenstellennummer=="Auswahl") return false;
        $result=array();
        $leader=array();
                
        $leader[$kostenstellennummer]=$this->kstl[$kostenstellennummer]['leader'];

        //Testvalues:
        /*
        $leader[12010]= ARRAY (4356, 135);        
        $leader[12020]= ARRAY (4344, 135);
        $leader[12030]= ARRAY (4365, 4198);
        $leader[13000]= ARRAY (1, 2);*/
        //$leader[981]= ARRAY (4198, 4198); //zum email testen
        

       if (!empty($leader[$kostenstellennummer])) $result=$leader[$kostenstellennummer];

       return $result;    
    }

    /**
     * Liefert alle Kostenstellen aus dem ECM 
     * @return array of Kostenstellen
     */
    public function getKstNums(){        
        return array_keys($this->kstl);
    }
    
    /**
     * Name einer Kostenstelle
     * @return string
     */
    public function getKstName($kostl){
    	return $this->kstl[$kostl]['name'];
    }
    /**
     * Name eines Projektes
     * @return string
     */
    public function getProjName($projektnummer){
    	return $this->projects[$projektnummer]['name'];
    }


    /* Projektmanagement: */
    /**
     * Liefert Leiter und Stellvertreter eines Projekts
     * @param type $projektnummer
     * @return int
     */
    public function getLeaderOfProj($projektnummer)
    {
        $result =array();
        $leader =array();
                        
        if (isset($this->projects[$projektnummer]['leader'])){
            $leader[$projektnummer]=$this->projects[$projektnummer]['leader'];
        }
        
            
        /* Testprojekt für email-Versand an Thomas Koch */
        //$leader[111111]= ARRAY (4198, 4198);

        if (isset($leader[$projektnummer])) $result=$leader[$projektnummer];
        return $result;    
    }

    
    public function getProjNums($kostenstellennummer)
    /*
     * $projs = $lib->getProjNums( $kst ); 
     * 
     * Liefert alle Projektnummern einer Kostenstelle
     */
    {
        $ProjReturn=array();
        if (isset($this->kstl[$kostenstellennummer]['projects'])){
            $ProjReturn=$this->kstl[$kostenstellennummer]['projects'];
        }
        
        $ProjReturn[$kostenstellennummer]=$ProjReturn;
        //$ProjReturn[13000] = ARRAY (131011, 131021, 131022, 111111, 777);
        /* Nummer 777 für Fehlerfahl (ohne leader) */

         
        return $ProjReturn[$kostenstellennummer];
    }

    /**
     * Liefert alle Kostenstellen in dem $pnum Leiter ist
     * @param type $pnum
     * @return array of Kostenstellen
     */
    public function getMyKst($pnum){
        $all_kst=$this->getKstNums();        
        $mykst= array();   
        foreach($all_kst as $kst){
            if (in_array($pnum,$this->getLeaderOfKst($kst))) array_push($mykst, $kst);             
        }
        //$mykst=array(222,333,444,555,666,777,888,999,1111,2222,3333,4444,5555);

        if (empty($mykst)){
            return false;
        }else {
            return $mykst;
        }
    }

    public function getMyProjects($pnum){
        $all_kst=$this->getKstNums();            
        $myProj=array();
        $cur_proj="";    
        foreach($all_kst as $kst){        
            $cur_proj=self::getProjNums($kst);
            foreach($cur_proj as $proj){
                $cur_leader=self::getLeaderOfProj($proj);            
                if (in_array($pnum,$cur_leader)){                
                    array_push($myProj, $proj);                
                }
            }

        }
        
        //$myProj=array(111,222,333,444);

        if (empty($myProj)){
            return false;
        }else {
            return $myProj;
        }
    }
}

