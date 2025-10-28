-- phpMyAdmin SQL Dump
-- version 3.5.2.2
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: Sep 09, 2014 at 05:38 PM
-- Server version: 5.5.27
-- PHP Version: 5.4.7

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `dev_lms2`
--

-- --------------------------------------------------------

--
-- Table structure for table `mdl_customnavigation`
--

DROP TABLE IF EXISTS `mdl_customnavigation`;
CREATE TABLE IF NOT EXISTS `mdl_customnavigation` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) NOT NULL DEFAULT '0',
  `sort` int(11) NOT NULL,
  `type` char(20) DEFAULT NULL COMMENT 'values can be: link, container, module',
  `module` varchar(120) DEFAULT NULL,
  `label` char(120) DEFAULT NULL,
  `href` text,
  `target` varchar(10) DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `asignuserid` varchar(255) DEFAULT NULL,
  `roleid` varchar(255) DEFAULT NULL,
  `inst_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `index3` (`sort`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=31 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
