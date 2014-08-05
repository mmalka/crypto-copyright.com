-- PATCH: 0001 to 0002

ALTER TABLE `crypto_hashs` ADD `done` INT(1) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `crypto_payments` CHANGE `confirmed` `confirmed` INT(1) UNSIGNED NOT NULL DEFAULT '0';