-- PATCH: 0003 to 0004

UPDATE `crypto_hashs` SET `done` = '1' WHERE `crypto_hashs`.`hash_id` = 1;
