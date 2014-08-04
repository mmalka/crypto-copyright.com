<!--- Version: 0 -->
# models #
## tables ##
* user
* hash
* payment

### Table: user ###
* see CMS user db for more informations *

### Table: hash ###
* id
* ownerid
* filename
* filesize
* description
* hash
* lastevent (eventid)
* receiving bitcoin address
* transactionid
* transactionid2

### Table: events ###
* eventid
* hashid
* timestamp
* statusold
* statusnew
* details

### Table: payment ###
* paymentid
* hashid
* transaction id of payment (see below)
* btc from transaction id (btc address is taken from hash table)
* confirmed state :: true/false

## relations ##
* One User to Many Hashs :: user.id to hash.ownerid
* One Hash to Many Payments :: hash.id to payment.hashid
* One User to Many Payments :: user.id to hash.userid to payment.hashid

# queries #
1. Find hash with user (to see if the same hash was already there). Also for unpaid transactions
2. Create/Read/Update/Delete for users
3. INSERT for transaction. Note: transactions are immutable: they NEVER get changed except for the event data.
4. Verify a payment: select sum(received_btc) from txs_payments where received_address ='XXXX' and confirmed='true'
5. Prune unpaid transaction:
    something like:
    UPDATE transaction SET cancelled=TIME() WHERE TIME()-paid > x hours (6 hours ?)