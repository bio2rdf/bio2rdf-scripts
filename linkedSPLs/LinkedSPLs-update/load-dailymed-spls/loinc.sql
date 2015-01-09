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
-- Dumping data for table `loinc`
--

LOCK TABLES `loinc` WRITE;
/*!40000 ALTER TABLE `loinc` DISABLE KEYS */;
INSERT INTO `loinc` VALUES ('34066-1','BOXED WARNING SECTION','boxed_warning'),('34067-9','INDICATIONS & USAGE SECTION','indications_and_usage'),('34068-7','DOSAGE & ADMINISTRATION SECTION','dosage_and_administration'),('34069-5','HOW SUPPLIED SECTION','how_supplied'),('34070-3','CONTRAINDICATIONS SECTION','contraindications'),('34071-1','WARNINGS SECTION','warnings'),('34072-9','GENERAL PRECAUTIONS SECTION','general_precautions'),('34073-7','DRUG INTERACTIONS SECTION','drug_interactions'),('34074-5','DRUG & OR LABORATORY TEST INTERACTIONS SECTION','drug_and_or_laboratory_test_interactions'),('34075-2','LABORATORY TESTS SECTION','laboratory_tests'),('34076-0','INFORMATION FOR PATIENTS SECTION','information_for_patients'),('34077-8','TERATOGENIC EFFECTS SECTION','teratogenic_effects'),('34078-6','NONTERATOGENIC EFFECTS SECTION','nonteratogenic_effects'),('34079-4','LABOR & DELIVERY SECTION','labor_and_delivery'),('34080-2','NURSING MOTHERS SECTION','nursing_mothers'),('34081-0','PEDIATRIC USE SECTION','pediatric_use'),('34082-8','GERIATRIC USE SECTION','geriatric_use'),('34083-6','CARCINOGENESIS & MUTAGENESIS & IMPAIRMENT OF FERTILITY SECTION','carcinogenesis_and_mutagenesis_and_impairment_of_fertility'),('34084-4','ADVERSE REACTIONS SECTION','adverse_reactions'),('34085-1','CONTROLLED SUBSTANCE SECTION','controlled_substance'),('34086-9','ABUSE SECTION','abuse'),('34087-7','DEPENDENCE SECTION','dependence'),('34088-5','OVERDOSAGE SECTION','overdosage'),('34089-3','DESCRIPTION SECTION','description'),('34090-1','CLINICAL PHARMACOLOGY SECTION','clinical_pharmacology'),('34091-9','ANIMAL PHARMACOLOGY & OR TOXICOLOGY SECTION','animal_pharmacology_and_or_toxicology'),('34092-7','CLINICAL STUDIES SECTION','clinical_studies'),('34093-5','REFERENCES SECTION','references'),('38056-8','SUPPLEMENTAL PATIENT MATERIAL SECTION','supplemental_patient_material_section'),('42227-9','DRUG ABUSE AND DEPENDENCE SECTION','drug_abuse_and_dependence'),('42228-7','PREGNANCY SECTION','pregnancy'),('42229-5','SPL UNCLASSIFIED SECTION','spl_unclassified'),('42230-3','SPL PATIENT PACKAGE INSERT SECTION','spl_patient_package_insert'),('42231-1','SPL MEDGUIDE SECTION','spl_medguide'),('42232-9','PRECAUTIONS SECTION','precautions'),('43678-2','DOSAGE FORMS & STRENGTHS SECTION','dosage_forms_and_strengths'),('43679-0','MECHANISM OF ACTION SECTION','mechanism_of_action'),('43680-8','NONCLINICAL TOXICOLOGY SECTION','nonclinical_toxicology'),('43681-6','PHARMACODYNAMICS SECTION','pharmacodynamics'),('43682-4','PHARMACOKINETICS SECTION','pharmacokinetics'),('43683-2','RECENT MAJOR CHANGES SECTION','recent_major_changes'),('43684-0','USE IN SPECIFIC POPULATIONS SECTION','use_in_specific_populations'),('43685-7','WARNINGS AND PRECAUTIONS SECTION','warnings_and_precautions'),('44425-7','STORAGE AND HANDLING SECTION','storage_and_handling'),('48779-3','SPL INDEXING DATA ELEMENTS SECTION','spl_indexing_data_elements'),('48780-1','SPL PRODUCT DATA ELEMENTS SECTION','spl_product_data_elements'),('49489-8','MICROBIOLOGY SECTION','microbiology'),('50565-1','OTC - KEEP OUT OF REACH OF CHILDREN SECTION','otc_keep_out_of_reach_of_children'),('50566-9','OTC - STOP USE SECTION','otc_stop_use'),('50567-7','OTC - WHEN USING SECTION','otc_when_using'),('50568-5','OTC - ASK DOCTOR/PHARMACIST SECTION','otc_ask_doctor_pharmacist'),('50569-3','OTC - ASK DOCTOR SECTION','otc_ask_doctor'),('50570-1','OTC - DO NOT USE SECTION','otc_do_not_use'),('50740-0','GUARANTEED ANALYSIS OF FEED SECTION','guaranteed_analysis_of_feed'),('50741-8','SAFE HANDLING WARNING SECTION','safe_handling_warning'),('50742-6','ENVIRONMENTAL WARNING SECTION','environmental_warning'),('50743-4','FOOD SAFETY WARNING SECTION','food_safety_warning'),('50744-2','INFORMATION FOR OWNERS/CAREGIVERS SECTION','information_for_owners_caregivers'),('50745-9','VETERINARY INDICATIONS SECTION','veterinary_indications'),('51727-6','INACTIVE INGREDIENT SECTION','inactive_ingredient'),('51945-4','PACKAGE LABEL.PRINCIPAL DISPLAY PANEL','package_label_principal_display_panel'),('53412-3','RESIDUE WARNING SECTION','residue_warning'),('53413-1','OTC - QUESTIONS SECTION','otc_questions'),('53414-9','OTC - PREGNANCY OR BREAST FEEDING SECTION','otc_pregnancy_or_breast_feeding'),('54433-8','USER SAFETY WARNINGS SECTION','user_safety_warnings'),('55105-1','OTC - PURPOSE SECTION','otc_purpose'),('55106-9','OTC - ACTIVE INGREDIENT SECTION','otc_active_ingredient'),('59845-8','INSTRUCTIONS FOR USE SECTION','instructions_for_use'),('60555-0','ACCESSORIES','accessories'),('60556-8','ASSEMBLY OR INSTALLATION INSTRUCTIONS','assembly_or_installation_instructions'),('60557-6','CALIBRATION INSTRUCTIONS','calibration_instructions'),('60558-4','CLEANING, DISINFECTING, AND STERILIZATION INSTRUCTIONS','cleaning_disinfecting_and_sterilization_instructions'),('60559-2','COMPONENTS','components'),('60560-0','INTENDED USE OF THE DEVICE','intended_use_of_the_device'),('60561-8','OTHER SAFETY INFORMATION','other_safety_information'),('60562-6','ROUTE, METHOD AND FREQUENCY OF ADMINISTRATION','route_method_and_frequency_of_administration'),('60563-4','SUMMARY OF SAFETY AND EFFECTIVENESS','summary_of_safety_and_effectiveness'),('66106-6','PHARMACOGENOMICS SECTION','pharmacogenomics'),('68498-5','PATIENT MEDICATION INFORMATION SECTION','patient_medication_information'),('69718-5','STATEMENT OF IDENTITY SECTION','statement_of_identity'),('69719-3','HEALTH CLAIM SECTION','health_claim'),('69763-1','DISPOSAL AND WASTE HANDLING','disposal_and_waste_handling'),('71744-7','HEALTH CARE PROVIDER LETTER SECTION','health_care_provider_letter_section');
/*!40000 ALTER TABLE `loinc` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping events for database 'linkedSPLs'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2014-11-07 10:55:46
