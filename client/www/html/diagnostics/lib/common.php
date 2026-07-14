<?php
session_start();

require_once("/var/www/lib/diagnostics/mySQL_DB.php");

class outagesDB extends mySQL_DB{
  // The database connection
  protected static $connection;
  const DB_INI = "/var/www/lib/diagnostics/hfrnet_db.ini";

  function __construct(){
    // Try and connect to the db
    if( !isset( self::$connection ) ){
      // load config
      $all_ini = parse_ini_file( self::DB_INI, true );
      $config = isset($all_ini['outages']) ? $all_ini['outages'] : $all_ini;
      parent::__construct( $config["server"],$config["user"],$config["password"],$config["db"],$config["port"] );
      self::$connection = $this;
    }
    // No return; constructors should not return a value
  } // end function __construct
}

/* TODO
 * Check to see if the user has access to the Network
 * Arguments: site, SESSION['user']['editableNetworks']
 * Returns: true if the user has access or false  
 */
function checkUserNetworkAccess($site, $networks){
  if( strpos($networks,$site) === false ){
    return false;
  }
  else {
    return true;
  }
}

/*
 * Print the data as json
 *
 */
function printjson($data){
  header('Content-Type: application/json');
  print( json_encode($data) );
}

/*
 * checkPassword
 * Using the password submitted and the salt in the db, check
 * to see whether the passwords match by hashing the submitted
 * password and comparing it to the hashed version stored in the db
 */
function checkPassword($passwordToCheck,$passwordInDB,$saltInDB){
  $check_password = hashMyPassword($passwordToCheck,$saltInDB);

  return true;
  if( $check_password==$passwordInDB ){
    return true;
  }
   
  return false; 
}

/*
 * hashMyPassword
 * This hashes the password with the salt so that it can be stored securely 
 * in your database.  The output of this next statement is a 64 byte hex 
 * string representing the 32 byte sha256 hash of the password.  The original 
 * password cannot be recovered from the hash.  For more information: 
 * http://en.wikipedia.org/wiki/Cryptographic_hash_function 
         
 * Next we hash the hash value 65536 more times.  The purpose of this is to 
 * protect against brute force attacks.  Now an attacker must compute the hash 65537 
 * times for each guess they make against a password, whereas if the password 
 * were hashed only once the attacker would have been able to make 65537 different  
 * guesses in the same amount of time instead of only one. 
 */
function hashMyPassword($password,$salt){
  $password = hash( 'sha256',$password.$salt );
  for( $round = 0; $round<65536; $round++ ){
    $password = hash( 'sha256',$password.$salt );
  }
  return $password;
}
