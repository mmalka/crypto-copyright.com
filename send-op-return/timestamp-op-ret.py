#!/usr/bin/python3

# Distributed under the MIT/X11 software license, see the accompanying
# file COPYING or http://www.opensource.org/licenses/mit-license.php.

"""Example of timestamping a file via OP_RETURN"""

import hashlib
import bitcoin.rpc
import sys
import string

from bitcoin.core import *
from bitcoin.core.script import *

def is_hex(s):
     hex_digits = set(string.hexdigits)
     # if s is long, then it is faster to check against a set
     return all(c in hex_digits for c in s)
proxy = bitcoin.rpc.Proxy()
silent_mode = 0
stdrin = sys.argv[1:]
if sys.argv[1] == "-s" or sys.argv[1] == "--silent-mode":
    silent_mode = 1
    stdrin = sys.argv[2:]
if silent_mode == 0: print("-----------------------------+=======================================================================+---------------------------")
if silent_mode == 0: print("-----------------------------+ OP_RETURN transaction builder by crypto-copyright.com, copyright 2014 +---------------------------")
if silent_mode == 0: print("-----------------------------+  Builds raw transaction, signs it, broadcasts it to bitcoin network.  +---------------------------")
if silent_mode == 0: print("-----------------------------+             usage: script.py [\"valid-sha3/224-hash1\",...]             +---------------------------")
if silent_mode == 0: print("-----------------------------+=======================================================================+---------------------------")
assert len(sys.argv) > 1

FEE_TO_PAY_PER_BYTE = 0.0001; # prod: 0.001
byte_marker = "CryptoTests-"; # prod: "CryptoProof-";
digests = []
for myHash in stdrin:
    try:
        if len(byte_marker) > 12:
            print("The 'byte_marker' variable is not valid, should contains at most 12 characters, currently containing " + str(len(byte_marker)))
            exit(0)
        if not is_hex(myHash):
            print("The input is not a valid hex hash.")
            print("Size: " + str(len(myHash)) + ", input: " + str(myHash))
            exit(0)
        if len(myHash) < 56:
            print("The input hash is not a valid SHA-3/224 hash.")
            print("Size: " + str(len(myHash)) + ", input: " + str(myHash))
            exit(0)
        if len(myHash) > 56:
            print("The input hash is not a valid SHA-3/224 hash.")
            print("Size: " + str(len(myHash)) + ", input: " + str(myHash))
            exit(0)
        if silent_mode == 0: print("The input hash is a valid SHA-3/224 hash | Size: " + str(len(myHash)) + " | input: " + str(myHash))
        output_hash = (binascii.hexlify(byte_marker.encode('ascii')) + myHash.encode('ascii'));
# We have a valid input, let's append our marker to it.
        digests.append(output_hash)
    except IOError as exp:
        print(exp,file=sys.stderr)
        continue
if silent_mode == 0: print("---------------------------------------------------------------------------------------------------------------------------------")
if silent_mode == 0: print("Processing the inputs... " + str(len(digests)) + " valids SHA-3/224 hashs found.")
if silent_mode == 0: print("---------------------------------------------------------------------------------------------------------------------------------")
i = 1;
for digest in digests:
    if silent_mode == 0: print("Processing #" + str(i) + " OP_RETURN | Size: " + str(len(digest)) + " | Value: " + str(digest))
    if silent_mode == 0: print("---------------------------------------------------------------------------------------------------------------------------------")
    i += 1
    txouts = []

    unspent = sorted(proxy.listunspent(0), key=lambda x: hash(x['amount']))

    txins = [CTxIn(unspent[-1]['outpoint'])]
    value_in = unspent[-1]['amount']

    change_addr = proxy.getnewaddress()
    change_pubkey = proxy.validateaddress(change_addr)['pubkey']
    digest_outs = CMutableTxOut(0, CScript([script.OP_RETURN, x(digest.decode('UTF-8'))]))

    
    change_out = [CMutableTxOut(MAX_MONEY, CScript([change_pubkey, OP_CHECKSIG]))]
    txouts = [digest_outs] + change_out

    tx = CMutableTransaction(txins, txouts)

    FEE_PER_BYTE = 0.00025*COIN/1000
    while True:
        tx.vout[1].nValue = int(value_in - max(len(tx.serialize())*FEE_PER_BYTE, FEE_TO_PAY_PER_BYTE*COIN))

        r = proxy.signrawtransaction(tx)
        assert r['complete']
        tx = r['tx']

        if value_in - tx.vout[1].nValue >= len(tx.serialize())*FEE_PER_BYTE:
            content_txraw = b2x(tx.serialize());
            size_txraw = str(len(tx.serialize())) + " bytes";
            generated_tx = b2lx(proxy.sendrawtransaction(tx));
            if silent_mode == 0: print("Generated TxRaw | Size: " + str(size_txraw) + " | Value: " + content_txraw)
            if silent_mode == 0: print("Transaction Broadcast -> Generated TxId | " + generated_tx)
            if silent_mode == 0: print("---------------------------------------------------------------------------------------------------------------------------------")
            if silent_mode == 1: print(generated_tx)
            break
