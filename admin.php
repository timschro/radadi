<?php

set_time_limit(60);
error_reporting(-1);
require_once "config.php";



?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<script src="js/jquery-2.2.3.min.js"></script>

<title>radadi - Race Data Display</title>
<style type="text/css" media="screen">


</style>
</head>

<body>
<div id="radaheader">
  <h1><?=$eventconfig['eventname'] ?></h1>
</div>
<div id="radaspace">


  <?php
  $clients = $database->select("clients", ["ip"]);
  foreach($clients as $client) {
    var_dump($client);
    echo $client['ip'];
  }






  ?>



</div>
<!-- /Error popup -->

</body></html>
