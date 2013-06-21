-- phpMyAdmin SQL Dump
-- version 3.4.7
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Jun 21, 2013 at 12:49 PM
-- Server version: 5.5.29
-- PHP Version: 5.3.20-1~dotdeb.0

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Database: `evemail`
--

-- --------------------------------------------------------

--
-- Table structure for table `api_keys`
--

CREATE TABLE IF NOT EXISTS `api_keys` (
  `key_id` int(11) NOT NULL,
  `v_code` varchar(255) CHARACTER SET latin1 NOT NULL,
  `forward_mail` varchar(100) CHARACTER SET latin1 NOT NULL,
  `username` varchar(100) COLLATE utf8_bin NOT NULL,
  `premium` date NOT NULL,
  `filters` text COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`key_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `corp_ally_names`
--

CREATE TABLE IF NOT EXISTS `corp_ally_names` (
  `id` int(11) NOT NULL,
  `name` varchar(255) COLLATE utf8_bin NOT NULL,
  `ticker` varchar(10) COLLATE utf8_bin NOT NULL,
  `typ` varchar(1) COLLATE utf8_bin NOT NULL DEFAULT 'c',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `list_names`
--

CREATE TABLE IF NOT EXISTS `list_names` (
  `list_id` int(11) NOT NULL,
  `list_name` varchar(100) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`list_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `processed_mails`
--

CREATE TABLE IF NOT EXISTS `processed_mails` (
  `key_id` int(11) NOT NULL,
  `message_id` int(11) NOT NULL,
  UNIQUE KEY `key_id` (`key_id`,`message_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

-- --------------------------------------------------------

--
-- Table structure for table `user_names`
--

CREATE TABLE IF NOT EXISTS `user_names` (
  `character_id` int(11) NOT NULL,
  `character_name` varchar(100) COLLATE utf8_bin NOT NULL,
  PRIMARY KEY (`character_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
