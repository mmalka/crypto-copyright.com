-- PATCH: 0002 to 0003

ALTER TABLE `crypto_hashs` CHANGE `done` `done` INT(1) NOT NULL DEFAULT '0';
