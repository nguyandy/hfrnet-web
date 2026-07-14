-- MySQL dump 10.13  Distrib 8.0.43, for Linux (x86_64)
--
-- Host: localhost    Database: rtvproc
-- ------------------------------------------------------
-- Server version	8.0.43

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Current Database: `rtvproc`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `rtvproc` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `rtvproc`;

--
-- Table structure for table `site`
--

DROP TABLE IF EXISTS `site`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `network` varchar(45) NOT NULL COMMENT 'Network abbreviation',
  `name` varchar(45) NOT NULL COMMENT 'site name abbreviation',
  PRIMARY KEY (`id`),
  UNIQUE KEY `site_idx` (`network`,`name`)
) ENGINE=InnoDB AUTO_INCREMENT=304 DEFAULT CHARSET=utf8mb3 COMMENT='Site definitions for RTV processing';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `site_config`
--

DROP TABLE IF EXISTS `site_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `site_config` (
  `id` smallint unsigned NOT NULL AUTO_INCREMENT,
  `site_id` smallint unsigned NOT NULL,
  `domain_id` tinyint unsigned NOT NULL,
  `resolution_id` tinyint unsigned NOT NULL,
  `start_time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Processing dates and times greater than or equal to this value AND\nless than the end_time should use this configuration.',
  `end_time` timestamp NULL DEFAULT NULL COMMENT 'Processing dates and times less than this value AND greater than\nor equal to the start_time should use this configuration.',
  `beampattern` enum('ideal','measured') NOT NULL COMMENT 'Beam pattern for RTV processing',
  `use_radial_minute` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'Radial data timestamp minute to use in RTV processing. Some sites\nreport multiple times per hour or otherwise don''t report at the top of \nthe hour.  This value defines the minute of the radial data timestamp\nto be used.',
  PRIMARY KEY (`id`),
  KEY `siteconfig_res_fk_idx` (`resolution_id`),
  KEY `siteconfig_site_fk_idx` (`site_id`),
  KEY `siteconfig_dom_fk_idx` (`domain_id`),
  CONSTRAINT `siteconfig_dom_fk` FOREIGN KEY (`domain_id`) REFERENCES `domain` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `siteconfig_res_fk` FOREIGN KEY (`resolution_id`) REFERENCES `resolution` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `siteconfig_site_fk` FOREIGN KEY (`site_id`) REFERENCES `site` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31349 DEFAULT CHARSET=utf8mb3 COMMENT='Time-dependent site configurations for RTV processing';
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-07 23:33:19
