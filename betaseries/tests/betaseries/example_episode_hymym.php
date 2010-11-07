<?php

/**
 *
 * Utilisation de l'API BetaSeries.
 * Exemple : Identification d'un membre puis r�cup�ration des s�ries d'un membre
 * 
 *
 * @package BetaSeries
 *
 */
require_once '../../config/config_betaseries.php';
require_once '../../includes/betaseries/class_betaseries.php';

/**
 *
 * CléPI, nom et mot de passe utilisateur àenseigner.
 *
 */

$API_KEY = constant('BETASERIES_API_KEY');
$ACCOUNT_USERNAME = constant('ACCOUNT_USERNAME');
$ACCOUNT_PASSWORD = constant('ACCOUNT_PASSWORD');

/**
 *
 * Construction de la classe avec la cléPI.
 *
 */

$b = new BetaSeries($API_KEY);

/**
 *
 * Appel de l'API pour identifier l'utilisateur.
 *
 */

$xml = $b->send_request('http://api.betaseries.com/members/auth.xml',array('login' => $ACCOUNT_USERNAME , 'password' => md5($ACCOUNT_PASSWORD)));

/**
 *
 * On met le token de l'utilisateur dans une variable et on
 * configure la classe avec celle-ci.
 *
 */

$token = (string)$xml->member->token;
$b->set_token($token);

/**
 *
 * Appel de l'API pour afficher des informations de base sur le membre.
 *
 */

$xml = $b->send_request('http://api.betaseries.com/shows/episodes/himym.xml');

/**
 *
 * Sortie var_dump() de l'objet répé.
 *
 */

var_dump($xml);

?>
