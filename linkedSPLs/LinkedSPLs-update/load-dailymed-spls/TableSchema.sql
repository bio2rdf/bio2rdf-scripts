CREATE DATABASE  IF NOT EXISTS `linkedSPLs` /*!40100 DEFAULT CHARACTER SET latin1 */;
USE `linkedSPLs`;
-- MySQL dump 10.13  Distrib 5.5.40, for debian-linux-gnu (x86_64)
--
-- Host: 127.0.0.1    Database: linkedSPLs
-- ------------------------------------------------------
-- Server version	5.5.40-0ubuntu0.14.04.1

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
-- Table structure for table `ChEBI_DRUGBANK_BIO2RDF`
--

DROP TABLE IF EXISTS `ChEBI_DRUGBANK_BIO2RDF`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ChEBI_DRUGBANK_BIO2RDF` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ChEBI_OBO` varchar(200) NOT NULL,
  `ChEBI_BIO2RDF` varchar(200) NOT NULL,
  `DRUGBANK_CA` varchar(200) NOT NULL,
  `DRUGBANK_BIO2RDF` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FDAPharmgxTable`
--

DROP TABLE IF EXISTS `FDAPharmgxTable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FDAPharmgxTable` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `activeMoiety` varchar(200) NOT NULL, 
  `therapeuticApplication` varchar(500) NOT NULL,       
  `biomarker` varchar(50) NOT NULL,
  `setId` varchar(200) NOT NULL,
  `SPLSection` varchar(500) NOT NULL,   
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FDAPharmgxTableToOntologyMap`
--

DROP TABLE IF EXISTS `FDAPharmgxTableToOntologyMap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;

CREATE TABLE `FDAPharmgxTableToOntologyMap` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `FDAReferencedSubgroup` varchar(200) NOT NULL,        
  `HGNCGeneSymbol` varchar(100),        
  `Synonymns` varchar(500) ,
  `AlleleVariant` varchar(100),
  `Pharmgkb` varchar(100),      
  `URI` varchar(200),   
  `Ontology` varchar(200),
  `CuratorComments` varchar(500),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

--
-- Table structure for table `FDAPreferredSubstanceToRxNORM`
--

DROP TABLE IF EXISTS `FDAPreferredSubstanceToRxNORM`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FDAPreferredSubstanceToRxNORM` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `PreferredSubstance` varchar(200) NOT NULL,
  `RxNORM` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=32768 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FDAPreferredSubstanceToUNII`
--

DROP TABLE IF EXISTS `FDAPreferredSubstanceToUNII`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FDAPreferredSubstanceToUNII` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `PreferredSubstance` varchar(200) NOT NULL,
  `UNII` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=524281 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FDA_EPC_Table`
--

DROP TABLE IF EXISTS `FDA_EPC_Table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FDA_EPC_Table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setId` varchar(200) NOT NULL,
  `UNII` varchar(50) NOT NULL,
  `NUI` varchar(50) NOT NULL,
  `PreferredNameAndRole` varchar(300) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16384 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FDA_SUBSTANCE_TO_DRUGBANK_BIO2RDF`
--

DROP TABLE IF EXISTS `FDA_SUBSTANCE_TO_DRUGBANK_BIO2RDF`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FDA_SUBSTANCE_TO_DRUGBANK_BIO2RDF` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `PreferredSubstance` varchar(200) NOT NULL,
  `DRUGBANK_CA` varchar(200) NOT NULL,
  `DRUGBANK_BIO2RDF` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4096 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `FDA_UNII_to_ChEBI`
--

DROP TABLE IF EXISTS `FDA_UNII_to_ChEBI`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `FDA_UNII_to_ChEBI` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `PreferredSubstance` varchar(200) NOT NULL,
  `ChEBI` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8192 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `OMOP_RXCUI`
--
DROP TABLE IF EXISTS `OMOP_RXCUI`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `linkedSPLs`.`OMOP_RXCUI` (
  `Id` INT NOT NULL AUTO_INCREMENT,
  `OMOPConceptId` VARCHAR(20) NOT NULL,
  `RxCUI` VARCHAR(20) NOT NULL,
  PRIMARY KEY (`Id`)
) ENGINE=InnoDB AUTO_INCREMENT=4096 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;;

--
-- Table structure for table `RXNATOMARCHIVE`
--

DROP TABLE IF EXISTS `RXNATOMARCHIVE`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RXNATOMARCHIVE` (
  `RXAUI` varchar(8) NOT NULL,
  `AUI` varchar(10) DEFAULT NULL,
  `STR` varchar(4000) NOT NULL,
  `ARCHIVE_TIMESTAMP` varchar(280) NOT NULL,
  `CREATED_TIMESTAMP` varchar(280) NOT NULL,
  `UPDATED_TIMESTAMP` varchar(280) NOT NULL,
  `CODE` varchar(50) DEFAULT NULL,
  `IS_BRAND` varchar(1) DEFAULT NULL,
  `LAT` varchar(3) DEFAULT NULL,
  `LAST_RELEASED` varchar(30) DEFAULT NULL,
  `SAUI` varchar(50) DEFAULT NULL,
  `VSAB` varchar(40) DEFAULT NULL,
  `RXCUI` varchar(8) DEFAULT NULL,
  `SAB` varchar(20) DEFAULT NULL,
  `TTY` varchar(20) DEFAULT NULL,
  `MERGED_TO_RXCUI` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RXNCONSO`
--

DROP TABLE IF EXISTS `RXNCONSO`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RXNCONSO` (
  `RXCUI` varchar(8) NOT NULL,
  `LAT` varchar(3) NOT NULL DEFAULT 'ENG',
  `TS` varchar(1) DEFAULT NULL,
  `LUI` varchar(8) DEFAULT NULL,
  `STT` varchar(3) DEFAULT NULL,
  `SUI` varchar(8) DEFAULT NULL,
  `ISPREF` varchar(1) DEFAULT NULL,
  `RXAUI` varchar(8) NOT NULL,
  `SAUI` varchar(50) DEFAULT NULL,
  `SCUI` varchar(50) DEFAULT NULL,
  `SDUI` varchar(50) DEFAULT NULL,
  `SAB` varchar(20) NOT NULL,
  `TTY` varchar(20) NOT NULL,
  `CODE` varchar(50) NOT NULL,
  `STR` varchar(3000) NOT NULL,
  `SRL` varchar(10) DEFAULT NULL,
  `SUPPRESS` varchar(1) DEFAULT NULL,
  `CVF` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RXNCUI`
--

DROP TABLE IF EXISTS `RXNCUI`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RXNCUI` (
  `cui1` varchar(8) DEFAULT NULL,
  `ver_start` varchar(40) DEFAULT NULL,
  `ver_end` varchar(40) DEFAULT NULL,
  `cardinality` varchar(8) DEFAULT NULL,
  `cui2` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RXNCUICHANGES`
--

DROP TABLE IF EXISTS `RXNCUICHANGES`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RXNCUICHANGES` (
  `RXAUI` varchar(8) DEFAULT NULL,
  `CODE` varchar(50) DEFAULT NULL,
  `SAB` varchar(20) DEFAULT NULL,
  `TTY` varchar(20) DEFAULT NULL,
  `STR` varchar(3000) DEFAULT NULL,
  `OLD_RXCUI` varchar(8) NOT NULL,
  `NEW_RXCUI` varchar(8) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RXNDOC`
--

DROP TABLE IF EXISTS `RXNDOC`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RXNDOC` (
  `DOCKEY` varchar(50) NOT NULL,
  `VALUE` varchar(1000) DEFAULT NULL,
  `TYPE` varchar(50) NOT NULL,
  `EXPL` varchar(1000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RXNORM_NDFRT_INGRED_Table`
--

DROP TABLE IF EXISTS `RXNORM_NDFRT_INGRED_Table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RXNORM_NDFRT_INGRED_Table` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `RxNORM` varchar(200) NOT NULL,
  `NUI` varchar(200) NOT NULL,
  `NDFRT_LABEL` varchar(200) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16384 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RXNREL`
--

DROP TABLE IF EXISTS `RXNREL`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RXNREL` (
  `RXCUI1` varchar(8) DEFAULT NULL,
  `RXAUI1` varchar(8) DEFAULT NULL,
  `STYPE1` varchar(50) DEFAULT NULL,
  `REL` varchar(4) DEFAULT NULL,
  `RXCUI2` varchar(8) DEFAULT NULL,
  `RXAUI2` varchar(8) DEFAULT NULL,
  `STYPE2` varchar(50) DEFAULT NULL,
  `RELA` varchar(100) DEFAULT NULL,
  `RUI` varchar(10) DEFAULT NULL,
  `SRUI` varchar(50) DEFAULT NULL,
  `SAB` varchar(20) NOT NULL,
  `SL` varchar(1000) DEFAULT NULL,
  `DIR` varchar(1) DEFAULT NULL,
  `RG` varchar(10) DEFAULT NULL,
  `SUPPRESS` varchar(1) DEFAULT NULL,
  `CVF` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RXNSAB`
--

DROP TABLE IF EXISTS `RXNSAB`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RXNSAB` (
  `VCUI` varchar(8) DEFAULT NULL,
  `RCUI` varchar(8) DEFAULT NULL,
  `VSAB` varchar(40) DEFAULT NULL,
  `RSAB` varchar(20) NOT NULL,
  `SON` varchar(3000) DEFAULT NULL,
  `SF` varchar(20) DEFAULT NULL,
  `SVER` varchar(20) DEFAULT NULL,
  `VSTART` varchar(10) DEFAULT NULL,
  `VEND` varchar(10) DEFAULT NULL,
  `IMETA` varchar(10) DEFAULT NULL,
  `RMETA` varchar(10) DEFAULT NULL,
  `SLC` varchar(1000) DEFAULT NULL,
  `SCC` varchar(1000) DEFAULT NULL,
  `SRL` int(11) DEFAULT NULL,
  `TFR` int(11) DEFAULT NULL,
  `CFR` int(11) DEFAULT NULL,
  `CXTY` varchar(50) DEFAULT NULL,
  `TTYL` varchar(300) DEFAULT NULL,
  `ATNL` varchar(1000) DEFAULT NULL,
  `LAT` varchar(3) DEFAULT NULL,
  `CENC` varchar(20) DEFAULT NULL,
  `CURVER` varchar(1) DEFAULT NULL,
  `SABIN` varchar(1) DEFAULT NULL,
  `SSN` varchar(3000) DEFAULT NULL,
  `SCIT` varchar(4000) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RXNSAT`
--

DROP TABLE IF EXISTS `RXNSAT`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RXNSAT` (
  `RXCUI` varchar(8) DEFAULT NULL,
  `LUI` varchar(8) DEFAULT NULL,
  `SUI` varchar(8) DEFAULT NULL,
  `RXAUI` varchar(8) DEFAULT NULL,
  `STYPE` varchar(50) DEFAULT NULL,
  `CODE` varchar(50) DEFAULT NULL,
  `ATUI` varchar(11) DEFAULT NULL,
  `SATUI` varchar(50) DEFAULT NULL,
  `ATN` varchar(1000) NOT NULL,
  `SAB` varchar(20) NOT NULL,
  `ATV` varchar(4000) DEFAULT NULL,
  `SUPPRESS` varchar(1) DEFAULT NULL,
  `CVF` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `RXNSTY`
--

DROP TABLE IF EXISTS `RXNSTY`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `RXNSTY` (
  `RXCUI` varchar(8) NOT NULL,
  `TUI` varchar(4) DEFAULT NULL,
  `STN` varchar(100) DEFAULT NULL,
  `STY` varchar(50) DEFAULT NULL,
  `ATUI` varchar(11) DEFAULT NULL,
  `CVF` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `SPLSetIDToRxNORM`
--

DROP TABLE IF EXISTS `SPLSetIDToRxNORM`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `SPLSetIDToRxNORM` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setId` varchar(200) NOT NULL,
  `RxCUI` varchar(50) NOT NULL,
  `RxClinicalDrug` varchar(1000) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=196606 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `abuse`
--

DROP TABLE IF EXISTS `abuse`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `abuse` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `abuse_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1316 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `accessories`
--

DROP TABLE IF EXISTS `accessories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `accessories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `accessories_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `active_moiety`
--

DROP TABLE IF EXISTS `active_moiety`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `active_moiety` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(300) CHARACTER SET latin1 NOT NULL,
  `UNII` varchar(50) CHARACTER SET latin1 NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=117117 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `adverse_reactions`
--

DROP TABLE IF EXISTS `adverse_reactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `adverse_reactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `adverse_reactions_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=25246 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `animal_pharmacology_and_or_toxicology`
--

DROP TABLE IF EXISTS `animal_pharmacology_and_or_toxicology`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `animal_pharmacology_and_or_toxicology` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `animal_pharmacology_and_or_toxicology_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2953 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `assembly_or_installation_instructions`
--

DROP TABLE IF EXISTS `assembly_or_installation_instructions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assembly_or_installation_instructions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `assembly_or_installation_instructions_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `boxed_warning`
--

DROP TABLE IF EXISTS `boxed_warning`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `boxed_warning` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `boxed_warning_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8890 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `calibration_instructions`
--

DROP TABLE IF EXISTS `calibration_instructions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calibration_instructions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `calibration_instructions_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `carcinogenesis_and_mutagenesis_and_impairment_of_fertility`
--

DROP TABLE IF EXISTS `carcinogenesis_and_mutagenesis_and_impairment_of_fertility`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `carcinogenesis_and_mutagenesis_and_impairment_of_fertility` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `carci_and_mutag_and_impair_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16439 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cleaning_disinfecting_and_sterilization_instructions`
--

DROP TABLE IF EXISTS `cleaning_disinfecting_and_sterilization_instructions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cleaning_disinfecting_and_sterilization_instructions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `cleaning_disinfecting_and_sterilization_instructions_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinical_pharmacology`
--

DROP TABLE IF EXISTS `clinical_pharmacology`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinical_pharmacology` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `clinical_pharmacology_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24053 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `clinical_studies`
--

DROP TABLE IF EXISTS `clinical_studies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `clinical_studies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `clinical_studies_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10085 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `components`
--

DROP TABLE IF EXISTS `components`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `components` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `components_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `contraindications`
--

DROP TABLE IF EXISTS `contraindications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contraindications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `contraindications_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24413 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `controlled_substance`
--

DROP TABLE IF EXISTS `controlled_substance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `controlled_substance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `controlled_substance_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1711 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dependence`
--

DROP TABLE IF EXISTS `dependence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dependence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `dependence_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1404 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `description`
--

DROP TABLE IF EXISTS `description`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `description` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `description_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26059 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `disposal_and_waste_handling`
--

DROP TABLE IF EXISTS `disposal_and_waste_handling`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `disposal_and_waste_handling` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `disposal_and_waste_handling_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dosage_and_administration`
--

DROP TABLE IF EXISTS `dosage_and_administration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dosage_and_administration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `dosage_and_administration_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=52967 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `dosage_forms_and_strengths`
--

DROP TABLE IF EXISTS `dosage_forms_and_strengths`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `dosage_forms_and_strengths` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `dosage_forms_and_strengths_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5955 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `drug_abuse_and_dependence`
--

DROP TABLE IF EXISTS `drug_abuse_and_dependence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drug_abuse_and_dependence` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `drug_abuse_and_dependence_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5534 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `drug_and_or_laboratory_test_interactions`
--

DROP TABLE IF EXISTS `drug_and_or_laboratory_test_interactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drug_and_or_laboratory_test_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `drug_and_or_laboratory_test_interactions_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3517 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `drug_interactions`
--

DROP TABLE IF EXISTS `drug_interactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `drug_interactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `drug_interactions_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17004 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `environmental_warning`
--

DROP TABLE IF EXISTS `environmental_warning`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `environmental_warning` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `environmental_warning_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `food_safety_warning`
--

DROP TABLE IF EXISTS `food_safety_warning`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `food_safety_warning` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `food_safety_warning_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `general_precautions`
--

DROP TABLE IF EXISTS `general_precautions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `general_precautions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `general_precautions_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10074 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geriatric_use`
--

DROP TABLE IF EXISTS `geriatric_use`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geriatric_use` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `geriatric_use_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=14261 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `guaranteed_analysis_of_feed`
--

DROP TABLE IF EXISTS `guaranteed_analysis_of_feed`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guaranteed_analysis_of_feed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `guaranteed_analysis_of_feed_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `health_care_provider_letter_section`
--

DROP TABLE IF EXISTS `health_care_provider_letter_section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `health_care_provider_letter_section` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `health_care_provider_letter_section_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `health_claim`
--

DROP TABLE IF EXISTS `health_claim`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `health_claim` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `health_claim_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `how_supplied`
--

DROP TABLE IF EXISTS `how_supplied`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `how_supplied` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `how_supplied_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=24234 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `inactive_ingredient`
--

DROP TABLE IF EXISTS `inactive_ingredient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inactive_ingredient` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `inactive_ingredient_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30317 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `indications_and_usage`
--

DROP TABLE IF EXISTS `indications_and_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `indications_and_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `indications_and_usage_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=53373 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `information_for_owners_caregivers`
--

DROP TABLE IF EXISTS `information_for_owners_caregivers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `information_for_owners_caregivers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `information_for_owners_caregivers_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=37 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `information_for_patients`
--

DROP TABLE IF EXISTS `information_for_patients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `information_for_patients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `information_for_patients_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16738 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `instructions_for_use`
--

DROP TABLE IF EXISTS `instructions_for_use`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `instructions_for_use` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `instructions_for_use_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=578 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `intended_use_of_the_device`
--

DROP TABLE IF EXISTS `intended_use_of_the_device`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `intended_use_of_the_device` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `intended_use_of_the_device_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `labor_and_delivery`
--

DROP TABLE IF EXISTS `labor_and_delivery`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `labor_and_delivery` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `labor_and_delivery_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5403 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `laboratory_tests`
--

DROP TABLE IF EXISTS `laboratory_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `laboratory_tests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `laboratory_tests_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6248 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `loinc`
--

DROP TABLE IF EXISTS `loinc`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `loinc` (
  `loinc` varchar(7) NOT NULL,
  `dailymed_name` varchar(300) NOT NULL,
  `table_name` varchar(300) NOT NULL,
  PRIMARY KEY (`loinc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mechanism_of_action`
--

DROP TABLE IF EXISTS `mechanism_of_action`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mechanism_of_action` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `mechanism_of_action_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7444 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `microbiology`
--

DROP TABLE IF EXISTS `microbiology`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `microbiology` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `microbiology_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1480 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nonclinical_toxicology`
--

DROP TABLE IF EXISTS `nonclinical_toxicology`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nonclinical_toxicology` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `nonclinical_toxicology_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5863 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nonteratogenic_effects`
--

DROP TABLE IF EXISTS `nonteratogenic_effects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nonteratogenic_effects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `nonteratogenic_effects_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2805 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `nursing_mothers`
--

DROP TABLE IF EXISTS `nursing_mothers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `nursing_mothers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `nursing_mothers_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17684 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `otc_active_ingredient`
--

DROP TABLE IF EXISTS `otc_active_ingredient`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otc_active_ingredient` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `otc_active_ingredient_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=30329 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `otc_ask_doctor`
--

DROP TABLE IF EXISTS `otc_ask_doctor`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otc_ask_doctor` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `otc_ask_doctor_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12858 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `otc_ask_doctor_pharmacist`
--

DROP TABLE IF EXISTS `otc_ask_doctor_pharmacist`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otc_ask_doctor_pharmacist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `otc_ask_doctor_pharmacist_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7573 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `otc_do_not_use`
--

DROP TABLE IF EXISTS `otc_do_not_use`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otc_do_not_use` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `otc_do_not_use_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15100 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `otc_keep_out_of_reach_of_children`
--

DROP TABLE IF EXISTS `otc_keep_out_of_reach_of_children`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otc_keep_out_of_reach_of_children` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `otc_keep_out_of_reach_of_children_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28981 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `otc_pregnancy_or_breast_feeding`
--

DROP TABLE IF EXISTS `otc_pregnancy_or_breast_feeding`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otc_pregnancy_or_breast_feeding` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `otc_pregnancy_or_breast_feeding_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9568 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `otc_purpose`
--

DROP TABLE IF EXISTS `otc_purpose`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otc_purpose` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `otc_purpose_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=28830 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `otc_questions`
--

DROP TABLE IF EXISTS `otc_questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otc_questions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `otc_questions_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=15382 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `otc_stop_use`
--

DROP TABLE IF EXISTS `otc_stop_use`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otc_stop_use` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `otc_stop_use_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20323 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `otc_when_using`
--

DROP TABLE IF EXISTS `otc_when_using`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `otc_when_using` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `otc_when_using_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16697 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `other_safety_information`
--

DROP TABLE IF EXISTS `other_safety_information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `other_safety_information` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `other_safety_information_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2069 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `overdosage`
--

DROP TABLE IF EXISTS `overdosage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `overdosage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `overdosage_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=22363 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `package_label_principal_display_panel`
--

DROP TABLE IF EXISTS `package_label_principal_display_panel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `package_label_principal_display_panel` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `package_label_principal_display_panel_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56165 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `patient_medication_information`
--

DROP TABLE IF EXISTS `patient_medication_information`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `patient_medication_information` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `patient_medication_information_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pediatric_use`
--

DROP TABLE IF EXISTS `pediatric_use`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pediatric_use` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `pediatric_use_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17728 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pharmacodynamics`
--

DROP TABLE IF EXISTS `pharmacodynamics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pharmacodynamics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `pharmacodynamics_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5993 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pharmacogenomics`
--

DROP TABLE IF EXISTS `pharmacogenomics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pharmacogenomics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `pharmacogenomics_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pharmacokinetics`
--

DROP TABLE IF EXISTS `pharmacokinetics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pharmacokinetics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `pharmacokinetics_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=11023 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `precautions`
--

DROP TABLE IF EXISTS `precautions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `precautions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `precautions_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18504 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pregnancy`
--

DROP TABLE IF EXISTS `pregnancy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pregnancy` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `pregnancy_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17683 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `recent_major_changes`
--

DROP TABLE IF EXISTS `recent_major_changes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recent_major_changes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `recent_major_changes_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3347 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `references`
--

DROP TABLE IF EXISTS `references`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `references` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `references_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4610 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `residue_warning`
--

DROP TABLE IF EXISTS `residue_warning`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `residue_warning` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `residue_warning_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `route_method_and_frequency_of_administration`
--

DROP TABLE IF EXISTS `route_method_and_frequency_of_administration`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `route_method_and_frequency_of_administration` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `route_method_and_frequency_of_administration_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `safe_handling_warning`
--

DROP TABLE IF EXISTS `safe_handling_warning`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `safe_handling_warning` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `safe_handling_warning_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `side_effects`
--

DROP TABLE IF EXISTS `side_effects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `side_effects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `side_effects_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spl_has_active_moiety`
--

DROP TABLE IF EXISTS `spl_has_active_moiety`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spl_has_active_moiety` (
  `spl` int(11) NOT NULL,
  `active_moiety` int(11) NOT NULL,
  PRIMARY KEY (`spl`,`active_moiety`),
  KEY `spl_id` (`spl`),
  KEY `active_moiety_id` (`active_moiety`),
  CONSTRAINT `spl_has_active_moiety_ibfk_1` FOREIGN KEY (`spl`) REFERENCES `structuredProductLabelMetadata` (`id`),
  CONSTRAINT `spl_has_active_moiety_ibfk_2` FOREIGN KEY (`active_moiety`) REFERENCES `active_moiety` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spl_indexing_data_elements`
--

DROP TABLE IF EXISTS `spl_indexing_data_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spl_indexing_data_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `spl_indexing_data_elements_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spl_medguide`
--

DROP TABLE IF EXISTS `spl_medguide`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spl_medguide` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `spl_medguide_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4582 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spl_patient_package_insert`
--

DROP TABLE IF EXISTS `spl_patient_package_insert`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spl_patient_package_insert` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `spl_patient_package_insert_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2945 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spl_product_data_elements`
--

DROP TABLE IF EXISTS `spl_product_data_elements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spl_product_data_elements` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `spl_product_data_elements_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=56239 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `spl_unclassified`
--

DROP TABLE IF EXISTS `spl_unclassified`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `spl_unclassified` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `spl_unclassified_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=33185 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Temporary table structure for view `spl_view`
--

DROP TABLE IF EXISTS `spl_view`;
/*!50001 DROP VIEW IF EXISTS `spl_view`*/;
SET @saved_cs_client     = @@character_set_client;
SET character_set_client = utf8;
/*!50001 CREATE TABLE `spl_view` (
  `id` tinyint NOT NULL,
  `setId` tinyint NOT NULL,
  `versionNumber` tinyint NOT NULL,
  `fullName` tinyint NOT NULL,
  `routeOfAdministration` tinyint NOT NULL,
  `genericMedicine` tinyint NOT NULL,
  `representedOrganization` tinyint NOT NULL,
  `effectiveTime` tinyint NOT NULL,
  `active_moieties` tinyint NOT NULL
) ENGINE=MyISAM */;
SET character_set_client = @saved_cs_client;

--
-- Table structure for table `statement_of_identity`
--

DROP TABLE IF EXISTS `statement_of_identity`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `statement_of_identity` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `statement_of_identity_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `storage_and_handling`
--

DROP TABLE IF EXISTS `storage_and_handling`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `storage_and_handling` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `storage_and_handling_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=20591 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `structuredProductLabelMetadata`
--

DROP TABLE IF EXISTS `structuredProductLabelMetadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `structuredProductLabelMetadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setId` varchar(100) NOT NULL,
  `versionNumber` varchar(10) NOT NULL,
  `fullName` varchar(500) DEFAULT NULL,
  `routeOfAdministration` varchar(500) DEFAULT NULL,
  `drugbank_id` varchar(15) DEFAULT NULL,
  `genericMedicine` varchar(500) DEFAULT NULL,
  `representedOrganization` varchar(500) DEFAULT NULL,
  `effectiveTime` date DEFAULT NULL,
  `filename` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setId` (`setId`),
  KEY `drugbank_id` (`drugbank_id`)
) ENGINE=InnoDB AUTO_INCREMENT=56810 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `summary_of_safety_and_effectiveness`
--

DROP TABLE IF EXISTS `summary_of_safety_and_effectiveness`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `summary_of_safety_and_effectiveness` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `summary_of_safety_and_effectiveness_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `supplemental_patient_material`
--

DROP TABLE IF EXISTS `supplemental_patient_material`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplemental_patient_material` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `supplemental_patient_material_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `supplemental_patient_material_section`
--

DROP TABLE IF EXISTS `supplemental_patient_material_section`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `supplemental_patient_material_section` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `supplemental_patient_material_section_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=83 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `teratogenic_effects`
--

DROP TABLE IF EXISTS `teratogenic_effects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `teratogenic_effects` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `teratogenic_effects_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7343 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `use_in_specific_populations`
--

DROP TABLE IF EXISTS `use_in_specific_populations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `use_in_specific_populations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `use_in_specific_populations_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6441 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_safety_warnings`
--

DROP TABLE IF EXISTS `user_safety_warnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_safety_warnings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `user_safety_warnings_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=72 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `veterinary_indications`
--

DROP TABLE IF EXISTS `veterinary_indications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `veterinary_indications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `veterinary_indications_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `warnings`
--

DROP TABLE IF EXISTS `warnings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warnings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `spl_id` (`splId`),
  CONSTRAINT `warnings_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=46752 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `warnings_and_precautions`
--

DROP TABLE IF EXISTS `warnings_and_precautions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `warnings_and_precautions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `splId` int(11) NOT NULL,
  `field` longtext NOT NULL,
  PRIMARY KEY (`id`),
  KEY `splId` (`splId`),
  CONSTRAINT `warnings_and_precautions_ibfk_1` FOREIGN KEY (`splId`) REFERENCES `structuredProductLabelMetadata` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6933 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `DrOn_ChEBI_RXCUI`
--
DROP TABLE IF EXISTS `DrOn_ChEBI_RXCUI`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `DrOn_ChEBI_RXCUI` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dron_id` varchar(30) NOT NULL,
  `ChEBI` varchar(50) NOT NULL,
  `rxcui` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
)  ENGINE=InnoDB AUTO_INCREMENT=6933 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;



--
-- Final view structure for view `spl_view`
--

/*!50001 DROP TABLE IF EXISTS `spl_view`*/;
/*!50001 DROP VIEW IF EXISTS `spl_view`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8 */;
/*!50001 SET character_set_results     = utf8 */;
/*!50001 SET collation_connection      = utf8_general_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `spl_view` AS select `spl`.`id` AS `id`,`spl`.`setId` AS `setId`,`spl`.`versionNumber` AS `versionNumber`,`spl`.`fullName` AS `fullName`,`spl`.`routeOfAdministration` AS `routeOfAdministration`,`spl`.`genericMedicine` AS `genericMedicine`,`spl`.`representedOrganization` AS `representedOrganization`,`spl`.`effectiveTime` AS `effectiveTime`,group_concat(`am`.`name` separator ',') AS `active_moieties` from ((`structuredProductLabelMetadata` `spl` join `spl_has_active_moiety` `splam` on((`spl`.`id` = `splam`.`spl`))) join `active_moiety` `am` on((`splam`.`active_moiety` = `am`.`id`))) group by `spl`.`id` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-11-05 14:48:02
