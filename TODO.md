crypto-copyright.com - To-do and ideas
====================

All information present on this page are subject to change anytime, once they will be confirmed by both partner, they will be added to README.md.

- Price of the service: 0.005 BTC => 20% as transactions fee to the BTC network (0.001 instead of 0.0001)
- A service that respect miners, we gives 10x more txfee to the BTC network than the minimum recommended.
- A service that guarantee the least eventual collisions using SHA-3/224 hashing algorithm that is still collision-proof as of 1st August 2014.
- A service that allow you to retrieve your past copyrights/patents proof of existence anytime on your personal account.
- A service that allow you to print a signed certificate to show in case you are going to a court.
- A service that needs only 1 confirmation before approving your copyright/patent proof of existence case.
 - It will then process request as soon as possible regarding our current cash-fund, could wait up to 6 transactions to process in worst cases, but request is considered paid at 1 confirmation.
- A service that do the checksum hashing on the client side to protect your documents.
- A service that also provide an on-demand double-authentication registration. (Will submit another proof of existence for a raw ASCII text identifying the owner, document name, document content to easily prove the owner of a file)

To-do to be able to fulfil those required features:
- Install a bitcoind local wallet.
- Make a cronjob script that will check the new transactions ids.
  - Auto-refund transactions that are not exact amount. Allow payments with 0.0006 btc difference and authorize any higher price. If it's higher than 0.0001 btc fee (minus the fee).
  - If transaction accepted/confirmed:
     - Will immediately create the proof of existence.
	   - Will wait only one confirm if our cash-fund is funded enough to fullfil the transaction.
	     - In case our cash-fund is not enough, will retry every new block to process it as we may receive others transactions. The maximum amount of block to wait will be 6 confirmations anyway.
	   - Will create a transaction using an OP_RETURN meta-data.
	     - The identifier for our transactions will be the following 12 bytes ASCII "CryptoProof-", translated "43 72 79 70 74 6f 50 72 6f 6f 66 2d" in hexadecimal.
	     - The hash of our transactions will be using SHA-3/224 algorithm. It will use 224 bits/28 bytes.
	     - The identifier + the hash will be using a total of 40 bytes length, using 100% of the available space for this meta-data field.
	 - Will create an entry in our database about this proof of existence and all the information related to it for PDF generation.
	 - Will send: ((TOTAL-0.0001)/2) BTC to Partner#1 daily/weekly/monthly possible.
	 - Will send: ((TOTAL-0.0001)/2) BTC to Partner#2 in the same transaction as for Partner#1, daily/weekly/monthly possible..
  - Auto-refund transactions not linked to any ongoing task if it's higher than 0.0001 BTC fee (minus the fee).
- Make a cronjob script that will prune unpaid registration.
  - 6 hours to receive the payment.
- Create a graphical chart for the logo/website.
- Code the backend with user connection supporting google dual authentication and Facebook connect.
  - Should be able to connect using Facebook connect.
  - Should be able to secure though google double authentication system.
  - Should be able to parse our previous patents/copyright deposit with personal information (description/email/etc.)
- Code the frontend interface.
  - Should be able to search a copyright/patent by its hash and/or its id on crypto-copyright.com
  - Should be able to drag'n'drop a file to submit a copyright/patent registration.
    - If the hash already exist, directly open the hash page.
	- If the hash don't exist, request the payment.
	  - Allow a double-authentication secure (money*2) to also timestamp a raw text link to the owner of the document.
- About the hash page on public view:
  - Allow to download a "public domain" pdf certificate hiding the description/name of the file and personal data.
    - It will attest that our company registered a document (identified by file type or "text document", size of the file/length of the text document, date)

- Tool that users can download to verify the SHA-3/224 hash of their file.
