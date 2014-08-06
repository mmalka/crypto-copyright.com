-- PATCH: 0004 to 0005

ALTER TABLE `crypto_hashs` ADD `timestamp` INT(11) NOT NULL AFTER `owner_id`;
ALTER TABLE `crypto_hashs` ADD `data_digest2` CHAR(56) NOT NULL AFTER `data_digest`;
UPDATE `cryptocopyright`.`crypto_hashs` SET `payment_address` = '1MDo23U4X1VRxMRC626xNW2Rciuxx4cpXB' WHERE `crypto_hashs`.`hash_id` = 1;
