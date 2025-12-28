/*M!999999\- enable the sandbox mode */ 
-- MariaDB dump 10.19  Distrib 10.11.13-MariaDB, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: fm_laravel
-- ------------------------------------------------------
-- Server version	10.11.13-MariaDB-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `account_types`
--

DROP TABLE IF EXISTS `account_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `account_types_created_by_foreign` (`created_by`),
  KEY `account_types_updated_by_foreign` (`updated_by`),
  CONSTRAINT `account_types_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `account_types_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_types`
--

LOCK TABLES `account_types` WRITE;
/*!40000 ALTER TABLE `account_types` DISABLE KEYS */;
INSERT INTO `account_types` VALUES
(1,'Accounts Receivable (A/R)','Asset',NULL,'active',1,1,'2025-12-22 07:30:25','2025-12-23 08:49:57'),
(3,'Credit Card','Liability',NULL,'active',1,1,'2025-12-22 07:40:30','2025-12-22 07:40:30'),
(4,'Asset','Asset','Resources owned by the business with economic value (e.g., cash, inventory, equipment).','active',NULL,NULL,'2025-12-22 07:46:04','2025-12-22 07:46:04'),
(5,'Liability','Liability','Obligations or debts owed to others (e.g., loans, accounts payable).','active',NULL,NULL,'2025-12-22 07:46:04','2025-12-22 07:46:04'),
(6,'Equity','Equity','Owner\'s residual interest in the business after liabilities are deducted from assets.','active',NULL,NULL,'2025-12-22 07:46:04','2025-12-22 07:46:04'),
(7,'Income','Income','Revenues or gains from business operations (e.g., sales, service income).','active',NULL,NULL,'2025-12-22 07:46:04','2025-12-22 07:46:04'),
(8,'Expense','Expense','Costs incurred in generating revenue (e.g., rent, salaries, materials).','active',NULL,NULL,'2025-12-22 07:46:04','2025-12-22 07:46:04');
/*!40000 ALTER TABLE `account_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customer_types`
--

DROP TABLE IF EXISTS `customer_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_types_created_by_foreign` (`created_by`),
  KEY `customer_types_updated_by_foreign` (`updated_by`),
  CONSTRAINT `customer_types_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `customer_types_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customer_types`
--

LOCK TABLES `customer_types` WRITE;
/*!40000 ALTER TABLE `customer_types` DISABLE KEYS */;
INSERT INTO `customer_types` VALUES
(1,'Restoration','active','for restoration type work',1,1,'2025-12-20 11:27:07','2025-12-20 11:30:06'),
(3,'Retail','active',NULL,1,1,'2025-12-20 11:33:39','2025-12-20 11:33:39');
/*!40000 ALTER TABLE `customer_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `company_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `customer_type` varchar(255) NOT NULL DEFAULT 'individual',
  `customer_status` varchar(255) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customers_parent_id_foreign` (`parent_id`),
  KEY `customers_created_by_foreign` (`created_by`),
  KEY `customers_updated_by_foreign` (`updated_by`),
  CONSTRAINT `customers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `customers_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES
(1,NULL,'Test Customer',NULL,'test@example.com',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'individual','active',NULL,NULL,NULL,'2025-12-18 08:09:36','2025-12-18 08:09:36'),
(2,NULL,'John Doe',NULL,'john@example.com','555-1234',NULL,NULL,NULL,NULL,NULL,NULL,'individual','active',NULL,NULL,NULL,'2025-12-18 08:12:14','2025-12-18 08:12:14'),
(3,NULL,'FOS','First OnSite Restoration','info@firstonsite.com','604-280-0556','778-255-2778','1234 Address Street','#1228','Vancouver','BC','V3X2V8','restoration','active','yes this should work',1,1,'2025-12-18 13:16:36','2025-12-18 13:16:36'),
(4,NULL,NULL,'FOS3','info22@fos.com',NULL,'778-123-4567','4115 William Street','#3455','Vancouver','BC','V2X8V8','company','active','hello test for just adding the company',1,1,'2025-12-19 08:10:25','2025-12-19 08:14:17');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `detail_types`
--

DROP TABLE IF EXISTS `detail_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `detail_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_type_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `detail_types_account_type_id_foreign` (`account_type_id`),
  KEY `detail_types_created_by_foreign` (`created_by`),
  KEY `detail_types_updated_by_foreign` (`updated_by`),
  CONSTRAINT `detail_types_account_type_id_foreign` FOREIGN KEY (`account_type_id`) REFERENCES `account_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `detail_types_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `detail_types_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `detail_types`
--

LOCK TABLES `detail_types` WRITE;
/*!40000 ALTER TABLE `detail_types` DISABLE KEYS */;
INSERT INTO `detail_types` VALUES
(1,1,'Accounts Receivable (A/R)','active',NULL,1,1,'2025-12-23 09:03:00','2025-12-23 09:03:00'),
(3,3,'Credit Card','active',NULL,1,1,'2025-12-23 10:23:39','2025-12-23 10:23:39');
/*!40000 ALTER TABLE `detail_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `gl_accounts`
--

DROP TABLE IF EXISTS `gl_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `gl_accounts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `account_number` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `account_type_id` bigint(20) unsigned NOT NULL,
  `detail_type_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `gl_accounts_account_number_unique` (`account_number`),
  KEY `gl_accounts_account_type_id_foreign` (`account_type_id`),
  KEY `gl_accounts_detail_type_id_foreign` (`detail_type_id`),
  KEY `gl_accounts_parent_id_foreign` (`parent_id`),
  KEY `gl_accounts_created_by_foreign` (`created_by`),
  KEY `gl_accounts_updated_by_foreign` (`updated_by`),
  CONSTRAINT `gl_accounts_account_type_id_foreign` FOREIGN KEY (`account_type_id`) REFERENCES `account_types` (`id`),
  CONSTRAINT `gl_accounts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `gl_accounts_detail_type_id_foreign` FOREIGN KEY (`detail_type_id`) REFERENCES `detail_types` (`id`),
  CONSTRAINT `gl_accounts_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `gl_accounts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `gl_accounts_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `gl_accounts`
--

LOCK TABLES `gl_accounts` WRITE;
/*!40000 ALTER TABLE `gl_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `gl_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `labour_types`
--

DROP TABLE IF EXISTS `labour_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `labour_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `labour_types_created_by_foreign` (`created_by`),
  KEY `labour_types_updated_by_foreign` (`updated_by`),
  CONSTRAINT `labour_types_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `labour_types_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `labour_types`
--

LOCK TABLES `labour_types` WRITE;
/*!40000 ALTER TABLE `labour_types` DISABLE KEYS */;
INSERT INTO `labour_types` VALUES
(1,'Carpet','added notes',1,1,'2025-12-20 07:20:35','2025-12-20 07:25:02'),
(2,'Laminate',NULL,1,1,'2025-12-20 07:20:43','2025-12-20 07:20:43');
/*!40000 ALTER TABLE `labour_types` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES
(1,'0001_01_01_000000_create_users_table',1),
(2,'0001_01_01_000001_create_cache_table',1),
(3,'0001_01_01_000002_create_jobs_table',1),
(4,'2025_12_16_193959_create_permission_tables',2),
(5,'2025_12_17_234741_create_customers_table',3),
(6,'2025_12_19_000547_modify_name_column_in_customers_table',4),
(7,'2025_12_19_000920_make_name_nullable_in_customers_table',4),
(8,'2025_12_19_003621_create_vendors_table',5),
(9,'2025_12_19_012502_create_vendor_reps_table',6),
(10,'2025_12_19_022511_create_vendor_vendor_rep_table',7),
(11,'2025_12_19_222257_create_project_managers_table',8),
(12,'2025_12_19_230840_create_labour_types_table',9),
(13,'2025_12_20_022525_create_unit_measures_table',10),
(14,'2025_12_20_030249_create_customer_types_table',11),
(15,'*_create_account_types_table',12),
(16,'2025_12_21_233208_add_category_to_account_types_table',13),
(17,'2025_12_21_235223_create_detail_types_table',14),
(18,'2025_12_23_012154_create_tax_agencies_table',15),
(19,'2025_12_23_020105_create_gl_accounts_table',16);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_permissions`
--

DROP TABLE IF EXISTS `model_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`model_id`,`model_type`),
  KEY `model_has_permissions_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_permissions`
--

LOCK TABLES `model_has_permissions` WRITE;
/*!40000 ALTER TABLE `model_has_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `model_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `model_has_roles`
--

DROP TABLE IF EXISTS `model_has_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `model_has_roles` (
  `role_id` bigint(20) unsigned NOT NULL,
  `model_type` varchar(255) NOT NULL,
  `model_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`role_id`,`model_id`,`model_type`),
  KEY `model_has_roles_model_id_model_type_index` (`model_id`,`model_type`),
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `model_has_roles`
--

LOCK TABLES `model_has_roles` WRITE;
/*!40000 ALTER TABLE `model_has_roles` DISABLE KEYS */;
INSERT INTO `model_has_roles` VALUES
(1,'App\\Models\\User',1),
(2,'App\\Models\\User',4);
/*!40000 ALTER TABLE `model_has_roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES
(1,'manage users','web','2025-12-18 07:04:21','2025-12-18 07:04:21'),
(2,'manage roles','web','2025-12-18 07:04:26','2025-12-18 07:04:26'),
(3,'view dashboard','web','2025-12-18 07:04:31','2025-12-18 07:04:31'),
(4,'edit settings','web','2025-12-18 07:04:37','2025-12-18 07:04:37'),
(5,'view customers','web','2025-12-18 07:42:50','2025-12-18 07:42:50'),
(6,'create customers','web','2025-12-18 07:42:56','2025-12-18 07:42:56'),
(7,'edit customers','web','2025-12-18 07:43:01','2025-12-18 07:43:01'),
(8,'delete customers','web','2025-12-18 07:43:06','2025-12-18 07:43:06');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `project_managers`
--

DROP TABLE IF EXISTS `project_managers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `project_managers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_managers_customer_id_foreign` (`customer_id`),
  KEY `project_managers_created_by_foreign` (`created_by`),
  KEY `project_managers_updated_by_foreign` (`updated_by`),
  CONSTRAINT `project_managers_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `project_managers_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_managers_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `project_managers`
--

LOCK TABLES `project_managers` WRITE;
/*!40000 ALTER TABLE `project_managers` DISABLE KEYS */;
INSERT INTO `project_managers` VALUES
(1,3,'Matt VanBrunt','778-884-9135','604-280-9595','mvb@fos.com','he need blah blah blah',1,1,'2025-12-20 06:40:29','2025-12-20 06:45:29');
/*!40000 ALTER TABLE `project_managers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `role_has_permissions`
--

DROP TABLE IF EXISTS `role_has_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `role_has_permissions` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `role_has_permissions_role_id_foreign` (`role_id`),
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `role_has_permissions`
--

LOCK TABLES `role_has_permissions` WRITE;
/*!40000 ALTER TABLE `role_has_permissions` DISABLE KEYS */;
INSERT INTO `role_has_permissions` VALUES
(1,1),
(2,1),
(3,1),
(4,1),
(5,2),
(6,2),
(7,2);
/*!40000 ALTER TABLE `role_has_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `guard_name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_guard_name_unique` (`name`,`guard_name`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES
(1,'Admin','web','2025-12-17 03:41:41','2025-12-17 03:41:41'),
(2,'Manager','web','2025-12-18 12:10:08','2025-12-18 12:10:08');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES
('1fCJtqJ1jPBQZgYj8NFYkfmw0zMw5PiZjZn8f03s',NULL,'192.168.1.99','Mozilla/5.0 (X11; Linux x86_64; rv:144.0) Gecko/20100101 Firefox/144.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoidm5VMGRYZjlzNVcydzhZdXVsR1BHNmZTbk9GdDBhTmZWenNPaGZZeiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766661985),
('20BtBaWNRv1RX1zi3q1fMnnm0E69zFca88wKYVOP',NULL,'192.168.1.99','python-requests/2.31.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVzZVRWlYVkthdHZMMmwyS1JEV0xkQVhhWGVsWW5wbnJ6SUtoZVN4MiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766640444),
('3EXf6kJEG7DcgGL67EEi7mkt12JZULO0UXyyB082',NULL,'192.168.1.99','Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/5d7.36 (KHTML, like Gecko) Chrome/138.0.0.0 Mobile Safari/537.36','YToyOntzOjY6Il90b2tlbiI7czo0MDoiWGV1WmVib25OWUl5U2prbkQzSUU1NXVDZmtWb21zMGpuTW9TUE00diI7czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1766622398),
('41cAgMJaNxmQ19oxtXIGB3V3sDGP0gWTKGuDOkQG',NULL,'192.168.1.99','Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)','YTozOntzOjY6Il90b2tlbiI7czo0MDoidVhmd2RSSUVjOVNGZG5pMzJraDFYYTJ1V3VubFVqaFQ5TjZKM2JxNCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766634978),
('4i8kYIFnuPCECwNXmrtZqWjJW7aveAJI4zxc41Ld',NULL,'192.168.1.99','Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)','YTozOntzOjY6Il90b2tlbiI7czo0MDoib25Mb0ZTSlJjYU5EbmlNZDNySWQ0OFE2N3I5a3hPMEdiZkhic3FFVCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766657473),
('50BvnzWe060c4NKsnaAhnpN1yxluUo9alkHykCHM',NULL,'192.168.1.99','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSVI2ZXNSMmVJcGdzR1psRlBPREQyNGl4aGpuRklYb2JrZTZ0cU83QiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766674875),
('7kqZ6QjyvmUOtFtcY6viaCnzho7KCXyWvYMc9pVm',NULL,'192.168.1.99','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/142.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiSk5PTllVekJoVHh2bWZ1RzBhN2NCWGdhYWhkTXQzdzR6dUZXdXNxWiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766631043),
('8naIExIE6K1cCFZ58lIHbT6Js7tqSFk8IfhmsZPZ',NULL,'192.168.1.99','Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)','YTozOntzOjY6Il90b2tlbiI7czo0MDoiVEY0bFdGMkZ6Wm91aDFjeFVEakNmd0w0c21tS3puWDNxV3VXdVVjbyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjQ6Imh0dHBzOi8vZm0ucm1mbG9vcmluZy5jYSI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1766669306),
('8rbaoLuawFOEJBa3GCkfB7PJmshi6IPPe576yQtn',NULL,'192.168.1.99','python-requests/2.32.5','YTozOntzOjY6Il90b2tlbiI7czo0MDoib0hnYTYxTUdvT0FPN2QzYWhTajlsQjB6R0l2Mk11WnJKY0diSlExQyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766649511),
('9Y9w6KiXSyKDqHWmAzwGobAFJRfpxqfeA4M7Bzys',NULL,'192.168.1.99','Mozilla/5.0 (X11; Linux x86_64; rv:142.0) Gecko/20100101 Firefox/142.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYjFLck1HUlhJcnFGb1l5WWdVYVJTZUlBNWJ4OVN2WjZ3VThVSmhGZSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHBzOi8vY3BhbmVsLmZtLnJtZmxvb3JpbmcuY2EiO3M6NToicm91dGUiO047fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1766622287),
('AaNy957X7iqsFg2kLx91VIe1VJFrs2R4MRI09PMv',NULL,'192.168.1.99','Mozilla/5.0 (compatible; wpbot/1.3; +https://forms.gle/ajBaxygz9jSR8p8G9)','YTozOntzOjY6Il90b2tlbiI7czo0MDoiYzh2TW9IUXp1dm5IaU5ySjF2RlhxdlJsOVZTeVF6ejdPdmNEamFBMyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MzE6Imh0dHBzOi8vd2ViZGlzay5tZWFsc2Zvcmxlc3MuY2EiO3M6NToicm91dGUiO047fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1766636646),
('BHiEhoztj0vDm9aSwWLdEPbkOHmi11ZWE8fGgOB2',NULL,'192.168.1.99','Mozilla/5.0 (compatible; wpbot/1.3; +https://forms.gle/ajBaxygz9jSR8p8G9)','YTozOntzOjY6Il90b2tlbiI7czo0MDoidGZJUVd0S25RMGZqN0NscUxyNnIwU0EwR0ZFc2ZOZ2N0QmY3b2c5ciI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjI6Imh0dHBzOi8vY3BhbmVsLmd2ZWwuY2EiO3M6NToicm91dGUiO047fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1766628795),
('cyKS6SawKMvzkQRjmzw1evuQnyvEtjcEAfUWiwbb',NULL,'192.168.1.99','Hello from Palo Alto Networks, find out more about our scans in https://docs-cortex.paloaltonetworks.com/r/1/Cortex-Xpanse/Scanning-activity','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQWJyU1pZd0FHVFNrT01yUjJOa0VIU3l4VTkwR3l0eVNRT0w0RHVhbiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766651154),
('DXzkpGXsjryzZVGOIrTeCYoUnuSag43bHqERtWkv',NULL,'192.168.1.99','','YTozOntzOjY6Il90b2tlbiI7czo0MDoiazZvaTd5d25yYkVIaXlYQUtXdExBVlZhek5EdG8zdkpiTE0xdFVVbyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766648822),
('GjWcuynWhDtFLSW0edQp0dbaL41c3ymVgsFAFt2I',NULL,'192.168.1.99','Mozilla/5.0 zgrab/0.x','YTozOntzOjY6Il90b2tlbiI7czo0MDoiR3JUTFFPRkNNaXBIcjF0cUVLU2JxcVlQbmpIblpreGFVS1U2T3V4RyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766653482),
('GPs255CVxvYvUuXvMF6Yk7tfEipfChRIeNtgHWE3',NULL,'192.168.1.99','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoidzhQN3duUk51SFJUOEp5eEUwRkNrV294eUpBWnFpeFdsNUVvTWFudSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766624776),
('gwfIyiuOWEMZ1iqC3oIteKN6RcHT65bG1SxPZ8ka',NULL,'192.168.1.99','Mozilla/5.0 (compatible; wpbot/1.3; +https://forms.gle/ajBaxygz9jSR8p8G9)','YTozOntzOjY6Il90b2tlbiI7czo0MDoiblYxVUZPcmcyMmxnTXVyeUQ5bUZISG1TNkdobUduOXpXT1JuZzJzTyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjg6Imh0dHBzOi8vY3BhbmVsLnJtZmxvb3JpbmcuY2EiO3M6NToicm91dGUiO047fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1766666233),
('jE5K9F0o7Nsq5TAuXf2Oqi2sVTwDkqSXxweJnZFP',NULL,'192.168.1.99','Mozilla/5.0 (compatible; InternetMeasurement/1.0; +https://internet-measurement.com/)','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWkoyeldBSW04WWlRQ0RpekROVE5XSjVyNHQ1bHBtclNRWGZhYWtjRSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766670133),
('jhfhrinkmZA7QA51cmKLpy1X2U5DL9iReG0jgxTD',NULL,'192.168.1.99','Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:132.0) Gecko/20100101 Firefox/132.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZTFYTEM2cHNsc1FmaWJvbUs3aWx6Q1JNbzhMWlp0Nk9Sdlc2ODVmdyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766685702),
('KRb8twwOM4TmPfHAAf5mn9dFA9tAkjpzqE2hSoBH',NULL,'192.168.1.99','Scrapy/2.13.4 (+https://scrapy.org)','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRFpHTURSUmxDVzZNdUF3SDE5amVrUk9na2xrQTk4T21mR3dmTVZoaSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjU6Imh0dHBzOi8vcm1mbG9vcmluZy4zY3guY2EiO3M6NToicm91dGUiO047fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1766673351),
('L91SS1uXughJ1PjAH5lZDg4yisLQPBOLYVZdkCAa',NULL,'192.168.1.99','Mozilla/5.0 zgrab/0.x','YTozOntzOjY6Il90b2tlbiI7czo0MDoiemJLR3BUZHAzY3BDeFZlQktlc1JENGxCZEdmYlJHaFFjc2Jua2ZNTyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766681603),
('lEIn2U5qdFqmJwQRjvRs9aGjKLIz6rVlQGhYzmT4',NULL,'192.168.1.99','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/78.0.3904.108 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZVBWZ3ZndGNNM3MzVDNwVUdRbmJPSVc2R3VqaFNKak53dzlvcWV1cCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTE6Imh0dHBzOi8vMTkyLjI1MS4wLjIxLz9YREVCVUdfU0VTU0lPTl9TVEFSVD1waHBzdG9ybSI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1766684567),
('LmvIOk3gIBt8Lts8HtMyrhUehEk9NbTm9g2AwgqO',NULL,'192.168.1.99','fasthttp','YTozOntzOjY6Il90b2tlbiI7czo0MDoiM3JvVjlTYXdxYmlOTmhWa0F5NkdNS0piQ2VBbkNyZXAwWDlJYUh4YiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766638543),
('LXzEUqrIw6fFNVAg9YkcyCftdgTUe30BjZoG6onl',NULL,'192.168.1.99','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiTXIwUUZkRHptQnBJeE5NTHlVVUljMUpRbGt6dUxvbU5aOFkwTXRYRCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjQ6Imh0dHBzOi8vZm0ucm1mbG9vcmluZy5jYSI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1766655745),
('npPaME4nA4Fk3mpAZmx2FazwNqphpsBjlvjbDzHc',NULL,'192.168.1.99','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiWk02RVQzdTRld0hVVjRaaHdUVDZ4aUsyWDlGTXcwYnBMNkFaN2Y5OCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766658750),
('OjvoNCAeHkNlUEFE91gWdbu1qmctdwwKNcOl1yLi',NULL,'192.168.1.99','HTTP Banner Detection (https://security.ipip.net)','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ2ZXUEtQM1U2azRWMkNtUHhBSVdIRVAxU2VSUXVsWmxxRU4yUjhydiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766675269),
('pBsoEnkCPnUhEXzPTpW4QaYWYHIuNZAISachp8Nx',NULL,'192.168.1.99','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.113 Safari/537.36 Assetnote/1.0.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiODJNNldxelU1ZTJqWnJLUVBkM2N5ZUh1NFA3cEg1c1BaMlhRWnIxNiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjQ6Imh0dHBzOi8vZm0ucm1mbG9vcmluZy5jYSI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1766653569),
('PPwj8unFkL6mMpx94oWSMmpostXusSU58XqGjbml',NULL,'192.168.1.99','cypex.ai/scanning Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/126.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoibDdSNE1pdTZsV2RlbnlKRWZ0bllkNDhBSVp6ZThmajlvRU1wMlJicSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766634739),
('py0gWWhaV3HYaegALytEg4jlBx12pGsUoRrbm9ci',NULL,'192.168.1.99','Mozilla/5.0 (X11; Linux x86_64; rv:1.9.7.20) Gecko/ Firefox/3.6.11','YTozOntzOjY6Il90b2tlbiI7czo0MDoiMG15VjZKN3ZEc2lPQ3hNdzNnWWJFblUzbzZNR3ZkM1ZQU01SbUtWZiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766688445),
('qDheuz9wP30xh9HXYbGaJPJkGsAFd8ytTYEUrDWa',NULL,'192.168.1.99','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiY0VYSHBHTWpaMU9odTRpR1Z1SUpZVDVsWEFsYkcyeWFyb1hJWllBOSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766622697),
('qkZUiaXOryYg4GDigjsbW34CYjZttqNlGzhDLcDa',NULL,'192.168.1.99','fasthttp','YTozOntzOjY6Il90b2tlbiI7czo0MDoiOFdvU2t4ZzdMQWc4djJxZUJWUXhjM0ZpUVg0V013ZnlGbkc4RUhGYyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766638543),
('R7TjyUaqdEUhN3GDVgP4Pab9xeSm1chEua0NCpm5',NULL,'192.168.1.99','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRk1NdGNTRUVBRGR4aDljSDRQYWlicEt4ZlhYOEJYZk1VNGIzb0xycyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766629356),
('ScOXrUfVh05y3fSNnif1LdVOAF9rYkv54Cl7W2yR',NULL,'192.168.1.99','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiRVd1N2kzV21DVW9nVkV3TDl2VTZUOG4yUXI4QUR6U1FXS1hKU1VrUCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6Mjg6Imh0dHBzOi8vcmVtb3RlLnJtZmxvb3JpbmcuY2EiO3M6NToicm91dGUiO047fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1766661434),
('SFiO8xEAfg2jlsQtvfqIlMAgddKqMWdU5KLe8m7g',NULL,'192.168.1.99','fasthttp','YTozOntzOjY6Il90b2tlbiI7czo0MDoiT3RaS3NkNWZKaXhoQlBSeVBiQWtDQm9LRFZScjNzY1RkZndMZTdDMCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766638541),
('SKuBPZGAHzk8e1AjPbbGJN7xuM317gY7y0oCsod3',NULL,'192.168.1.99','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiT3JTcTRKd21VNFJMSVNYVWt0NkJ4ZWFjdDBQVDc3a21TZVNJTlZFZyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NjQ6Imh0dHBzOi8vZGV2aWNlLTM4MzIyYTVjLTU4MjQtNGU1ZC1hMTlkLTgwOTRhNDBkYTMyMS5yZW1vdGV3ZC5jb20iO3M6NToicm91dGUiO047fXM6NjoiX2ZsYXNoIjthOjI6e3M6Mzoib2xkIjthOjA6e31zOjM6Im5ldyI7YTowOnt9fX0=',1766676509),
('ULoK0EZmtoM3oRl2KihzP5Q066tTEgd0lh3bJttQ',NULL,'192.168.1.99','fasthttp','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUUVJcEFpS01oRXpiR3JmYWRkTmVXMmJIdTlKODlYWWN2Qkc3TzhDTyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766638542),
('USbaMYzM948z9E7tKWdubSQSnLFqudTsoLiaDFXZ',NULL,'192.168.1.99','Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.61 Safari/537.36','YTozOntzOjY6Il90b2tlbiI7czo0MDoiN1lmVXhJR2xqanVod3FJdWZqcWNvdEltbFBmcjdJMjB5enlBbDUxayI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766631237),
('vxSyTZoiO0xPfsvLTUXlYh1yoPahMLybNiOy4oT0',NULL,'192.168.1.99','','YTozOntzOjY6Il90b2tlbiI7czo0MDoibkNVR3JxOWZGWHJRMnVLREphZGhzY3o2aGhUYlllME96b3dKOTduUiI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766621888),
('xLyfvi3ASth04yJYNSkauJobpDu57qUmSFzOXTUA',NULL,'192.168.1.99','Mozilla/5.0 (compatible; CensysInspect/1.1; +https://about.censys.io/)','YTozOntzOjY6Il90b2tlbiI7czo0MDoiUlFxbmpHTDBxRTVxRnd1alJiRERpM1pwZ205bTVBeEg1WWU1MkM1bSI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjY6Imh0dHBzOi8vMTkyLjI1MS4wLjIxL2xvZ2luIjtzOjU6InJvdXRlIjtzOjU6ImxvZ2luIjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==',1766635029),
('Z7rZGn9HNtuYKFiae8Z9037u29bOo0qV3A61aRHH',NULL,'192.168.1.99','python-requests/2.31.0','YTozOntzOjY6Il90b2tlbiI7czo0MDoiZ05WTlBPWHV4SFVBQ0tnZEVJRDU2ek5sT1FtTFBrR242eklNdlNMSyI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766673376),
('zDf90KH5PKhD4yg6VZPaW0sGlJIuYf8Iz6SManSk',NULL,'192.168.1.99','fasthttp','YTozOntzOjY6Il90b2tlbiI7czo0MDoiQlJnUlk5WHRGdXNETTF4U0M2a0tQaFVVYkN2SWRtMHdwY0QxR083cCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjA6Imh0dHBzOi8vMTkyLjI1MS4wLjIxIjtzOjU6InJvdXRlIjtOO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX19',1766638541);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tax_agencies`
--

DROP TABLE IF EXISTS `tax_agencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `tax_agencies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `registration_number` varchar(255) DEFAULT NULL,
  `next_period_month` varchar(255) DEFAULT NULL,
  `filing_frequency` varchar(255) DEFAULT NULL,
  `reporting_method` varchar(255) DEFAULT NULL,
  `collect_on_sales` tinyint(1) NOT NULL DEFAULT 0,
  `pay_on_purchases` tinyint(1) NOT NULL DEFAULT 0,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tax_agencies_created_by_foreign` (`created_by`),
  KEY `tax_agencies_updated_by_foreign` (`updated_by`),
  CONSTRAINT `tax_agencies_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `tax_agencies_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `tax_agencies`
--

LOCK TABLES `tax_agencies` WRITE;
/*!40000 ALTER TABLE `tax_agencies` DISABLE KEYS */;
INSERT INTO `tax_agencies` VALUES
(1,'Canada Revenue Agency (CRA)','012345','May','Quarterly','Accrual',1,1,'active',NULL,1,1,'2025-12-23 09:34:56','2025-12-23 09:43:07'),
(2,'Ministry of Finance (BC)','01218776','January','Quarterly','Accrual',1,1,'active',NULL,1,1,'2025-12-23 09:35:23','2025-12-23 09:35:23');
/*!40000 ALTER TABLE `tax_agencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `unit_measures`
--

DROP TABLE IF EXISTS `unit_measures`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `unit_measures` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unit_measures_code_unique` (`code`),
  KEY `unit_measures_created_by_foreign` (`created_by`),
  KEY `unit_measures_updated_by_foreign` (`updated_by`),
  CONSTRAINT `unit_measures_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `unit_measures_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `unit_measures`
--

LOCK TABLES `unit_measures` WRITE;
/*!40000 ALTER TABLE `unit_measures` DISABLE KEYS */;
INSERT INTO `unit_measures` VALUES
(1,'SF','Square Feet','active',1,1,'2025-12-20 10:42:21','2025-12-20 10:46:22'),
(2,'Ea','Each','active',1,1,'2025-12-20 10:43:27','2025-12-20 10:43:27'),
(3,'sy','Square Yard','inactive',1,1,'2025-12-20 10:43:37','2025-12-20 10:46:27');
/*!40000 ALTER TABLE `unit_measures` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES
(1,'Admin','admin@rmflooring.ca',NULL,'$2y$12$PlpNdeuX1mIzp54U76Ozt.fXHrFKEOqWHPGPEhRSacicPG0sIT7Ue',NULL,'2025-12-17 03:21:53','2025-12-17 03:21:53'),
(2,'Regular User','user@rmflooring.ca',NULL,'$2y$12$FvFATMvpIf2TOpOXO7c1heaxrX5yP8WvbqCTilSTH2BXOt9X4uZPK',NULL,'2025-12-17 04:04:45','2025-12-17 04:04:45'),
(4,'Ellie','ellie22@hotmail.com',NULL,'$2y$12$7WmgbYxkEZiGMuHafGaD3OinGAHtTRd60185z22bJCewc2nqHFBvK',NULL,'2025-12-17 09:51:24','2025-12-17 09:51:24');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_reps`
--

DROP TABLE IF EXISTS `vendor_reps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_reps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_reps_created_by_foreign` (`created_by`),
  KEY `vendor_reps_updated_by_foreign` (`updated_by`),
  CONSTRAINT `vendor_reps_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `vendor_reps_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_reps`
--

LOCK TABLES `vendor_reps` WRITE;
/*!40000 ALTER TABLE `vendor_reps` DISABLE KEYS */;
INSERT INTO `vendor_reps` VALUES
(1,'Kevin k','778-234-2827','5661425','Kevink@metrofloors.com','kevin is the best',1,1,'2025-12-19 10:09:40','2025-12-19 10:16:31');
/*!40000 ALTER TABLE `vendor_reps` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_vendor_rep`
--

DROP TABLE IF EXISTS `vendor_vendor_rep`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_vendor_rep` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint(20) unsigned NOT NULL,
  `vendor_rep_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_vendor_rep_vendor_id_foreign` (`vendor_id`),
  KEY `vendor_vendor_rep_vendor_rep_id_foreign` (`vendor_rep_id`),
  CONSTRAINT `vendor_vendor_rep_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vendor_vendor_rep_vendor_rep_id_foreign` FOREIGN KEY (`vendor_rep_id`) REFERENCES `vendor_reps` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_vendor_rep`
--

LOCK TABLES `vendor_vendor_rep` WRITE;
/*!40000 ALTER TABLE `vendor_vendor_rep` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_vendor_rep` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendors` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_name` varchar(255) NOT NULL,
  `contact_name` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `mobile` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `address2` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `province` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `vendor_type` varchar(255) DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `account_number` varchar(255) DEFAULT NULL,
  `terms` varchar(255) DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `updated_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendors_created_by_foreign` (`created_by`),
  KEY `vendors_updated_by_foreign` (`updated_by`),
  CONSTRAINT `vendors_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `vendors_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors`
--

LOCK TABLES `vendors` WRITE;
/*!40000 ALTER TABLE `vendors` DISABLE KEYS */;
INSERT INTO `vendors` VALUES
(1,'Shaw Floors','Jason Loser','604-280-9595',NULL,'info@shawfloors.com','1228 West milner Rd','#302','Blackhill','MB','V2C8n7',NULL,'testing a new vendor.','Flooring Supplier','active','16225','Net 30',1,1,'2025-12-19 09:05:59','2025-12-19 09:14:16');
/*!40000 ALTER TABLE `vendors` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-12-25 12:22:04
