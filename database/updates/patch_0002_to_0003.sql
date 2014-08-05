-- PATCH: 0001 to 0002

ALTER TABLE `crypto_hashs` CHANGE `done` `done` INT(1) NOT NULL DEFAULT '0';
