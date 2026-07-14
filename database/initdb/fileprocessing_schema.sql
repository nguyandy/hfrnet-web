-- MySQL dump for fileprocessing database
-- Database: fileprocessing
-- Purpose: Track S3 file processing status for radial and wave files
-- ------------------------------------------------------

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
-- Current Database: `fileprocessing`
--

CREATE DATABASE /*!32312 IF NOT EXISTS*/ `fileprocessing` /*!40100 DEFAULT CHARACTER SET utf8mb4 */ /*!80016 DEFAULT ENCRYPTION='N' */;

USE `fileprocessing`;

--
-- Table structure for table `radial_file_processing`
--

DROP TABLE IF EXISTS `radial_file_processing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `radial_file_processing` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `s3_key` VARCHAR(512) NOT NULL COMMENT 'Full S3 path (e.g., radials/AMAG/2025-06/file.ruv)',
  `s3_bucket` VARCHAR(128) NOT NULL COMMENT 'S3 bucket name',
  `site_code` VARCHAR(8) NOT NULL COMMENT 'Site identifier extracted from path',
  `file_name` VARCHAR(255) NOT NULL COMMENT 'Base filename only',
  
  -- S3 metadata for change detection
  `s3_last_modified` DATETIME NOT NULL COMMENT 'Last modified timestamp from S3',
  `s3_etag` VARCHAR(128) COMMENT 'S3 ETag for detecting file changes',
  
  -- Processing status tracking
  `status` ENUM('pending', 'processing', 'success', 'failed', 'skipped') NOT NULL DEFAULT 'pending' COMMENT 'Current processing status',
  `processing_completed_at` DATETIME COMMENT 'When processing finished',
  
  -- Error tracking
  `error_message` TEXT COMMENT 'Error details if processing failed',
  `error_type` VARCHAR(128) COMMENT 'Exception type or error category',
  
  -- Indexes and constraints
  UNIQUE KEY `unique_file` (`s3_bucket`, `s3_key`, `s3_etag`),
  INDEX `idx_status` (`status`),
  INDEX `idx_site_code` (`site_code`),
  INDEX `idx_processing_completed` (`processing_completed_at`),
  INDEX `idx_s3_last_modified` (`s3_last_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tracks processing status of radial files from S3';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `wave_file_processing`
--

DROP TABLE IF EXISTS `wave_file_processing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wave_file_processing` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `s3_key` VARCHAR(512) NOT NULL COMMENT 'Full S3 path (e.g., waves/AMAG/2025-06/file.wls)',
  `s3_bucket` VARCHAR(128) NOT NULL COMMENT 'S3 bucket name',
  `site_code` VARCHAR(8) NOT NULL COMMENT 'Site identifier extracted from path',
  `file_name` VARCHAR(255) NOT NULL COMMENT 'Base filename only',
  
  -- S3 metadata for change detection
  `s3_last_modified` DATETIME NOT NULL COMMENT 'Last modified timestamp from S3',
  `s3_etag` VARCHAR(128) COMMENT 'S3 ETag for detecting file changes',
  
  -- Processing status tracking
  `status` ENUM('pending', 'processing', 'success', 'failed', 'skipped') NOT NULL DEFAULT 'pending' COMMENT 'Current processing status',
  `processing_completed_at` DATETIME COMMENT 'When processing finished',
  
  -- Error tracking
  `error_message` TEXT COMMENT 'Error details if processing failed',
  `error_type` VARCHAR(128) COMMENT 'Exception type or error category',
  
  -- Indexes and constraints
  UNIQUE KEY `unique_file` (`s3_bucket`, `s3_key`, `s3_etag`),
  INDEX `idx_status` (`status`),
  INDEX `idx_site_code` (`site_code`),
  INDEX `idx_processing_completed` (`processing_completed_at`),
  INDEX `idx_s3_last_modified` (`s3_last_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tracks processing status of wave files from S3';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `metric_file_processing`
--

DROP TABLE IF EXISTS `metric_file_processing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `metric_file_processing` (
  `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
  `s3_key` VARCHAR(512) NOT NULL COMMENT 'Full S3 path (e.g., radials/AMAG/2025-06/file.ruv)',
  `s3_bucket` VARCHAR(128) NOT NULL COMMENT 'S3 bucket name',
  `site_code` VARCHAR(8) NOT NULL COMMENT 'Site identifier extracted from path',
  `file_name` VARCHAR(255) NOT NULL COMMENT 'Base filename only',
  
  -- S3 metadata for change detection
  `s3_last_modified` DATETIME NOT NULL COMMENT 'Last modified timestamp from S3',
  `s3_etag` VARCHAR(128) COMMENT 'S3 ETag for detecting file changes',
  
  -- Processing status tracking
  `status` ENUM('pending', 'processing', 'success', 'failed', 'skipped') NOT NULL DEFAULT 'pending' COMMENT 'Current processing status',
  `processing_completed_at` DATETIME COMMENT 'When processing finished',
  
  -- Error tracking
  `error_message` TEXT COMMENT 'Error details if processing failed',
  `error_type` VARCHAR(128) COMMENT 'Exception type or error category',
  
  -- Indexes and constraints
  UNIQUE KEY `unique_file` (`s3_bucket`, `s3_key`, `s3_etag`),
  INDEX `idx_status` (`status`),
  INDEX `idx_site_code` (`site_code`),
  INDEX `idx_processing_completed` (`processing_completed_at`),
  INDEX `idx_s3_last_modified` (`s3_last_modified`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Tracks processing status of metric files from S3';
/*!40101 SET character_set_client = @saved_cs_client */;

/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;
/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed

