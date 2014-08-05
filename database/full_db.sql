-- VERSION: 0001

-- phpMyAdmin SQL Dump
-- version 4.2.7
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Aug 05, 2014 at 05:43 PM
-- Server version: 5.5.37-0+wheezy1
-- PHP Version: 5.4.4-14+deb7u12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `cryptocopyright`
--

-- --------------------------------------------------------

--
-- Table structure for table `crypto_events`
--

CREATE TABLE IF NOT EXISTS `crypto_events` (
`event_id` bigint(20) unsigned NOT NULL,
  `hash_id` bigint(20) unsigned NOT NULL,
  `timestamp` int(11) NOT NULL,
  `old_state` enum('null','cryptoproof_submission_opened','payment_pending','cryptoproof_submission_cancelled','payment_pending_confirmation','payment_approved','cryptoproof_sent','cryptoproof_pending_confirmation','cryptoproof_guaranteed') NOT NULL DEFAULT 'null',
  `new_state` enum('null','cryptoproof_submission_opened','payment_pending','cryptoproof_submission_cancelled','payment_pending_confirmation','payment_approved','cryptoproof_sent','cryptoproof_pending_confirmation','cryptoproof_guaranteed') NOT NULL DEFAULT 'null',
  `details` varchar(255) DEFAULT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Dumping data for table `crypto_events`
--

INSERT INTO `crypto_events` (`event_id`, `hash_id`, `timestamp`, `old_state`, `new_state`, `details`) VALUES
(1, 1, 1407008700, 'null', 'cryptoproof_submission_opened', 'Manually registered.'),
(2, 1, 1407008986, 'cryptoproof_submission_opened', 'cryptoproof_guaranteed', 'Manually registered.');

-- --------------------------------------------------------

--
-- Table structure for table `crypto_hashs`
--

CREATE TABLE IF NOT EXISTS `crypto_hashs` (
`hash_id` bigint(20) unsigned NOT NULL,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `data_name` varchar(255) NOT NULL,
  `data_size` bigint(16) unsigned NOT NULL COMMENT 'The maximum data size is 9 peta bytes.',
  `data_description` text,
  `data_digest` char(56) NOT NULL,
  `payment_address` char(64) NOT NULL COMMENT 'The generated bitcoin address to pay to.',
  `transactionid` char(64) NOT NULL COMMENT 'The transaction that include the hash in the blockchain.',
  `transactionid2` varchar(64) NOT NULL COMMENT 'The transaction that double-authentificate the owner of the hash in the blockchain.'
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Dumping data for table `crypto_hashs`
--

INSERT INTO `crypto_hashs` (`hash_id`, `owner_id`, `data_name`, `data_size`, `data_description`, `data_digest`, `payment_address`, `transactionid`, `transactionid2`) VALUES
(1, NULL, 'timestamp-op-ret.py', 4653, 'timestamp-op-ret.py initially commited to github: 512e41c6da0d31a06a150a638e099a48f6bcade1', '4b4dada08cb7280f092e22ac04a7b509cdf05922ae23b7861f561511', '512e41c6da0d31a06a150a638e099a48f6bcade1', '7550cf37fb758cb58ec282d222783bb7fd23142387ec4c207d92c977daaaf5eb', '');

-- --------------------------------------------------------

--
-- Table structure for table `crypto_payments`
--

CREATE TABLE IF NOT EXISTS `crypto_payments` (
`payment_id` bigint(20) unsigned NOT NULL,
  `hash_id` bigint(20) unsigned NOT NULL,
  `transactionid` char(56) NOT NULL,
  `btc` decimal(11,8) NOT NULL,
  `confirmed` int(1) NOT NULL
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `crypto_events`
--
ALTER TABLE `crypto_events`
 ADD PRIMARY KEY (`event_id`), ADD KEY `hash_id` (`hash_id`), ADD KEY `hash_id_2` (`hash_id`), ADD KEY `hash_id_3` (`hash_id`);

--
-- Indexes for table `crypto_hashs`
--
ALTER TABLE `crypto_hashs`
 ADD PRIMARY KEY (`hash_id`), ADD KEY `owner_id` (`owner_id`);

--
-- Indexes for table `crypto_payments`
--
ALTER TABLE `crypto_payments`
 ADD PRIMARY KEY (`payment_id`), ADD KEY `hash_id` (`hash_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `crypto_events`
--
ALTER TABLE `crypto_events`
MODIFY `event_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=3;
--
-- AUTO_INCREMENT for table `crypto_hashs`
--
ALTER TABLE `crypto_hashs`
MODIFY `hash_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT for table `crypto_payments`
--
ALTER TABLE `crypto_payments`
MODIFY `payment_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- Constraints for dumped tables
--

--
-- Constraints for table `crypto_events`
--
ALTER TABLE `crypto_events`
ADD CONSTRAINT `crypto_events_ibfk_1` FOREIGN KEY (`hash_id`) REFERENCES `crypto_hashs` (`hash_id`);

--
-- Constraints for table `crypto_payments`
--
ALTER TABLE `crypto_payments`
ADD CONSTRAINT `crypto_payments_ibfk_1` FOREIGN KEY (`hash_id`) REFERENCES `crypto_hashs` (`hash_id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
