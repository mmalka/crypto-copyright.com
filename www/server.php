<?php
// Awesome server file
if (isset($_GET['hash']) && isset($_GET['doit_protectedSDfdsk']))
{
  $hash = htmlentities($_GET['hash']);
  $doIt = $_GET['doit_protectedSDfdsk'];
  if($doIt != "124584s")
    exit(0);
  if (0 == strcspn($hash, '0123456789abcdef') && strlen($hash) == 56)
  {
    $command = escapeshellcmd('python3 /home/crypto-copyright/corporate/www/timestamp-op-ret.py '. $hash);
	$output = array();
    exec($command, $output);
    foreach($output as $line)
    {
      echo $line."<br />";
    }
  }
}