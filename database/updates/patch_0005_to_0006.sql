-- PATCH: 0005 to 0006

ALTER TABLE `crypto_payments` ADD `timestamp` INT(11) NOT NULL AFTER `transactionid`;
