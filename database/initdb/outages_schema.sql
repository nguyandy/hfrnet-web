-- MySQL dump 10.13  Distrib 8.0.43, for Linux (x86_64)
--
-- Host: localhost    Database: outages
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
-- Current Database: `outages`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `outages` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `outages`;

--
-- Table structure for table `data_availability`
--

DROP TABLE IF EXISTS `data_availability`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `data_availability` (
  `data_availability_id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `text` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`data_availability_id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `outage_records`
--

DROP TABLE IF EXISTS `outage_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `outage_records` (
  `outage_records_id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `date_entered` decimal(17,5) NOT NULL,
  `notes` varchar(2000) DEFAULT NULL,
  `users_id` mediumint unsigned NOT NULL,
  `time_to_repair_id` mediumint unsigned DEFAULT NULL,
  `site_id` mediumint unsigned NOT NULL,
  `data_availability_id` mediumint unsigned DEFAULT NULL,
  `is_resolved` tinyint(1) DEFAULT '0',
  `date_resolved` decimal(17,5) DEFAULT NULL,
  `start_date` decimal(17,5) DEFAULT NULL,
  PRIMARY KEY (`outage_records_id`),
  KEY `fk_outage_records_1_idx` (`users_id`),
  KEY `fk_outage_records_2_idx` (`data_availability_id`),
  KEY `fk_outage_records_3_idx` (`time_to_repair_id`),
  CONSTRAINT `fk_outage_records_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`),
  CONSTRAINT `fk_outage_records_2` FOREIGN KEY (`data_availability_id`) REFERENCES `data_availability` (`data_availability_id`),
  CONSTRAINT `fk_outage_records_3` FOREIGN KEY (`time_to_repair_id`) REFERENCES `time_to_repair` (`time_to_repair_id`)
) ENGINE=InnoDB AUTO_INCREMENT=452 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `outages`
--

DROP TABLE IF EXISTS `outages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `outages` (
  `outages_id` mediumint unsigned NOT NULL,
  `outages` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`outages_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `records_multiple_outages`
--

DROP TABLE IF EXISTS `records_multiple_outages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `records_multiple_outages` (
  `outage_records_id` mediumint unsigned NOT NULL,
  `outages_id` mediumint unsigned NOT NULL,
  KEY `fk_records_multiple_outages_1_idx` (`outage_records_id`),
  KEY `fk_records_multiple_outages_2_idx` (`outages_id`),
  CONSTRAINT `fk_records_multiple_outages_1` FOREIGN KEY (`outage_records_id`) REFERENCES `outage_records` (`outage_records_id`),
  CONSTRAINT `fk_records_multiple_outages_2` FOREIGN KEY (`outages_id`) REFERENCES `outages` (`outages_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `records_multiple_tags`
--

DROP TABLE IF EXISTS `records_multiple_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `records_multiple_tags` (
  `outage_records_id` mediumint unsigned NOT NULL,
  `tags_id` mediumint unsigned NOT NULL,
  KEY `fk_records_multiple_tags_1_idx` (`outage_records_id`),
  KEY `fk_records_multiple_tags_2_idx` (`tags_id`),
  CONSTRAINT `fk_records_multiple_tags_1` FOREIGN KEY (`outage_records_id`) REFERENCES `outage_records` (`outage_records_id`),
  CONSTRAINT `fk_records_multiple_tags_2` FOREIGN KEY (`tags_id`) REFERENCES `tags` (`tags_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tags`
--

DROP TABLE IF EXISTS `tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tags` (
  `tags_id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `text` varchar(100) NOT NULL,
  `abbreviation` varchar(5) NOT NULL,
  PRIMARY KEY (`tags_id`,`abbreviation`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `time_to_repair`
--

DROP TABLE IF EXISTS `time_to_repair`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `time_to_repair` (
  `time_to_repair_id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `text` varchar(45) NOT NULL,
  PRIMARY KEY (`time_to_repair_id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `tokens`
--

DROP TABLE IF EXISTS `tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tokens` (
  `users_id` mediumint unsigned NOT NULL,
  `token` char(64) NOT NULL,
  `date_expires` decimal(17,8) NOT NULL,
  PRIMARY KEY (`users_id`),
  CONSTRAINT `fk_tokens_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COMMENT='Remove after 15 minutes or password change';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `users_id` mediumint unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `password` char(64) NOT NULL,
  `salt` char(32) NOT NULL,
  `email` varchar(100) NOT NULL,
  PRIMARY KEY (`users_id`)
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users_networks`
--

DROP TABLE IF EXISTS `users_networks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users_networks` (
  `users_id` mediumint unsigned NOT NULL,
  `network_id` mediumint unsigned NOT NULL,
  KEY `fk_users_networks_1_idx` (`users_id`),
  CONSTRAINT `fk_users_networks_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`users_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-07 23:33:14
