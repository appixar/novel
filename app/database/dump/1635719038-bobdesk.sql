-- MySQL dump 10.13  Distrib 5.7.35, for Linux (x86_64)
--
-- Host: appixar.com    Database: bobdesk
-- ------------------------------------------------------
-- Server version	5.7.35-0ubuntu0.18.04.1

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
-- Table structure for table `bd_company`
--

DROP TABLE IF EXISTS `bd_company`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bd_company` (
  `com_id` int(11) NOT NULL AUTO_INCREMENT,
  `com_name` varchar(128) NOT NULL,
  `com_cnpj` varchar(24) NOT NULL,
  `com_discord` varchar(24) NOT NULL COMMENT 'discord channel',
  `com_status` int(11) NOT NULL,
  `com_date_insert` datetime NOT NULL,
  PRIMARY KEY (`com_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bd_company_user`
--

DROP TABLE IF EXISTS `bd_company_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bd_company_user` (
  `com_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bd_depart`
--

DROP TABLE IF EXISTS `bd_depart`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bd_depart` (
  `dep_id` int(11) NOT NULL AUTO_INCREMENT,
  `dep_name` varchar(32) NOT NULL,
  `dep_status` int(11) NOT NULL,
  `dep_date_insert` datetime NOT NULL,
  PRIMARY KEY (`dep_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bd_depart_operator`
--

DROP TABLE IF EXISTS `bd_depart_operator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bd_depart_operator` (
  `dep_id` int(11) NOT NULL,
  `op_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bd_operator`
--

DROP TABLE IF EXISTS `bd_operator`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bd_operator` (
  `op_id` int(11) NOT NULL AUTO_INCREMENT,
  `op_name` varchar(64) NOT NULL,
  `op_email` varchar(256) NOT NULL,
  `op_password` varchar(64) NOT NULL,
  `op_picture` varchar(128) NOT NULL,
  `op_status` int(11) NOT NULL,
  `op_date_insert` int(11) NOT NULL,
  PRIMARY KEY (`op_id`),
  UNIQUE KEY `op_email` (`op_email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bd_ticket`
--

DROP TABLE IF EXISTS `bd_ticket`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bd_ticket` (
  `tk_id` int(11) NOT NULL AUTO_INCREMENT,
  `tk_code` varchar(9) NOT NULL,
  `tk_subject` varchar(64) NOT NULL,
  `tk_content` varchar(512) NOT NULL,
  `tk_attach` varchar(64) NOT NULL,
  `tk_date_insert` datetime NOT NULL,
  `tk_date_finish` datetime NOT NULL,
  `tk_date_prev_finish` datetime NOT NULL COMMENT 'previsão de conclusão',
  `user_id` int(11) NOT NULL,
  `com_id` int(11) NOT NULL,
  `tk_status` int(11) NOT NULL COMMENT '-1=cancelado,1=recebido em analise,2=em andamento,3=ag int cliente,9=concluido',
  `dep_id` int(11) NOT NULL,
  `op_id` int(11) NOT NULL,
  `tk_priority` int(11) NOT NULL COMMENT '1-3',
  PRIMARY KEY (`tk_id`),
  UNIQUE KEY `tk_code` (`tk_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bd_ticket_history`
--

DROP TABLE IF EXISTS `bd_ticket_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bd_ticket_history` (
  `ht_id` int(11) NOT NULL AUTO_INCREMENT,
  `tk_id` int(11) NOT NULL,
  `ht_action` varchar(16) NOT NULL COMMENT 'received,comment,finished,waiting client,jira(inserido),data_prev_alterada',
  `ht_content` varchar(512) NOT NULL,
  `wa_send` int(11) NOT NULL COMMENT 'esta interação foi comunicada ao cliente via whats',
  `mail_send` int(11) NOT NULL COMMENT 'email para cliente',
  `disc_send` int(11) NOT NULL COMMENT 'discord para equipe',
  `op_id` int(11) NOT NULL,
  `ht_date` datetime NOT NULL,
  PRIMARY KEY (`ht_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bd_ticket_type`
--

DROP TABLE IF EXISTS `bd_ticket_type`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bd_ticket_type` (
  `tt_id` int(11) NOT NULL AUTO_INCREMENT,
  `tt_name` varchar(32) NOT NULL,
  `tt_status` int(11) NOT NULL,
  PRIMARY KEY (`tt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `bd_user`
--

DROP TABLE IF EXISTS `bd_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `bd_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'test',
  `user_name` varchar(128) NOT NULL,
  `user_cpf` varchar(11) NOT NULL,
  `user_email` varchar(256) NOT NULL,
  `user_whats` varchar(11) NOT NULL,
  `user_status` int(11) NOT NULL,
  `user_insert` datetime NOT NULL,
  `user_picture` varchar(128) NOT NULL,
  PRIMARY KEY (`user_id`,`user_whats`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2021-10-31 19:23:58
