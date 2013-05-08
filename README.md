importgmail
===========

An standalone PHP library to import GMail contacts.

Before starting you should go to: https://code.google.com/apis/console/  , create a 
new application and get an API access.

How to use it ? 

<?php 
  require_once 'importGmail.php';
  
  $client_id = '';
  $client_secret = ''; 
  $redirect_uri = 'http://localhost/this_file.php?back=true'; // Please change it with the url of this file on your server! Use the back parameter, in order to avoid cycle redirect.
  $max_results = 50;

  $g = new importGmail($client_id, $client_secret, $redirect_uri, $max_results);
  
  if ( ! $g->isConnected() && !isset($_GET['back']) )
    $g->redirect(false);

  // he is connected, try to get the contact list
  $r = $g->getContacts();
  
  print_r($r);

?>
