-- PATCH: 0006 to 0007

ALTER TABLE `crypto_hashs` ADD `payment_btc` DECIMAL(11,8) NOT NULL AFTER `payment_address`;
ALTER TABLE `crypto_payments` CHANGE `transactionid` `transactionid` CHAR(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL;