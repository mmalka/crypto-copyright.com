crypto-copyright.com - Todo and ideas
====================

All informations present on this page are subject to change anytime, once they will be confirmed by both partner, they will be added to README.md.

- Price of the service : 0.005 btc => 20% as transactions fee to the btc network (0.001 instead of 0.0001)
- A service that respect miners, we gives 10x more txfee to the BTC network than the minimum recommanded.
- A service that guarranty the least eventual collisions using SHA-3/224 hashing algorytm that is still collision-proof as of 1st August 2014.
- A service that allow you to retrieve your past copyrights/patents proof of existence anytime on your personnal account.
- A service that allow you to print a signed certificat to show in case you are going to a court.
- A service that needs only 1 confirmation before approving your copyright/patent proof of existence case.
- A service that do the checksum hashing on the client side to protect your documents.
- A service that also provide a on-demand double-authentification registration. (Will submit another proof of existence for a raw Ascii text identifying the owner, document name, document content to easily rely the owner to a file)

Todo to be able to fullfil those required features:
- Install a bitecoind local wallet.
- Make a cronjob script that will check the new transactions ids.
  - Auto-refund transactions that are not exact amount. Allow a +/- 0.0006 difference. If it's higher than 0.0001 btc fee (minus the fee).
  - If transaction accepted/confirmed:
     - Will immediatly create the proof of existence.
	   - Will create a transaction using an OP_RETURN meta-data.
	     - The identifier for our transactions will be the following 12 bytes Ascii "CryptoProof-", translated "43 72 79 70 74 6f 50 72 6f 6f 66 2d" in hexadecimal.
	     - The hash of our transactions will be using SHA-3/224 algorithm. It will use 224 bits/28 bytes.
	     - The identifier + the hash will be a using a total of 40 bytes lengh, using 100% of the available space for this meta-data field.
	 - Will create an entry in our database about this proof of existence and all the informations related to it for PDF generation.
	 - Will send : ((TOTAL-0.0001*2)/2) BTC to Partner#1.
	 - Will send : ((TOTAL-0.0001*2)/2) BTC to Partner#2.
  - Auto-refund transactions not linked to any ongoing task if it's higher than 0.0001 btc fee (minus the fee).
- Make a cronjob script that will prune unpaid registration.
  - 6 hours to receive the payment.
- Create a graphical chart for the logo/website.
- Code the backend with user connexion supporting google dual authentification and facebook connect.
  - Should be able to connect using Facebook connect.
  - Should be able to secure though google double authentification system.
  - Should be able to parse our previous patents/copyright deposit with personnal informations (description/email/etc)
- Code the frontend interface.
  - Should be able to search a copyright/patent by its hash and/or its id on crypto-copyright.com
  - Should be able to drag'n'drop a file to submit a copyright/patent registration.
    - If the hash already exist, directly open the hash page.
	- If the hash don't exist, request the payment.
	  - Allow a double-authentification secure (money*2) to also timestamp a raw text link to the owner of the document.
- About the hash page on public view:
  - Allow to download a "public domain" pdf certificate hidding the description/name of the file and personnals datas.
    - It will attest that our company registred a document (identified by file type or "text document", size of the file/lenght of the text document, date)