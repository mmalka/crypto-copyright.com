#!/usr/bin/php
<?php
define('MYSQL_HOST', 'localhost');
define('MYSQL_LOGIN', '');
define('MYSQL_PASS', '');
$link = @mysql_connect(MYSQL_HOST, MYSQL_LOGIN, MYSQL_PASS);

function getonlineplayersnbr()
{
  $getonline = mysql_query("SELECT count(guid) FROM luna_characters.characters WHERE online = 1;");
  return mysql_result($getonline, 0);
}

function getonlineplayers()
{
  $getonline = mysql_query("SELECT guid FROM luna_characters.characters WHERE online = 1;");
  while ($row = mysql_fetch_array($getonline))
  {
    $players[$row['guid']] = time();
  }
  return $players;
}

function getaccountnbr()
{
  $getonline = mysql_query("SELECT count(id) FROM luna_auth.account;");
  return mysql_result($getonline, 0);
}

function build_stats()
{
  $d = date('d');
  $m = date('m');
  $Y = date('Y');
  $H = date('H');
  $W = date('W');
  $i = date('i');
  $online = getonlineplayersnbr();
  $compte = getaccountnbr();
  mysql_query("INSERT INTO `luna_site`.`online` (`id`, `time`, `d`, `m`, `Y`, `H`, `W`, `i`, `online`, `compte`) VALUES (NULL, '".
    time()."', '$d', '$m', '$Y', '$H', '$W', '$i', '$online', '$compte');");
}

function build_onlinechars()
{
  $players = getonlineplayers();
  $time10 = time() - 600;
  mysql_query("DELETE FROM luna_site.online_perso WHERE time < $time10;");
  foreach ($players as $key => $value)
  {
    mysql_query("INSERT IGNORE INTO `luna_site`.`online_perso` (`id`, `guid`, `time`) VALUES (NULL, '$key', '$value');");
  }
}

if ($link)
{
  echo 'connected';
  build_stats();
  build_onlinechars();
}
else
{
  echo 'problème de connexion';
}
?>
