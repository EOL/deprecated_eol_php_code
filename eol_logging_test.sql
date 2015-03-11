-- MySQL dump 10.13  Distrib 5.5.40, for debian-linux-gnu (x86_64)
--
-- Host: localhost    Database: eol_logging_test
-- ------------------------------------------------------
-- Server version	5.5.40-0ubuntu0.12.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `activities`
--

DROP TABLE IF EXISTS `activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=44 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activities`
--

LOCK TABLES `activities` WRITE;
/*!40000 ALTER TABLE `activities` DISABLE KEYS */;
INSERT INTO `activities` VALUES (1),(2),(3),(4),(5),(6),(7),(8),(9),(10),(11),(12),(13),(14),(15),(16),(17),(18),(19),(20),(21),(22),(23),(24),(25),(26),(27),(28),(29),(30),(31),(32),(33),(34),(35),(36),(37),(38),(39),(40),(41),(42),(43);
/*!40000 ALTER TABLE `activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_logs`
--

DROP TABLE IF EXISTS `api_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `api_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_ip` varchar(100) DEFAULT NULL,
  `request_uri` varchar(200) DEFAULT NULL,
  `method` varchar(100) DEFAULT NULL,
  `version` varchar(10) DEFAULT NULL,
  `request_id` varchar(50) DEFAULT NULL,
  `format` varchar(10) DEFAULT NULL,
  `key` char(40) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_logs`
--

LOCK TABLES `api_logs` WRITE;
/*!40000 ALTER TABLE `api_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `collection_activity_logs`
--

DROP TABLE IF EXISTS `collection_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collection_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `collection_id` int(11) NOT NULL,
  `collection_item_id` int(11) DEFAULT NULL,
  `activity_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `index_collection_activity_logs_on_created_at` (`created_at`),
  KEY `index_collection_activity_logs_on_collection_id` (`collection_id`),
  KEY `index_collection_activity_logs_on_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `collection_activity_logs`
--

LOCK TABLES `collection_activity_logs` WRITE;
/*!40000 ALTER TABLE `collection_activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `collection_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `community_activity_logs`
--

DROP TABLE IF EXISTS `community_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `community_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `activity_id` int(11) NOT NULL,
  `community_id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `collection_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `community_activity_logs`
--

LOCK TABLES `community_activity_logs` WRITE;
/*!40000 ALTER TABLE `community_activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `community_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curator_activity_logs`
--

DROP TABLE IF EXISTS `curator_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curator_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `target_id` int(11) DEFAULT NULL,
  `changeable_object_type_id` int(11) DEFAULT NULL,
  `comment_id` int(11) DEFAULT NULL,
  `taxon_concept_id` int(11) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `activity_id` int(11) DEFAULT NULL,
  `hierarchy_entry_id` int(11) DEFAULT NULL,
  `data_object_guid` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `object_id` (`target_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curator_activity_logs`
--

LOCK TABLES `curator_activity_logs` WRITE;
/*!40000 ALTER TABLE `curator_activity_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `curator_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `data_search_logs`
--

DROP TABLE IF EXISTS `data_search_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `data_search_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `q` varchar(512) DEFAULT NULL,
  `uri` varchar(512) DEFAULT NULL,
  `from` float DEFAULT NULL,
  `to` float DEFAULT NULL,
  `sort` varchar(64) DEFAULT NULL,
  `unit_uri` varchar(512) DEFAULT NULL,
  `taxon_concept_id` int(11) DEFAULT NULL,
  `clade_was_ignored` tinyint(1) DEFAULT '0',
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(512) DEFAULT NULL,
  `number_of_results` int(11) DEFAULT NULL,
  `time_in_seconds` float DEFAULT NULL,
  `known_uri_id` int(11) DEFAULT NULL,
  `language_id` int(11) DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `data_search_logs`
--

LOCK TABLES `data_search_logs` WRITE;
/*!40000 ALTER TABLE `data_search_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `data_search_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `external_link_logs`
--

DROP TABLE IF EXISTS `external_link_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `external_link_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `external_url` varchar(255) NOT NULL,
  `ip_address_raw` int(11) NOT NULL,
  `ip_address_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_agent` varchar(160) NOT NULL,
  `path` varchar(128) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `external_link_logs`
--

LOCK TABLES `external_link_logs` WRITE;
/*!40000 ALTER TABLE `external_link_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `external_link_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ip_addresses`
--

DROP TABLE IF EXISTS `ip_addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ip_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `number` int(11) NOT NULL,
  `success` tinyint(1) NOT NULL,
  `country_code` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `latitude` float DEFAULT NULL,
  `longitude` float DEFAULT NULL,
  `provider` varchar(255) NOT NULL,
  `street_address` varchar(255) DEFAULT NULL,
  `postal_code` varchar(255) DEFAULT NULL,
  `precision` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `ip_addresses`
--

LOCK TABLES `ip_addresses` WRITE;
/*!40000 ALTER TABLE `ip_addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `ip_addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `page_view_logs`
--

DROP TABLE IF EXISTS `page_view_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `page_view_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `agent_id` int(11) DEFAULT NULL,
  `taxon_concept_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`),
  KEY `taxon_concept_id` (`taxon_concept_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `page_view_logs`
--

LOCK TABLES `page_view_logs` WRITE;
/*!40000 ALTER TABLE `page_view_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `page_view_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `search_logs`
--

DROP TABLE IF EXISTS `search_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `search_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `search_term` varchar(255) DEFAULT NULL,
  `total_number_of_results` int(11) DEFAULT NULL,
  `number_of_common_name_results` int(11) DEFAULT NULL,
  `number_of_scientific_name_results` int(11) DEFAULT NULL,
  `number_of_suggested_results` int(11) DEFAULT NULL,
  `number_of_stub_page_results` int(11) DEFAULT NULL,
  `ip_address_raw` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `taxon_concept_id` int(11) DEFAULT NULL,
  `parent_search_log_id` int(11) DEFAULT NULL,
  `clicked_result_at` datetime DEFAULT NULL,
  `user_agent` varchar(160) NOT NULL,
  `path` varchar(128) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL,
  `number_of_tag_results` int(11) DEFAULT NULL,
  `search_type` varchar(255) DEFAULT 'text',
  PRIMARY KEY (`id`),
  KEY `index_search_logs_on_search_term` (`search_term`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `search_logs`
--

LOCK TABLES `search_logs` WRITE;
/*!40000 ALTER TABLE `search_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `search_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `translated_activities`
--

DROP TABLE IF EXISTS `translated_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `translated_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activity_id` int(10) unsigned NOT NULL,
  `language_id` smallint(5) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `activity_id` (`activity_id`,`language_id`)
) ENGINE=MyISAM AUTO_INCREMENT=44 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translated_activities`
--

LOCK TABLES `translated_activities` WRITE;
/*!40000 ALTER TABLE `translated_activities` DISABLE KEYS */;
INSERT INTO `translated_activities` VALUES (1,1,1,'create'),(2,2,1,'update'),(3,3,1,'delete'),(4,4,1,'trusted'),(5,5,1,'untrusted'),(6,6,1,'show'),(7,7,1,'hide'),(8,8,1,'inappropriate'),(9,9,1,'rate'),(10,10,1,'unreviewed'),(11,11,1,'add_association'),(12,12,1,'remove_association'),(13,13,1,'choose_exemplar_image'),(14,14,1,'choose_exemplar_article'),(15,15,1,'add_common_name'),(16,16,1,'remove_common_name'),(17,17,1,'preferred_classification'),(18,18,1,'curate_classifications'),(19,19,1,'split_classifications'),(20,20,1,'merge_classifications'),(21,21,1,'trust_common_name'),(22,22,1,'untrust_common_name'),(23,23,1,'inappropriate_common_name'),(24,24,1,'unreview_common_name'),(25,25,1,'unlock'),(26,26,1,'unlock_with_error'),(27,27,1,'crop'),(28,28,1,'add_editor'),(29,29,1,'bulk_add'),(30,30,1,'collect'),(31,31,1,'remove'),(32,32,1,'remove_all'),(33,33,1,'join'),(34,34,1,'leave'),(35,35,1,'add_collection'),(36,36,1,'change_description'),(37,37,1,'change_name'),(38,38,1,'change_icon'),(39,39,1,'add_manager'),(40,40,1,'set_exemplar_data'),(41,41,1,'unhide'),(42,42,1,'resource_validation'),(43,43,1,'clicked_link');
/*!40000 ALTER TABLE `translated_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `translation_logs`
--

DROP TABLE IF EXISTS `translation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `translation_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` varchar(128) DEFAULT NULL,
  `count` int(11) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `translation_logs`
--

LOCK TABLES `translation_logs` WRITE;
/*!40000 ALTER TABLE `translation_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `translation_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_activity_logs`
--

DROP TABLE IF EXISTS `user_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taxon_concept_id` int(11) DEFAULT NULL,
  `activity_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user_activity_logs`
--

LOCK TABLES `user_activity_logs` WRITE;
/*!40000 ALTER TABLE `user_activity_logs` DISABLE KEYS */;
INSERT INTO `user_activity_logs` VALUES (1,NULL,43,5,NULL,'2015-03-09 08:39:29');
/*!40000 ALTER TABLE `user_activity_logs` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-03-10  9:27:08
