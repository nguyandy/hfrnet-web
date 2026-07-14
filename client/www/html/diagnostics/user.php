<?php

require_once("lib/common.php");

// First make sure we are logged in, if not, return false
if( ! isset($_SESSION['user']) ) {
  echo "false";
  exit;
}

// Parse the url
parse_str($_SERVER['QUERY_STRING'],$urlargs);

$db = new outagesDB();

if( isset( $urlargs['action'] ) ){
  switch( $urlargs['action'] ){
    case "changepass":
      changePassword();
      break;
    case "checkloginstatus":
      checkLoginStatus();
      break;
    default:
      break;
  }
}


function changePassword(){
  global $db;
  $oldpass = $_POST['pass1'];
  $newpass = $_POST['pass2'];

  // Check to make sure the old password is correct
  $sql = "SELECT users_id,username,password,salt,email FROM users WHERE username=?";
  $rows = $db->selectQuery($sql, $_SESSION['user']['username']);
  $login_ok=checkPassword($oldpass, $rows[0]['password'],$rows[0]['salt']);
  if( ! $login_ok ) {
    echo '0';    
    exit;
  }

  // Hash my password 
  $newpass = hashMyPassword($newpass,$rows[0]['salt']);

  $sql = "UPDATE users set password=? WHERE username=?";

  $rows = $db->updatingQuery($sql,$newpass,$_SESSION['user']['username']); 
  if( $rows == 1){ 
    echo '1';
  }
  else {
    echo '2';
  }
  exit;
}

// Return 1 if we are logged in otherwise return 0
function checkLoginStatus(){
  if( isset($_SESSION['user']) ){
    echo "1";
  }
  else {
    echo "0";
  }
  exit;
}
?>
