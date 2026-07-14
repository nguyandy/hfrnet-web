<?php
// Command line tool to reset a forgotten password
// usage: resetPassword.php <user> <password>

require("lib/common.php");
$db = new outagesDB();

if( empty( $argv[1] ) && empty( $argv[2] ) ){
  die("Please enter a username and password as the first two arguments.\n");
}

// Check to see if the user is a valid user
$query = "SELECT salt from users WHERE username=?";
$rows = $db->selectQuery($query,$argv[1]);
if( count( $rows ) == 0 ){
  die("user " . $argv[1] . " not found\n.");
}
$salt = $rows[0]['salt'];

$query = "UPDATE users SET password=? WHERE username=?";
$password = hashMyPassword($argv[2],$salt);
print "query: $query\n";
print "password: ".$argv[2]." $salt $password\n";
print "user: ".$argv[1]."\n";

$rows = $db->updatingQuery($query,$password,$argv[1]);

if( $rows ){
  die( $argv[1] . " password was changed.\n");
}
else {
  die("Error changing password for ".$argv[1]."\n");
}
 


?>
