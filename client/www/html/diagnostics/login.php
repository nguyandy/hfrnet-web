<?php
require_once("lib/common.php");
$db = new outagesDB();

$submitted_username = '';
$login_ok = false;

// This checks to see if the login form has been submitted
// If it has, then the login code is run, otherwise the form is displayed
if (!empty($_POST)) {
  $query = "SELECT users_id,username,password,salt,email FROM users WHERE username=?";

  // Retrieve user data from db.  If row is false, then username is not registered
  $rows = $db->selectQuery($query, $_POST['user']);
  if ($rows) {
    $login_ok = checkPassword($_POST['pass'], $rows[0]['password'], $rows[0]['salt']);
  }

  if ($login_ok) {
    unset($rows[0]['salt']);
    unset($rows[0]['password']);
    // This stores the users data into the session at the index 'user'
    // We will check this index on the private page to determine whether
    // or not the user is logged in. 
    $_SESSION['user'] = $rows[0];

    // If login is okay, figure out which networks and stations they can edit outages for
    $query = "SELECT s.sta
              FROM users left join users_networks un on users.users_id = un.users_id left join hfradar.network n ON un.network_id = n.network_id LEFT JOIN hfradar.site s on s.network_id = n.network_id
              WHERE users.username=?";
    $rows = array();
    $rows = $db->selectQuery($query, $_POST['user']);
    $stas = "";
    foreach ($rows as $row) {
      $stas .= $row["sta"] . ",";
    }
    $_SESSION['user']['editableNetworks'] = $stas;
    //echo "true";
    echo $_SESSION['user']['username'] . "," . $_SESSION['user']['email'];
  } else {
    echo "false";
  }
}
