#!/usr/bin/php
<?php
require_once 'json-rpc/jsonRPCClient.php';
define("DB_HOST", "localhost");
define("DB_PORT", 3306);
define("DB_USER", "user");
define("DB_PASS", "pass");
define("DB_NAME", "cryptocopyright");
define("DB_CHARSET", "utf8");
define("BTCD_HOST", "localhost");
define("BTCD_PORT", "8332");
define("BTCD_USER", "bitcoinrpc");
define("BTCD_PASS", "pass");

$service_price = 0.005; // todo: use payment_btc from crypto_hashs table.
$debuglevel = 'INFO'; // todo: make a bitmask system for debugging level
//$fp = fopen(LOCK_FILE, "w+"); // todo: make logging to file

$db = new PDO('mysql:host='.DB_HOST.':'.DB_PORT.';dbname='.DB_NAME.';charset='.DB_CHARSET, DB_USER, DB_PASS);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$db2 = new PDO('mysql:host='.DB_HOST.':'.DB_PORT.';dbname='.DB_NAME.';charset='.DB_CHARSET, DB_USER, DB_PASS);
$db2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
$db2->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

try
{
    $bitcoindrpc = @new jsonRPCClient('http://'.BTCD_USER.':'.BTCD_PASS.'@'.BTCD_HOST.':'.BTCD_PORT.'/');
}
catch (exception $e)
{
    logging($e, 'ERROR');
    $generatedBitcoinAddress = "";
}

$queueOfEventsPid = 0;
$bitcoinTransactionsCronPid = 0;

function processTransactionsFromWallet($hash_id, $payment_address, $confirmed, $unconfirmed)
{
    global $db2, $bitcoindrpc;
    $account_name = "payment_address_{$hash_id}";
    logging("parsing transactions for hash_id={$hash_id}...");
    try
    {
        $txs = @$bitcoindrpc->listtransactions($account_name, 1000, 0);
    }
    catch (exception $e)
    {
        logging($e, 'ERROR');
        $txs = "";
    }
    logging(count($txs, 0)." transactions founds...");
    $count_passed = 0;
    $transactions = array();
    foreach ($txs as $value)
    {
        if ($value['category'] != "receive")
        {
            $count_passed++;
            continue;
        }
        if ($value['address'] != $payment_address)
        {
            $count_passed++; // an account "may" contains more address, but we manage only one of them.
            continue;
        }
        if (in_array($value['txid'], $confirmed))
        {
            $count_passed++;
            continue;
        }
        if ($value['timereceived'] < $value['time']) $time = $value['timereceived'];
        else  $time = $value['time'];
        $transaction = array();
        $transaction['transactionid'] = $value['txid'];
        $transaction['btc'] = $value['amount'];
        $transaction['confirmed'] = $value['confirmations'] >= 1?1:0;
        $transaction['timestamp'] = $time;
        if (in_array($transaction['transactionid'], $unconfirmed))
        {
            $update = $db2->prepare("UPDATE `crypto_payments` SET `confirmed`=1 WHERE `hash_id` = :hash_id and `transactionid` = :transactionid");
            $update->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
            $update->bindValue(':transactionid', $transaction['transactionid'], PDO::PARAM_STR);
            $update->execute();
            logging("transactionid {$transaction['transactionid']} updated to confirmed for hash_id {$hash_id}.");
        }
        else
        {
            $update = $db2->prepare("INSERT INTO `cryptocopyright`.`crypto_payments` (`payment_id`, `hash_id`, `transactionid`, `timestamp`, `btc`, `confirmed`) VALUES (NULL, :hash_id, :transactionid, :timestamp, :btc, :confirmed)");
            $update->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
            $update->bindValue(':transactionid', $transaction['transactionid'], PDO::PARAM_STR);
            $update->bindValue(':timestamp', $transaction['timestamp'], PDO::PARAM_INT);
            $update->bindValue(':btc', strval($transaction['btc']), PDO::PARAM_STR);
            $update->bindValue(':confirmed', $transaction['confirmed'], PDO::PARAM_INT);
            $update->execute();
            $not = $transaction['confirmed'] == 1?"":"not ";
            logging("transactionid {$transaction['transactionid']} added hash_id {$hash_id}, initially {$not}confirmed.");
        }
        $transactions[] = $transaction;
    }
    echo count($transactions, 0)." transactions processed...\n";
}
function bitcoinTransactionsCron()
{
    global $db2;
    while (1)
    {
        $query_hashs = $db2->prepare("SELECT `hash_id`, `payment_address` FROM `crypto_hashs` WHERE `done` = 0");
        if ($query_hashs === null)
        {
            logging("MySQL error, pausing thread for 15 seconds.", "ERROR");
            sleep(15);
            continue;
        }
        $query_hashs->execute();
        if ($query_hashs->rowCount() > 0)
        {
            while ($result = $query_hashs->fetch(PDO::FETCH_OBJ))
            {
                $hash_id = $result->hash_id;
                $payment_address = $result->payment_address;
                $gettxs = $db2->prepare("SELECT `transactionid`, `confirmed` FROM crypto_payments WHERE hash_id = :hash_id");
                $gettxs->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
                $gettxs->execute();
                $confirmed = array();
                $unconfirmed = array();
                if ($gettxs->rowCount() > 0)
                {
                    while ($result = $gettxs->fetch(PDO::FETCH_OBJ))
                    {
                        if ($result->confirmed) $confirmed[] = $result->transactionid;
                        else  $unconfirmed[] = $result->transactionid;
                    }
                }
                processTransactionsFromWallet($hash_id, $payment_address, $confirmed, $unconfirmed);
                sleep(15);
            }
        }
        logging('TransactionDaemon: There is nothing to work on. Pausing for 15 seconds.', 'INFO');
        sleep(15);
    }
}
function generateBitcoinAddress($hash_id)
{
    global $bitcoindrpc;
    $account = "";
    $generatedBitcoinAddress = "";
    $account = "payment_address_{$hash_id}";
    try
    {
        $generatedBitcoinAddress = @$bitcoindrpc->getnewaddress($account);
    }
    catch (exception $e)
    {
        logging($e, 'ERROR');
        $generatedBitcoinAddress = "";
    }

    if (strlen($generatedBitcoinAddress) != 34)
    {
        print ("Attempt to generate a BTC address for hash_id {$hash_id} has failed.\n");
        return false;
    }
    print ("BTC address: {$generatedBitcoinAddress} has been generated for hash_id {$hash_id}.\n");
    return $generatedBitcoinAddress;
}
function processQueue()
{
    global $db, $service_price;
    try
    {
        // the following SQL query will return all non-completed hash_id and last state. DONE/CANCELLED will be ignored !
        $queryevents = $db->prepare("SELECT e1.new_state, e1.hash_id FROM crypto_events e1 INNER JOIN (SELECT max(e.event_id) LastEvent, e.new_state, e.hash_id FROM crypto_events e WHERE e.hash_id IN (SELECT hash_id FROM crypto_hashs WHERE done = 0) GROUP BY e.hash_id) e2 ON e1.hash_id = e2.hash_id AND e1.event_id = e2.LastEvent order by e1.hash_id asc");
        if ($queryevents === null) return;
        $queryevents->execute();
        if ($queryevents->rowCount() === 0) logging('There is nothing to work on.', 'INFO');
        while ($result = $queryevents->fetch(PDO::FETCH_OBJ))
        {
            if ($result->new_state == null) continue;
            $hash_id = $result->hash_id;
            logging("Check hash_id: {$hash_id}, current state {$result->new_state}...", "INFO");
            switch ($result->new_state)
            {
                case "cryptoproof_submission_opened":
                    $getHash = $db->prepare("SELECT `data_digest`,`payment_address` FROM `crypto_hashs` WHERE `hash_id` = :hash_id");
                    $getHash->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
                    $getHash->execute();
                    $result = $getHash->fetch(PDO::FETCH_OBJ);

                    if (!ctype_xdigit($result->data_digest) || strlen($result->data_digest) != 56)
                    {
                        createEventByHashId($hash_id, 'cryptoproof_submission_opened', 'cryptoproof_submission_cancelled', "The hash_id {$hash_id} don't contains a valid SHA-3/224 digest.");
                        if (!setDoneToHashId($hash_id, -1)) logging("!setDoneToHashId($hash_id, -1)", "ERROR");
                        break;
                    }
                    if (strlen($result->payment_address) != 34)
                    {
                        $payment_address = generateBitcoinAddress($hash_id);
                        if (!$payment_address) break;
                        createEventByHashId($hash_id, 'cryptoproof_submission_opened', 'payment_pending', "Create bitcoin address: {$payment_address}.");
                        $update = $db->prepare("UPDATE `crypto_hashs` SET `payment_address`=:payment_address WHERE `hash_id` = :hash_id");
                        $update->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
                        $update->bindValue(':payment_address', $payment_address, PDO::PARAM_STR);
                        $update->execute();
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
                    $getHashSubmissionTimestamp = $db->prepare("SELECT `timestamp` FROM `crypto_hashs` WHERE `hash_id` = :hash_id");
                    $getHashSubmissionTimestamp->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
                    $getHashSubmissionTimestamp->execute();
                    $result = $getHashSubmissionTimestamp->fetch(PDO::FETCH_OBJ);
                    if ($result->timestamp < time() - 21600)
                    {
                        createEventByHashId($hash_id, 'payment_pending', 'cryptoproof_submission_cancelled', '$result->timestamp < time()-21600');
                        if (!setDoneToHashId($hash_id, -1)) logging("!setDoneToHashId($hash_id, -1)", "ERROR");
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
                            createEventByHashId($hash_id, 'payment_pending_confirmation', cryptoproof_submission_cancelled, '$payment_array[2] < time()-604800');
                            if (!setDoneToHashId($hash_id, -1)) logging("!setDoneToHashId($hash_id, -1)", "ERROR");
                        }
                    }
                    break;
                case "payment_approved":
                    $getHashs = $db->prepare("SELECT `data_digest`,`data_digest2`,`transactionid`,`transactionid2` FROM `crypto_hashs` WHERE `hash_id` = :hash_id");
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
                        if ((ctype_xdigit($tx2) && strlen($tx2) == 64) && (!ctype_xdigit($result->transactionid2) || strlen($result->transactionid2) != 64))
                        {
                            $update = $db->prepare("UPDATE `crypto_hashs` SET `transactionid2` = :transactionid WHERE `hash_id`=:hash_id");
                            $update->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
                            $update->bindValue(':transactionid', $result->transactionid2, PDO::PARAM_STR);
                            $update->execute();
                            break; // submit second hash before the first one for improved efficacity (a hash naming the owner existed before the actual hash been submitted)
                        }
                    }
                    if (ctype_xdigit($result->transactionid) && strlen($result->transactionid) == 64)
                    {
                        createEventByHashId($hash_id, 'payment_approved', 'cryptoproof_sent', '');
                        break;
                    }
                    $tx = createOpReturnByHash($hash_id, $result->data_digest);
                    if (ctype_xdigit($tx) && strlen($tx) == 64)
                    {
                        $update = $db->prepare("UPDATE `crypto_hashs` SET `transactionid` = :transactionid WHERE `hash_id`=:hash_id");
                        $update->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
                        $update->bindValue(':transactionid', $tx, PDO::PARAM_STR);
                        $update->execute();
                    }
                    break;
                case "cryptoproof_sent":
                    $getOpReturnTxs = $db->prepare("SELECT `data_digest`,`data_digest2`,`transactionid`,`transactionid2` FROM `crypto_hashs` WHERE `hash_id` = :hash_id");
                    $getOpReturnTxs->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
                    $getOpReturnTxs->execute();
                    $result = $getOpReturnTxs->fetch(PDO::FETCH_OBJ);
                    if (!ctype_xdigit($result->data_digest) || strlen($result->data_digest) != 56 || !ctype_xdigit($result->transactionid) || strlen($result->transactionid) != 64)
                    {
                        logging("The hash_id {$hash_id} don't contains a valid data_digest/transactionid", 'ERROR');
                        break;
                    }
                    if (ctype_xdigit($result->data_digest2) && strlen($result->data_digest2) == 56 && ctype_xdigit($result->transactionid2) && strlen($result->transactionid2) == 64)
                    {
                        if (getTransactionConfirmations($hash_id, $result->transactionid) > 0 && getTransactionConfirmations($hash_id, $result->transactionid2) > 0)
                        {
                            createEventByHashId($hash_id, 'cryptoproof_sent', 'cryptoproof_guaranteed', '');
                            if (!setDoneToHashId($hash_id, 1)) logging("!setDoneToHashId($hash_id, 1)", "ERROR");
                        }
                        break;
                    }
                    if (getTransactionConfirmations($hash_id, $result->transactionid) > 0)
                    {
                        createEventByHashId($hash_id, 'cryptoproof_sent', 'cryptoproof_guaranteed', '');
                        if (!setDoneToHashId($hash_id, 1)) logging("!setDoneToHashId($hash_id, 1)", "ERROR");
                    }
                    break;
            }
            sleep(1);
        }
    }
    catch (exception $e)
    {
        echo 'Exception reçue : ', $e->getMessage(), "\n";
    }
    return true;
}
function setDoneToHashId($hash_id, $done)
{
    global $db, $service_price;
    $update = $db->prepare("UPDATE `crypto_hashs` SET `done` = :done WHERE `hash_id`=:hash_id");
    $update->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
    $update->bindValue(':done', $done, PDO::PARAM_INT);
    $update->execute();
    $affected_rows = $update->rowCount();
    if ($affected_rows === 0) return false;
    switch ($done)
    {
        case - 1:
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
    global $bitcoindrpc;
    try
    {
        $txs = @$bitcoindrpc->gettransaction($txid);
        if ($txs["confirmations"] > 0) return 1;
    }
    catch (exception $e)
    {
        logging($e, 'ERROR');
    }
    return 0;
}
function createOpReturnByHash($hash_id, $hash)
{
    $command = escapeshellcmd('python3 /home/crypto-copyright/corporate/www/timestamp-op-ret.py -s '.$hash);
    $output = array();
    $txid = '';
    exec($command, $output);
    foreach ($output as $line)
    {
        $txid .= $line;
    }
    if (ctype_xdigit($txid) && strlen($txid) == 64) return $txid;
    return false;
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
    if ($details != "") $details = " Details: ".$details;
    if ($affected_rows === 0) logging("Update event status for hash_id: {$hash_id}, set the new status to {$new_state}, from {$old_state}.{$details}", "ERROR");
    else  logging("Update event status for hash_id: {$hash_id}, set the new status to {$new_state}, from {$old_state}.{$details}", "INFO");
}
function getPaymentsForHash($hash_id)
{
    global $db, $service_price;
    $confirmed_balance = 0;
    $unconfirmed_balance = 0;
    $last_timestamp = 0;
    $paymentsquery = $db->prepare("SELECT `btc`, `confirmed`, `timestamp` FROM `crypto_payments` WHERE `hash_id`=:hash_id ORDER BY `timestamp`");
    $paymentsquery->bindValue(':hash_id', $hash_id, PDO::PARAM_INT);
    $paymentsquery->execute();
    if ($paymentsquery->rowCount() === 0) return array($confirmed_balance, $unconfirmed_balance);

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
    return array(
        $confirmed_balance,
        $unconfirmed_balance,
        $last_timestamp);
}
function queueOfEvents()
{
    while (1)
    {
        if (processQueue()) sleep(5);
    }
}
function logging($msg, $level = 'NORMAL')
{
    global $debuglevel;
    echo "{$level}: {$msg}\n";
    // ToDo: Write on file.
    // ToDo: Handle debuglevel bitmask.
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
        if ($queueOfEventsPid) bitcoinTransactionsCron();
        else  queueOfEvents();
}
print ("-----------------------------+=======================================================================+---------------------------\n");
print ("-----------------------------+    TaskQueueManagerDaemon by crypto-copyright.com, copyright 2014     +---------------------------\n");
print ("-----------------------------+  Query Bitcoind, receive payments, send OP_RETURN txs, update status  +---------------------------\n");
print ("-----------------------------+=======================================================================+---------------------------\n");
main();
?>
