<?php
/*
 * Created on 22 oct. 2010
 * Auteur: Benjamin Boulaud
 * Fichier: class_request.php
 * 
 * Cette classe a pour objectif de:
 * -Permettre d'utiliser la classe b�tas�ries cr��e par Maxime Valette et de r�cup�rer du xml
 * -De g�n�rer des exceptions en cas d'�rreurs dans le xml r�cup�r�
 * -De permettre de g�rer une multitude d'utilisateurs diff�rents et leurs tokens
 * -De permettre la mise en cache des requettes et des tokens utilisateurs sans pour autant utiliser une base de donn�es
 * 
 * Elle constitue une couche d'abstraction entre vos scripts et la classe b�tas�ries de Maxime Valette.
 */
 //La classe de Maxime valette g�n�rant du xml apr�s l'envoie d'une requette � l'API de b�taseries
 require_once(dirname(__FILE__)."/../includes/betaseries/class_betaseries.php");
 //une classe d�finissant des variables globales
 require_once(dirname(__FILE__)."/../config/config_betaseries.php");
 
 class BetaSeriesRequest{
 	
	/*-----------------------------------*/
 	/**  nom (sans l'extension) du fichier de cache contenant les utilisateurs enregistr�s et leurs token)
 	 * 
 	 */
 	private static $CACHE_PATH_USER='users';
 	/*-----------------------------------*/
 	/** Instance de la classe Request (classe singleton)
 	 * 
 	 */
 	private static $instance;
 	
 	/*-----------------------------------*/
 	/** Instance de la classe BetaSeries cr��e par Maxime Valette
 	 * 
 	 */
 	private $connexion;
 	
 	/*-----------------------------------*/
 	/** Le tableau des utilisateurs qui se pr�sente sous la forme suivante:
 	 * $usersArray=array('login' => array('password' => 'MyPassWord', 'token' => 'MyUserTokenOnTheAPI');
 	 */
 	private $usersArray=array();
 	
 	/*-----------------------------------*/
 	/** Le modele g�n�r� au retour d'une requete
 	 *
 	 */
 	private $modele;
 	/*-----------------------------------*/
 	/*--------Fonctions Statiques--------*/
 	/*-----------------------------------*/
 	/**
 	 *  getInstance r�cup�re l'instance unique de cette classe
 	 *  (Design pattern singleton)
 	 */
 	public static function getInstance()
 	{
 		if (empty(self::$instance)) self::$instance=new BetaSeriesRequest();
 		return self::$instance;
 	}
 	
 	/*-----------------------------------*/
 	/*--------Fonctions Publiques--------*/
 	/*-----------------------------------*/
 	/** Fonction permettant d'ajouter un utilisateur, de r�cup�rer son token pour pouvoir faire de futures requettes
 	 * @param $login le login de l'utilisateur
 	 * @param $password le password de l'utilisateur
 	 */
 	public function addUser($login,$password)
 	{
 		if (empty($login)) throw new Exception("Login is empty");
 		if (empty($password)) throw new Exception("Password is empty");
 		//TODO getUsersCache();
 		//Si le tableau ne contient pas d�j� l'utilisateur
 		if (!$this->containsUser($login))
 		{
 			//On R�cup�re le token de l'utilisateur:':
 			$token=$this->getToken($login,$password);
 			if (empty($token)) throw new Exception("Le login ou le password de l'utilisateur est incorrect.'");
 			//On construit l'utilisateur et on l'ajoute au tableau
 			$this->usersArray[$login]=array("password" => $password,"token" => $token);
 			//TODO saveUsersCache();
 		}
 		else throw new Exception("User already exists");
 			
 	}
 	
 	/*-----------------------------------*/
 	/** Fonction permettant de supprimer un utilisateur et le token associ�
 	 * @param $login le login de l'utilisateur'
 	 */
 	public function delUser($login)
 	{
 		if (empty($login)) throw new Exception("Login is empty");
 		//TODO getUsersCache();
 		if (!$this->containsUser($login)) throw new Exception("Login doesn't exists in stored users");
 		$this->connexion->set_token($this->usersArray[$login]['token']);
 		$this->connexion->send_request(constant("BETASERIES_API_ADRESS").'/members/destroy.xml');
 		$this->connexion->set_token('');
 		unset($this->usersArray[$login]);
 		//TODO saveUsersCache();
 	}
 	/** Fonction permettant de tester la validit� d'un token pour un utilisateur donn�.
 	 * Si le token n'est plus actif le token va �tre r�cup�r� de nouveau puis mis � jour dans le userArray
 	 * Cette fonction est amen� � �tre appell� uniquement lors de la r�cup�ration d'une erreur de code ???? apr�s une requ�te.
 	 */
 	public function testToken($login)
 	{
 		
 	}
 	/*-----------------------------------*/
 	/** V�rifie que l'utilisateur est connu de la classe
 	 * (contenu dans le tableau usersArray)
 	 */
 	public function containsUser($login)
 	{
 		//TODO getUsersCache();
 		return array_key_exists((string)$login,$this->usersArray);
 	}
 	
 	/*-----------------------------------*/
 	/** Envoie une requete � l'api
 	 * 
 	 */
 	public function request($request,$optionsArray=null,$login=null)
 	{
 		if ($request == null) throw new Exception("Request is empty");
 		
 		//TODO
 		/* if (requestCacheExists($request)) $xml=getRequestCache($request,$login);
 		else */
 		
 		//Envoie une requette � l'api  et r�cupere un xml_simple_element
 		$xml = $this->connexion->send_request(constant("BETASERIES_API_ADRESS").$request,$optionsArray);
		
		//R�cup�re les erreurs et envoie l'exception correspondante si n�cessaire
		
		// TODO
		/*try
		{
			$this->makeExceptions($xml);
		}
		catch (Exception $e)
		{
			throw $e;
		}*/
		
		//TODO saveRequestCache($request,$login,$xml);
		
		return $xml;
  	}
  	
  	/*-----------------------------------*/
  	/** Envoie une requette propre � un utilisateur.
  	 * @param login			le login de l'utilisateur
  	 * @param userRequest	la requete de l'utilisateur http://api.betaseries.com/apropos/api
   	 * @see method request
  	 */
 	public function userRequest($login,$userRequest,$optionsArray=null)
 	{
 		if (empty($userRequest)) throw new Exception("userRequest is empty");
		if (empty($login)) throw new Exception("login is empty");
		//TODO getUsersCache();
		if (empty($this->usersArray[$login])) throw new Exception("login is unknown. can't perform a user request.");
		$this->connexion->set_token($this->usersArray[$login]['token']);
		$xml=$this->request($userRequest,$optionsArray,$login);
 		$this->connexion->set_token(null);
		return $xml;
 	}
 	
 	
 	/*-----------------------------------*/
 	/*---------Fonction priv�es----------*/
 	/*-----------------------------------*/
 	
 	/*-----------------------------------*/
 	/** Constructeur de la classe
 	 *  
 	 */
 	private function __construct()
 	{
 		$this->connexion = new BetaSeries(constant('BETASERIES_API_KEY'));
 	}
 	
 	/*-----------------------------------*/
 	/** G�n�re les exceptions en fonction des codes d'erreurs retourn�s
 	 * 
 	 */
 	private function makeExceptions($xml)
 	{
 		$errors=null;
 		if (empty($xml)) throw new Exception("Aucun retour lors de l'envoie d'une requette � la base de donn�es");
 		else foreach ($xml->errors->error as $err)
 		{
 			$errors.='Erreur API Betaseries ('.$err->code.')'.$err.'<br />';
 		}
 		if (empty($errors)) throw new Exception($err);
 	}

 	/*-----------------------------------*/
 	/** R�cup�re le token d'un utilisateur 
 	 * 
 	 */
 	private function getToken($login,$password)
 	{
 		$xml = $this->connexion->send_request(constant("BETASERIES_API_ADRESS").'/members/auth.xml',array('login' => $login , 'password' => md5($password)));
 		$token = (string)$xml->member->token;
 		return $token;
 	}
 	/*-----------------------------------*/
 	/* GESTION DU CACHE DES UTILISATEURS */
 	/*-----------------------------------*/
 	private function saveUsersCache()
 	{
 		if (empty($this->usersArray) || count($this->userArray) == 0) delUsersCache();
 		else saveCache(self::$CACHE_PATH_USER,serialize($this->usersArray));
 	}
	private function delUsersCache()
 	{
 		if (cacheExists(self::$CACHE_PATH_USER)) delCache(self::$CACHE_PATH_USER);
 	}
 	private function getUsersCache()
 	{
 		if (empty($this->usersArray) || (count($this->usersArray)<1))
 		{
 			if (cacheExists(self::$CACHE_PATH_USER)) $this->usersArray=unserialize(getCache(self::$CACHE_PATH_USER));
 			else $this->usersArray=array();
 		} 
 	}
 	/*-----------------------------------*/
 	/*-- GESTION DU CACHE D'UNE REQUETE -*/
 	/*-----------------------------------*/
 	private function saveRequestCache($request,$login,$xml)
 	{
 	    saveCache(makeRequestPath($request,$login),serialize($xml));
 	}
 	private function delRequestCache($request,$login)
 	{
 		delCache(makeRequestPath($request,$login));
 	}
 	private function requestCacheExists($request,$login)
 	{
 		return cacheExists(makeRequestPath($request,$login));
 	}
 	private function getRequestCache($request,$login)
 	{
 		$r=makeRequestPath($request,$login);
 		if (cacheExists($r)) return unserialize(getCache($r));
 		else return null;
 	}
 	private function makeRequestPath($request,$login)
 	{
 		$r="";
 		if ($login!=null) $r.= $login.'-';
 		$r.=$request;
 		return $r;
 	}
 	/*-----------------------------------*/
 	/*-------- GESTION DU CACHE ---------*/
 	/*-----------------------------------*/
 	//TODO
 	private function saveCache($file,$content)
 	{

 	}
 	//TODO
	private function delCache($file)
 	{
 		
 	}
 	//TODO
 	private function getCache($file)
 	{
 		
 	}
 	//TODO
	private function cacheExists($file)
 	{
 		
 	}
 }
?>
