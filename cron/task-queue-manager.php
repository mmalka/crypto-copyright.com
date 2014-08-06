#!/usr/bin/php
<?php

/* -- Definition des parametres DB et SOAP -- */
 define("LOCK_FILE", "/tmp/processQueue.lock");
 define("DB_HOST", "localhost");
 define("DB_PORT", 3306);
 define("DB_USER", "cryptocopyright");
 define("DB_PASS", "");
 define("DB_NAME", "cryptocopyright");
 define("DB_CHARSET", "utf8");

//Nombre maximum de remises en file avant mise en erreur
 define("NB_FAILED", 3);
 $service_price = 0.005;
 $debuglevel = 'INFO';

 $db = new PDO('mysql:host='.DB_HOST.':'.DB_PORT.';dbname='.DB_NAME.';charset='.
     DB_CHARSET, DB_USER, DB_PASS);
 $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
 $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
 $queueOfEventsPid = 0;
 $bitcoinTransactionsCronPid = 0;

 $fp = fopen(LOCK_FILE, "w+");

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

 function processTransactionsFromWallet()
 {
     global $db;
     // add transactions into payments
     // update transaction when confirmed
 }

 function bitcoinTransactionsCron()
 {
     while (1)
     {
         processTransactionsFromWallet();
         sleep(15);
     }
 }
function generateBitcoinAddress($hash_id)
{
    $address = "1MDo23U4X1VRxMRC626xNW2Rciuxx4cpXB";
    if (!ctype_xdigit($generatedBitcoinAddress) || strlen($generatedBitcoinAddress) != 64)
        {
            logging("Attempt to generate a BTC address for hash_id: {$hash_id} has failed.");
            return false;
        }
    logging("BTC address: {$address} have been generated for hash_id: {$hash_id}."); 
    return $address;
}
 function processQueue()
 {
     global $db, $service_price;
     try
     {
         // the following SQL query will return all non-completed hash_id and last state. DONE/CANCELLED will be ignored !
         $queryevents = $db->prepare("SELECT e1.new_state, e1.hash_id FROM crypto_events e1 INNER JOIN (SELECT max(e.event_id) LastEvent, e.new_state, e.hash_id FROM crypto_events e WHERE e.hash_id IN (SELECT hash_id FROM crypto_hashs WHERE done = 0) GROUP BY e.hash_id) e2 ON e1.hash_id = e2.hash_id AND e1.event_id = e2.LastEvent order by e1.hash_id asc;");
         // http://sqlfiddle.com/#!2/927392/11 - perfect results

         $queryevents->execute();
         if ($queryevents->rowCount() === 0)
            logging('There is nothing to work on.', 'INFO');
         while ($result = $queryevents->fetch(PDO::FETCH_OBJ))
         {
             if ($result->new_state == null) continue;
             $hash_id = $result->hash_id;
             logging("Check hash_id: {$hash_id}, current state {$result->new_state}...", "INFO");
             switch ($result->new_state)
             {
                 case "cryptoproof_submission_opened":
                     $getHash = $db->prepare("SELECT `data_digest`,`payment_address` FROM `crypto_hashs` WHERE `hash_id` = :hash_id;");
                     $getHash->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
                     $getHash->execute();
                     $result = $getHash->fetch(PDO::FETCH_OBJ);

                     if (!ctype_xdigit($result->data_digest) || strlen($result->data_digest) != 56)
                     {
                         createEventByHashId($hash_id, 'cryptoproof_submission_opened', 'cryptoproof_submission_cancelled', "The hash_id {$hash_id} don't contains a valid SHA-3/224 digest.");
                         if (!setDoneToHashId($hash_id, -1))
                            logging("!setDoneToHashId($hash_id, -1)", "ERROR");
                         break;
                     }
                     if (!ctype_xdigit($result->payment_address) || strlen($result->payment_address) != 64)
                     {
                        $generatedBitcoinAddress = generateBitcoinAddress($hash_id);
                        if(!$generatedBitCoinAddress)
                            break;
                        createEventByHashId($hash_id, 'cryptoproof_submission_opened', 'payment_pending', "Create bitcoin address: {$generatedBitcoinAddress}.");
                        break;
                     }
                     createEventByHashId($hash_id, 'cryptoproof_submission_opened', 'payment_pending', "");
                    break;
                 case "payment_pending":
                     $payment_array = getPaymentsForHash($result->hash_id);
                     
                     if ($payment_array[0] + $payment_array[1] > 0)
                     {
                         createEventByHashId($hash_id, 'payment_pending', 'payment_pending_confirmation', '');
                         break;
                     }
                     $getHashSubmissionTimestamp = $db->prepare("SELECT `timestamp` FROM `crypto_hashs` WHERE `hash_id` = :hash_id;");
                     $getHashSubmissionTimestamp->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
                     $getHashSubmissionTimestamp->execute();
                     $result = $getHashSubmissionTimestamp->fetch(PDO::FETCH_OBJ);
                     if ($result->timestamp < time() - 21600)
                     {
                         createEventByHashId($hash_id, 'payment_pending', 'cryptoproof_submission_cancelled', '$result->timestamp < time()-21600');
                         if (!setDoneToHashId($hash_id, -1))
                            logging("!setDoneToHashId($hash_id, -1)", "ERROR");
                     }
                     break;
                 case "payment_pending_confirmation":
                     $payment_array = getPaymentsForHash($result->hash_id);
                     if ($payment_array[0] >= $service_price)
                     {
                         createEventByHashId($hash_id, 'payment_pending_confirmation', 'payment_approved', '');
                         break;
                     }
                     if ($payment_array[0] + $payment_array[1] < $service_price)
                     {
                         // We didn't received enough payments (confirmed/unconfirmed).
                         // We just want to check if the last payment is not too old.
                         if ($payment_array[2] < time() - 604800)
                         {
                             createEventByHashId($hash_id, 'payment_pending_confirmation',
                                 cryptoproof_submission_cancelled, '$payment_array[2] < time()-604800');
                             if (!setDoneToHashId($hash_id, -1))
                                logging("!setDoneToHashId($hash_id, -1)", "ERROR");
                         }
                     }
                     break;
                 case "payment_approved":
                     $getHashs = $db->prepare("SELECT `data_digest`,`data_digest2`,`transactionid`,`transactionid2` FROM `crypto_hashs` WHERE `hash_id` = :hash_id;");
                     $getHashs->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
                     $getHashs->execute();
                     $result = $getHashs->fetch(PDO::FETCH_OBJ);
                     if (!ctype_xdigit($result->data_digest) || strlen($result->data_digest) != 56)
                     {
                         logging("The hash_id {$hash_id} contains no valid SHA-3/224 data_digest", 'ERROR');
                         break;
                     }
                     if (ctype_xdigit($result->data_digest2) && strlen($result->data_digest2) == 56)
                     {
                         $tx2 = $result->transactionid2;
                         if ($tx2 == "") $tx2 = createOpReturnByHash($hash_id, $result->data_digest2);
                         if ((ctype_xdigit($tx2) && strlen($tx2) == 64) && (!ctype_xdigit($result->
                             transactionid2) || strlen($result->transactionid2) != 64))
                         {
                             // update db for tx2
                             break; // submit second hash before the first one for improved efficacity (a hash naming the owner existed before the actual hash been submitted)
                         }
                     }
                     $tx = $result->transactionid;
                     if ($tx == "") $tx = createOpReturnByHash($hash_id, $result->data_digest);
                     if ((ctype_xdigit($tx) && strlen($tx) == 64) && (!ctype_xdigit($result->
                         transactionid) || strlen($result->transactionid) != 64))
                     {
                         // update db for tx
                     }
                     if (ctype_xdigit($tx) && strlen($tx) == 64)
                     {
                         createEventByHashId($hash_id, 'payment_approved', 'cryptoproof_sent', '');
                     }
                     break;
                 case "cryptoproof_sent":
                     $getOpReturnTxs = $db->prepare("SELECT `data_digest`,`data_digest2`,`transactionid`,`transactionid2` FROM `crypto_hashs` WHERE `hash_id` = :hash_id;");
                     $getOpReturnTxs->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
                     $getOpReturnTxs->execute();
                     $result = $getOpReturnTxs->fetch(PDO::FETCH_OBJ);

                     if (!ctype_xdigit($result->data_digest) || strlen($result->data_digest) != 56 ||
                         !ctype_xdigit($result->transactionid) || strlen($result->transactionid) != 64)
                     {
                         logging("The hash_id {$hash_id} don't contains a valid data_digest/transactionid", 'ERROR');
                         break;
                     }
                     if (ctype_xdigit($result->data_digest2) && strlen($result->data_digest2) == 56 &&
                         ctype_xdigit($result->transactionid2) && strlen($result->transactionid2) == 64)
                     {
                         if (getTransactionConfirmations($hash_id, $result->transactionid) > 0 &&
                             getTransactionConfirmations($hash_id, $result->transactionid2) > 0)
                         {
                             createEventByHashId($hash_id, 'cryptoproof_sent', 'cryptoproof_guaranteed', '');
                             if (!setDoneToHashId($hash_id, 1))
                                logging("!setDoneToHashId($hash_id, 1)", "ERROR");
                         }
                         break;
                     }
                     if (getTransactionConfirmations($hash_id, $result->transactionid) > 0)
                     {
                         createEventByHashId($hash_id, 'cryptoproof_sent', 'cryptoproof_guaranteed', '');
                         if (!setDoneToHashId($hash_id, 1))
                            logging("!setDoneToHashId($hash_id, 1)", "ERROR");
                     }
                     break;
             }
             sleep(1);
         }
     }
    catch (Exception $e)
    {
        echo 'Exception reçue : ',  $e->getMessage(), "\n";
    }
 }

 function setDoneToHashId($hash_id, $done)
 {
     global $db, $service_price;
     $update = $db->prepare("UPDATE `crypto_hashs` SET `done` = :done WHERE `hash_id`=:hash_id;");
     $update->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
     $update->bindValue(':done', $done, PDO::PARAM_INT);
     $update->execute();
     $affected_rows = $update->rowCount();
     if ($affected_rows === 0) return false;
     switch($done)
     {
        case -1:
            $done = "CANCELLED";
            break;
        case 0:
            $done = "PROCESSING";
            break;
        case 1:
            $done = "PROCESSED";
            break;
     }
     logging("Set main state of hash_id '{$hash_id}' to {$done}."); 
     return true;
 }

 function getTransactionConfirmations($hash_id, $txid)
 {
     $exec = exec("gettxconfirmations.py {$txid}");
     if (is_numeric($exec) && $exec > 0)
     {
         // createEventByHashId($hash_id, 'payment_approved', 'cryptoproof_sent', '');
         return $exec;
     }
     return 1;
 }

 function createOpReturnByHash($hash_id, $hash)
 {
     $command = escapeshellcmd('python3 /home/crypto-copyright/corporate/www/timestamp-op-ret.py -s '.$hash);
     // secondary hash later
     $output = array();
     $txid = '';
     //exec($command, $output);
     $output[0] = "2f1f39e6a1eee546887d556dc7c231b24e68243b157f2bd2750b1d260de01a22";
     foreach ($output as $line)
     {
        $txid .= $line;
     }
     var_dump($txid);
     echo ctype_xdigit($txid);
     if (ctype_xdigit($txid) && strlen($txid) == 64)
     {
         // DoAction: Insert the tx-id returned by timestamp-op-ret.py to crypto_hashs WHERE hashid.
         createEventByHashId($hash_id, 'payment_approved', 'cryptoproof_sent', '');
         return $txid;
     }
 }

 function createEventByHashId($hash_id, $old_state, $new_state, $details = '')
 {
     global $db;
     /*
     Available events:
     'null',
     'cryptoproof_submission_opened',
     'payment_pending',
     'cryptoproof_submission_cancelled',
     'payment_pending_confirmation',
     'payment_approved',
     'cryptoproof_sent',
     'cryptoproof_pending_confirmation',
     'cryptoproof_guaranteed'
     */
     $eventBuilder = $db->prepare("INSERT INTO `cryptocopyright`.`crypto_events` (`event_id`, `hash_id`, `timestamp`, `old_state`, `new_state`, `details`) VALUES (NULL, :hash_id, :timestamp, :old_state, :new_state, :details)");
     $eventBuilder->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
     $eventBuilder->bindValue(':timestamp', time(), PDO::PARAM_INT);
     $eventBuilder->bindValue(':old_state', $old_state, PDO::PARAM_STR);
     $eventBuilder->bindValue(':new_state', $new_state, PDO::PARAM_STR);
     $eventBuilder->bindValue(':details', $details, PDO::PARAM_STR);
     $eventBuilder->execute();
     $affected_rows = $eventBuilder->rowCount();
     if ($details != "")
        $details = " Details: ".$details;
     if ($affected_rows === 0)
        logging("Update event status for hash_id: {$hash_id}, set the new status to {$new_state}, from {$old_state}.{$details}", "ERROR");
     else
        logging("Update event status for hash_id: {$hash_id}, set the new status to {$new_state}, from {$old_state}.{$details}", "INFO");
 }

 function getPaymentsForHash($hash_id)
 {
     global $db, $service_price;
     $confirmed_balance = 0;
     $unconfirmed_balance = 0;
     $last_timestamp = 0;
     $paymentsquery = $db->prepare("SELECT `btc`, `confirmed`, `timestamp` FROM `crypto_payments` WHERE `hash_id`=:hash_id ORDER BY `timestamp`;");
     $paymentsquery->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
     $paymentsquery->execute();
     if ($paymentsquery->rowCount() === 0)
        return array($confirmed_balance, $unconfirmed_balance);

     while ($result = $paymentsquery->fetch(PDO::FETCH_OBJ))
     {
         if ($result->confirmed == 1)
         {
             $confirmed_balance += $result->btc;
             if ($confirmed_balance >= $service_price) break;
             continue;
         }
         $unconfirmed_balance += $result->btc;
         $last_timestamp = $result->timestamp;
     }
     return array($confirmed_balance, $unconfirmed_balance, $last_timestamp);
 }

 function logging($msg, $level = 'NORMAL')
 {
     global $debuglevel;
     echo "{$level}: {$msg}\n";
     // ToDo: Write on file.
     // ToDo: Handle debuglevel bitmask.
 }

 function showQueue($showCancelled = false)
 {
     global $db;
     // show all hashs status (not completed/cancelled)
 }

 function deleteQueue($task_id)
 {
     global $db;
     // manually cancel a hash
 }

 function addQueue($task_add)
 {
     global $db;
     // manually add a hash
 }

 function resubmitQueue($task_id)
 {
     global $db;
     // manually set a cancelled hash back to its previous status
 }

 function queueOfEvents()
 {
     while (1)
     {
         if (processQueue()) print "CryptoCopyright > ";
         sleep(5);
     }

 }

 function parse_command($command)
 {
     global $queueOfEventsPid;
     global $bitcoinTransactionsCronPid;
     global $fp;
     global $db;

     if (preg_match("/exit/", $command))
     {
         mysql_close($db);
         posix_kill($queueOfEventsPid, SIGTERM);
         posix_kill($bitcoinTransactionsCronPid, SIGTERM);
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
     }
     elseif(preg_match("/queue\n/", $command))
     {
         print "Les sous commandes pour la commande queue sont :\n";
         print "  queue add {\"commande\"} (experimental)\n";
         print "  queue delete {task_id}\n";
         print "  queue flush\n";
         print "  queue list\n";
         print "  queue resubmit {task_id}\n";
         return array('ok' => true, 'msg' => "");
     }
     elseif(preg_match("/queue list\n/", $command))
     {
         print "Affichage de la file d'attente...\n";
         showQueue();
         return array('ok' => true, 'msg' => "");
     }
     elseif(preg_match("/queue flush\n/", $command))
     {
         print "Traitement de la file d'attente...\n";
         if (processQueue(false)) print "\nSoapQueue > ";
         return array('ok' => true, 'msg' => "");
     }
     elseif(preg_match("/queue resubmit/", $command))
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
     }
     elseif(preg_match("/queue delete/", $command))
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
     }
     elseif(preg_match("/queue add/", $command))
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

 function console_handling()
 {
     global $bitcoinTransactionsCronPid;

     $bitcoinTransactionsCronPid = pcntl_fork();
     if ($bitcoinTransactionsCronPid == -1)
     {
         print "unable to fork console_handling() to bitcoinTransactionsCron().";
         exit;
     }
     else
         if ($bitcoinTransactionsCronPid)
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
         else  bitcoinTransactionsCron();
 }

 function main()
 {
     global $queueOfEventsPid;

     $queueOfEventsPid = pcntl_fork();
     if ($queueOfEventsPid == -1)
     {
         print "unable to fork main() to queueOfEvents().";
         exit;
     }
     else
         if ($queueOfEventsPid) 
            bitcoinTransactionsCron();
         else
            queueOfEvents();
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
