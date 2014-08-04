#!/usr/bin/php
<?php
/* -- Definition des parametres DB et SOAP -- */
define("LOCK_FILE", "/tmp/processStoreQueue.lock");
define("DB_HOST", "127.0.0.1");
define("DB_PORT", 3306);
define("DB_USER", "Sundark");
define("DB_PASS", "");
define("DB_ALLOPASS", "luna_site");
define("SOAP_HOST", "127.0.0.1");
define("SOAP_PORT", 7878);
define("SOAP_USER", "Sundark");
define("SOAP_PASS", "");
define("SOAP_URI", "urn:TC");
define("TYPE_ORDER", 1);
define("TYPE_COMMAND", 2);
//Nombre maximum de remises en file avant mise en erreur
define("NB_FAILED", 3);

$fd = mysql_connect(DB_HOST.":".DB_PORT, DB_USER, DB_PASS, true);
mysql_select_db(DB_ALLOPASS, $fd);
mysql_query('SET NAMES UTF8', $fd);
$gChildPid = 0;

$fp = fopen(LOCK_FILE, "w+");

$soapCred = array("location" => "http://".SOAP_HOST.":".SOAP_PORT."/", "uri" => SOAP_URI, "style" => SOAP_RPC, "login" =>
  SOAP_USER, "password" => SOAP_PASS);
$soapClient = new SoapClient(null, $soapCred);

function host_alive()
{
  $sock = @fsockopen(SOAP_HOST, SOAP_PORT, $ERROR_NO, $ERROR_STR, (float)0.5);
  if ($sock)
  {
    @fclose($sock);
    $soap = true;
  }
  else  $soap = false;

  $sock = @fsockopen(DB_HOST, DB_PORT, $ERROR_NO, $ERROR_STR, (float)0.5);
  if ($sock)
  {
    @fclose($sock);
    $sql = true;
  }
  else  $sql = false;

  return ($soap && $sql);
}

function lockFile($param)
{
  global $fp;

  switch ($param)
  {
      //unlock
    case 0:
      return flock($fp, LOCK_UN);
      //lock
    case 1:
      return flock($fp, LOCK_EX | LOCK_NB);
  }
}

function doCommand($command)
{
  global $soapClient;

  if ($command)
  {
    try
    {
      $result = $soapClient->executeCommand(new SoapParam($command, "command"));
      return array("ok" => true, "result" => $result);
    }
    catch (exception $e)
    {
      return array("ok" => false, "result" => $e->getMessage());
    }
  }
  return array("ok" => false, "result" => "Commande vide !");
}

function processQueue($stat = false)
{
  global $fd;
  $soap_tab = array();
  $sql = mysql_query("SELECT * FROM soap_queue WHERE failed<'".NB_FAILED."' ORDER BY id ASC", $fd);
  if (mysql_num_rows($sql) === 0) return false;
  while ($soap = mysql_fetch_object($sql))
  {
    $soap_tab[] = $soap;
  }
  $pSoap_tab = &$soap_tab;
  foreach ($pSoap_tab as $soap)
  {
    print "[".$soap->id."] Traitement de la commande ".$soap->command."... ";
    $result = doCommand($soap->command);
    if (preg_match("/Cette sous-commande n'existe pas./", $result['result']) or preg_match("/Cette commande n'existe pas./", $result['result']) or
      preg_match("/There is no such command/", $result['result']) or preg_match("/There is no such subcommand/", $result['result']))
    {
      $result["ok"] = false;
    }
    if ($result["ok"])
    {
      print "[OK]\n";
      if ($soap->type == TYPE_ORDER) //mysql_query("UPDATE command SET statut='1', mj='1' WHERE guid='".$soap->guid."'", $fd);
           print "[".$soap->id."] Type order\n";
      print "[".$soap->id."] Suppression de la file ";
      mysql_query("DELETE FROM soap_queue WHERE id='".$soap->id."'", $fd);
      print "[OK]\n";
    }
    else
    {
      print "[FAILED]\n";
      $soap->failed++;
      $soap->error = $result["result"];
      print "[".$soap->id."] ".$soap->failed." erreurs sur cette commande...\n";
      $pSoap_tab[] = $soap;
      if ($soap->failed >= NB_FAILED)
      {
        print "[".$soap->id."] Commande en erreur... ";
        mysql_query("UPDATE soap_queue SET failed='".$soap->failed."', error='".mysql_real_escape_string($soap->error).
          "' WHERE id=".$soap->id."", $fd);
        print "[OK]\n";
        return $stat;
      }
      else
      {
        print "[".$soap->id."] Remise en file d'attente... ";
        mysql_query("UPDATE soap_queue SET failed='".$soap->failed."' WHERE id=".$soap->id."", $fd);
        print "[OK]\n";
      }
    }
    sleep(1);
  }
  return $stat;
}

function showQueue()
{
  global $fd;
  $soap_tab = array();
  $sql = mysql_query("SELECT * FROM soap_queue ORDER BY id ASC", $fd) or print(mysql_error());
  if (mysql_num_rows($sql) === 0) return false;
  while ($soap = mysql_fetch_object($sql))
  {
    $soap_tab[] = $soap;
  }
  $pSoap_tab = &$soap_tab;
  foreach ($pSoap_tab as $soap)
  {
    if ($soap->failed == NB_FAILED)
      $sayit = '(en ERREUR) ';
    else
      $sayit = '';
    print "[".$soap->id."] Commande dans la queue $sayit: ".$soap->command."...\n";
  }
  return true;
}

function deleteQueue($task_id)
{
  global $fd;
  $soap_tab = array();
  $sql = mysql_query("SELECT * FROM soap_queue WHERE id=$task_id ORDER BY id ASC", $fd);
  if (mysql_num_rows($sql) !== 0)
  {
    mysql_query("DELETE FROM soap_queue WHERE id=$task_id;", $fd);
    print "Suppression reussi... \n";
  }
  else
  {
    print "Echec lors de la suppression...\n";
  }
  return true;
}

function addQueue($task_add)
{
  global $fd;
  $soap_tab = array();
  $task_add = mysql_real_escape_string($task_add, $fd);
  if (mysql_query("INSERT INTO `soap_queue` (`id`, `guid`, `command`, `type`, `failed`, `error`) VALUES (null, '', '{$task_add}', '1', '0', 'no error');", $fd))
  {
    print "Ajout en file reussi... \n";
  }
  else
  {
    print "Echec lors de l'ajout en file...\n";
  }
  return true;
}

function resubmitQueue($task_id)
{
  global $fd;
  $sql = mysql_query("SELECT * FROM soap_queue WHERE id=$task_id ORDER BY id ASC", $fd);
  if (mysql_num_rows($sql) !== 0)
  {
    mysql_query("UPDATE soap_queue SET failed='0' WHERE id=$task_id;", $fd);
    print "Remise en file reussi... \n";
  }
  else
  {
    print "Echec lors de la remise en file...\n";
  }
  return true;
}

function child()
{
  while (1)
  {
    if (host_alive())
    {
      print "queue flush\n";
      print "Traitement de la file d'attente...\n";
      if (processQueue(true)) print "SoapQueue > ";
    }
    else
    {
      print "Serveur de connexion down\n";
      sleep(5);
    }
    sleep(10);
  }

}

function parse_command($command)
{
  global $gChildPid;
  global $fp;
  global $fd;

  if (preg_match("/exit/", $command))
  {
    mysql_close($fd);
    posix_kill($gChildPid, SIGTERM);
    pcntl_waitpid(-1, $status);
    print "Deverrouillage du verrou... ";
    if (!lockFile(0))
    {
      fclose($fp);
      die("Impossible de deverrouiller ".LOCK_FILE." !\n");
    }
    print "[OK]\n";
    exit;
    break;
  } elseif (preg_match("/queue\n/", $command))
  {
    print "Les sous commandes pour la commande queue sont :\n";
    print "  queue add {\"commande\"} (experimental)\n";
    print "  queue delete {task_id}\n";
    print "  queue flush\n";
    print "  queue list\n";
    print "  queue resubmit {task_id}\n";
    return array('ok' => true, 'msg' => "");
  } elseif (preg_match("/queue list\n/", $command))
  {
    print "Affichage de la file d'attente...\n";
    showQueue();
    return array('ok' => true, 'msg' => "");
  } elseif (preg_match("/queue flush\n/", $command))
  {
    print "Traitement de la file d'attente...\n";
    if (processQueue(false)) print "\nSoapQueue > ";
    return array('ok' => true, 'msg' => "");
  } elseif (preg_match("/queue resubmit/", $command))
  {
    preg_match_all('#[0-9]+#', $command, $extract);
    $task_id = intval($extract[0][0]);
    if ($task_id !== 0)
    {
      print "Tentative de remise en file de la commande en erreur num: $task_id ...\n";
    }
    else
    {
      print "Veuillez entrer une valeur correct pour task_id ...\n";
    }
    resubmitQueue($task_id);
    return array('ok' => true, 'msg' => "");
  } elseif (preg_match("/queue delete/", $command))
  {
    preg_match_all('#[0-9]+#', $command, $extract);
    $task_id = intval($extract[0][0]);
    if ($task_id !== 0)
    {
      print "Suppression de la commande en file num: $task_id ...\n";
      deleteQueue($task_id);
    }
    else
    {
      print "Veuillez entrer une valeur correct pour task_id ...\n";
    }
    return array('ok' => true, 'msg' => "");
  } elseif (preg_match("/queue add/", $command))
  {
    $command = str_replace('queue add ', '', $command);
    $new_task = trim($command);
    if (!empty($new_task))
    {
      print "Ajout de la commande : $new_task ...\n";
      addQueue($new_task);
    }
    else
    {
      print "Veuillez entrer une valeur correct pour new_task ...\n";
    }
    return array('ok' => true, 'msg' => "");
  }
  else
  {
    return array('ok' => false, 'msg' => "Commande inexistante.");
  }
}

function cli()
{
  if (!defined("STDIN"))
  {
    define("STDIN", fopen('php://stdin', 'r'));
  }

  print "SoapQueue > ";
  while ($line = fread(STDIN, 1024))
  {
    if (preg_match("/(.+?)+/", $line))
    {
      $result = parse_command($line);
      if ($result['ok']) print $result['msg'];
      else  print "ERROR: ".$result['msg']."\n";
    }
    print "SoapQueue > ";
  }
}

function main()
{
  global $gChildPid;

  $gChildPid = pcntl_fork();
  if ($gChildPid == -1)
  {
    print "Impossible de lancer le processus fils.";
    exit;
  } elseif ($gChildPid) cli();
  else  child();
}

print "Creation et verrouillage du verrou... ";
if (!lockFile(1))
{
  fclose($fp);
  die("Impossible de verrouiller ".LOCK_FILE." !\n");
}
print "[OK]\n";

main();
?>
