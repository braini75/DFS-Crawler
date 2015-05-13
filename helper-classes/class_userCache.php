<?php

/**
 * Cach von Userdaten des Actrive Directory
 * usage: 
 * $userLib = userCache::getInstance()
 * $userLib->getEmail('braini');
 * 
 * @author Thomas Koch <thomas.koch@clondiag.com
 * @version 1.0
 * @package library
 */

include_once 'class_ecmCache.php';

class userCache {
    private static $instance;
    /**
     * @var type array(PNUM=>array('cn'=> , 'mail'=> , 'sAMAccountName' => ))
     */
    private static $userDict; 
    
    /**
     * @var type array('sAMAccountName'=> 'PNUM')
     */
    private static $NICDict; 
    
        
    // we'll store the LDAP connection so we do not need to re-connect for every command
    private $connection = null;
    private $def_server = 'ldap://ldap.clondiag.jena';  
	
    private function __construct()
    {
        $config = $_SESSION['config'];        
        $connection= $this->connect('', $config['auth_user'], $config['auth_pass']);
        
    }

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new userCache();
            self::$userDict = NULL;
        }
        return self::$instance;
    }
 
      
    /**   
     * Connect and bind to the LDAP or Active Directory Server
     * NOTE: we are assuming the default port of 389.  Alternate ports should be specified in the ldap_connect function, if needed.
     * NOTE: We are using the singleton pattern here - we only create a connection if it does not exist.
     * @param $server if NULL, use default server
     * @throws exceptions
     */
       
    private function connect($server, $user, $password)
    {
      if ($server==NULL) $server = $this->def_server;
      if ($user==NULL or $password==NULL) {
           throw new Exception('user oder password fehlt.');
      }

      $user = $user . '@clondiag.jena';

      if ($this->connection)
      {
        return $this->connection;
      }
      else
      {
        $ldapConn = ldap_connect($server);
        if ( $ldapConn )
        {
          ldap_set_option($ldapConn, LDAP_OPT_PROTOCOL_VERSION, 3);
          if ( @ldap_bind( $ldapConn, $user, $password) ) {
            $this->connection = $ldapConn;
            return $this->connection;
          }
        }
      }
    }
 
    private function search($filter, $attributes)
    {
        $base_dn = 'CN=Users,DC=clondiag,DC=jena';
        $connection = $this->connection;
        $results = ldap_search($connection, $base_dn, $filter, $attributes);
        if ($results)
        {
          $entries = ldap_get_entries($connection, $results);
          return $entries;
        }
    }

    /**
     * return email, sAMAccountName und Name als array
     * @param type $pnum as employeenumber
     * @return type array('cn','mail','employeenumber')
     */
    private function user_data($filterAttr, $filterVal)
    {   
        $userAttribute=array(); // array('cn'=> , 'mail'=> , 'samaccountname' => )       
        $filter = "(".$filterAttr."=".$filterVal.")";       
        $attr = array ('cn','mail','samaccountname', 'employeenumber');       
        $results = $this->search($filter, $attr); 
        //showDebug($results,"ldap: ",1);
        $count=$results['count'];
        if ($count){
            $pnum=$results[0]['employeenumber'][0];
            self::$NICDict[$results[0]['samaccountname'][0]]=$pnum;
            foreach ($attr as $col_name){
                self::$userDict[$pnum][$col_name]=$results[0][$col_name][0];                  
            }            
        } 
        return $count;
    }
    
    private function ecm_data($nick,$type){
    	$ecmCache = ecmCache::getInstance();
    	return $ecmCache-> users[$nick][$type];
    }
    
    public function getUserVacationInfo($nick){
    	//TODO: future handling of ecm vacation data?!
    	
    	
    }
    
    public function userExist($nic){
    	$nicExist=false;
        if (isset(self::$NICDict[$nic])){
        	 $nicExist=true;
        } else {
        	if ($this->user_data('samaccountname', $nic)) $nicExist=true;        	        		
        	}        	         
        return $nicExist;
    }
    
    
    public function getEmailByPNUM($pnum) {
        if (isset(self::$userDict[$pnum])) {                 
            return self::$userDict[$pnum]['mail'];
        }
        
        //echo 'hole aus LDAP:'.$pnum."<br>";
        
        $this->user_data('employeenumber',$pnum);
         
        return self::$userDict[$pnum]['mail'];
    }
    
    public function getNameByPNUM($pnum) {
        if (isset(self::$userDict[$pnum])) {                 
            return self::$userDict[$pnum]['cn'];
        }
        
        //echo 'hole aus LDAP:'.$pnum."<br>";
        
        $this->user_data('employeenumber',$pnum);
         
        return self::$userDict[$pnum]['cn'];
    }
    
    function getPNbyNIC ($nic){
        if (isset(self::$NICDict[$nic])) return self::$NICDict[$nic];
        
        $this->user_data('samaccountname', $nic);
        return self::$NICDict[$nic];
    }
    
    public function getNameByNIC($nic){
        $pnum = $this->getPNbyNIC($nic);
        $name=$this->getNameByPNUM($pnum);
        if (empty($name)){
        	$name=$nic;
        }
        return $name;
    }
    
    public function getUserDataByPNUM($pnum) {
        if (isset(self::$userDict[$pnum])) {                 
            return self::$userDict[$pnum];
        }
        
        //echo 'hole aus LDAP:'.$pnum."<br>";
        
        $this->user_data('employeenumber',$pnum);
         
        return self::$userDict[$pnum];
    }

    public function getUserDataByNIC($nic){
        $pnum = $this->getPNbyNIC($nic);
        return $this->getUserDataByPNUM($pnum);
    }
    
    
}