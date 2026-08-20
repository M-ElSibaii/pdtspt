-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 20, 2026 at 12:42 PM
-- Server version: 10.4.24-MariaDB
-- PHP Version: 8.1.6

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES latin1 */;

--
-- Database: `pdtspt`
--

-- --------------------------------------------------------

--
-- Table structure for table `constructionobjects`
--

CREATE TABLE `constructionobjects` (
  `GUID` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referenceDocumentGUID` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `constructionObjectNameEn` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `constructionObjectNamePt` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateOfRevision` date NOT NULL,
  `dateOfVersion` date NOT NULL,
  `updated_at` date NOT NULL DEFAULT '2026-07-07',
  `created_at` date NOT NULL DEFAULT '2026-07-07',
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `versionNumber` int(11) NOT NULL,
  `revisionNumber` int(11) NOT NULL,
  `descriptionEn` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descriptionPt` text COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `dimensions`
--

CREATE TABLE `dimensions` (
  `guid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `exp_length` decimal(6,3) DEFAULT NULL,
  `exp_mass` decimal(6,3) DEFAULT NULL,
  `exp_time` decimal(6,3) DEFAULT NULL,
  `exp_electric_current` decimal(6,3) DEFAULT NULL,
  `exp_thermodynamic_temperature` decimal(6,3) DEFAULT NULL,
  `exp_amount_of_substance` decimal(6,3) DEFAULT NULL,
  `exp_luminous_intensity` decimal(6,3) DEFAULT NULL,
  `canonical` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_uri` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `entity_relationships`
--

CREATE TABLE `entity_relationships` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sourceEntityType` enum('pdt','gop','property','objecttype') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sourceGuid` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `relationType` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `targetEntityType` enum('pdt','gop','property','objecttype') COLLATE utf8mb4_unicode_ci NOT NULL,
  `targetGuid` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `targetVersionNumber` int(11) DEFAULT NULL,
  `targetRevisionNumber` int(11) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `groupofproperties`
--

CREATE TABLE `groupofproperties` (
  `Id` int(10) UNSIGNED NOT NULL,
  `GUID` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pdtId` int(10) UNSIGNED NOT NULL,
  `referenceDocumentGUID` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `gopNameEn` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gopNamePt` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `definitionEn` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `definitionPt` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dateOfCreation` date DEFAULT NULL,
  `dateofActivation` date DEFAULT NULL,
  `dateOfLastChange` date DEFAULT NULL,
  `dateOfRevision` date NOT NULL,
  `dateOfVersion` date NOT NULL,
  `versionNumber` int(11) NOT NULL,
  `revisionNumber` int(11) NOT NULL,
  `listOfReplacedProperties` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `listOfReplacingProperties` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relationToOtherDataDictionaries` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creatorsLanguage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visualRepresentation` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `countryOfUse` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `countryOfOrigin` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categoryOfGroupOfProperties` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parentGroupOfProperties` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` date NOT NULL DEFAULT '2026-07-07',
  `created_at` date NOT NULL DEFAULT '2026-07-07',
  `depreciationExplanation` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `depreciationDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `physical_quantities`
--

CREATE TABLE `physical_quantities` (
  `guid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `languageIsoCode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en.EN',
  `dimension_guid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_uri` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `productdatatemplates`
--

CREATE TABLE `productdatatemplates` (
  `Id` int(10) UNSIGNED NOT NULL,
  `GUID` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `referenceDocumentGUID` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pdtNameEn` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pdtNamePt` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dateOfRevision` date NOT NULL,
  `dateOfVersion` date NOT NULL,
  `updated_at` date NOT NULL DEFAULT '2026-07-07',
  `created_at` date NOT NULL DEFAULT '2026-07-07',
  `status` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category` enum('Construção','Material de Construção','Obras Geotécnicas','Escavação e Estabilização','Fundação e Estacas','Estruturas de Retenção de Terra','Betão','Aço','Estruturas de Madeira','Alvenaria e Tijolo','Materiais Compostos e Especializados','Paredes','Telhados','Revestimento','Isolamento','Janelas','Portas','Divisórias','Tetos','Pisos','Tinta','Revestimentos de Parede','Sanitário','Cozinha','Ferrovias','Vias Rodoviárias','Sistemas de HVAC','Sistemas Elétricos','Plumbing','Proteção Contra Incêndio','Serviços Civis e de Utilidade','Infraestrutura de TI','Obras e Paisagismo','Sistemas de Segurança e Proteção') COLLATE utf8mb4_unicode_ci DEFAULT 'Construção',
  `versionNumber` int(11) NOT NULL,
  `revisionNumber` int(11) NOT NULL,
  `descriptionEn` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descriptionPt` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `constructionObjectGUID` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `depreciationExplanation` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `depreciationDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `properties`
--

CREATE TABLE `properties` (
  `Id` int(10) UNSIGNED NOT NULL,
  `GUID` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `gopID` int(10) UNSIGNED NOT NULL,
  `pdtID` int(10) UNSIGNED NOT NULL,
  `referenceDocumentGUID` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descriptionEn` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `descriptionPt` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `visualRepresentation` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` date NOT NULL DEFAULT '2026-07-07',
  `created_at` date NOT NULL DEFAULT '2026-07-07',
  `propertyId` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `propertiesdatadictionaries`
--

CREATE TABLE `propertiesdatadictionaries` (
  `Id` int(10) UNSIGNED NOT NULL,
  `GUID` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `namePt` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `namePtSc` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nameEn` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `nameEnSc` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `definitionPt` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `definitionEn` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dateOfCreation` date DEFAULT NULL,
  `dateofActivation` date DEFAULT NULL,
  `dateOfLastChange` date DEFAULT NULL,
  `dateOfRevision` date NOT NULL,
  `dateOfVersion` date NOT NULL,
  `versionNumber` int(11) NOT NULL,
  `revisionNumber` int(11) NOT NULL,
  `listOfReplacedProperties` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `listOfReplacingProperties` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `relationToOtherDataDictionaries` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `creatorsLanguage` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `visualRepresentation` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `countryOfUse` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `countryOfOrigin` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `physicalQuantity` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dimension` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dataType` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dynamicProperty` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parametersOfTheDynamicProperty` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `units` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `namesOfDefiningValues` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `definingValues` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tolerance` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `digitalFormat` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `textFormat` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `listOfPossibleValuesInLanguageN` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `boundaryValues` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` date NOT NULL DEFAULT '2026-07-07',
  `created_at` date NOT NULL DEFAULT '2026-07-07',
  `depreciationExplanation` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `depreciationDate` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_dependencies`
--

CREATE TABLE `property_dependencies` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `sourcePropertyGuid` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dependencyKind` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expression` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `property_dependency_targets`
--

CREATE TABLE `property_dependency_targets` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `dependencyId` bigint(20) UNSIGNED NOT NULL,
  `targetPropertyGuid` varchar(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isPreferred` tinyint(1) DEFAULT NULL,
  `position` int(11) DEFAULT NULL,
  `targetVersionNumber` int(11) DEFAULT NULL,
  `targetRevisionNumber` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `referencedocuments`
--

CREATE TABLE `referencedocuments` (
  `GUID` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rdName` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` text COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `updated_at` date NOT NULL DEFAULT '2026-07-07',
  `created_at` date NOT NULL DEFAULT '2026-07-07'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `units`
--

CREATE TABLE `units` (
  `guid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `name` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `physical_quantity_guid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_uri` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scale` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `base` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `coefficient` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `offset` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `constructionobjects`
--
ALTER TABLE `constructionobjects`
  ADD PRIMARY KEY (`GUID`),
  ADD KEY `constructionobjects_referencedocumentguid_foreign` (`referenceDocumentGUID`);

--
-- Indexes for table `dimensions`
--
ALTER TABLE `dimensions`
  ADD PRIMARY KEY (`guid`),
  ADD UNIQUE KEY `dimensions_canonical_unique` (`canonical`);

--
-- Indexes for table `entity_relationships`
--
ALTER TABLE `entity_relationships`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `entity_rel_unique_edge` (`sourceEntityType`,`sourceGuid`,`relationType`,`targetEntityType`,`targetGuid`),
  ADD KEY `entity_rel_source_idx` (`sourceEntityType`,`sourceGuid`),
  ADD KEY `entity_rel_target_idx` (`targetEntityType`,`targetGuid`);

--
-- Indexes for table `groupofproperties`
--
ALTER TABLE `groupofproperties`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `groupofproperties_pdtid_index` (`pdtId`),
  ADD KEY `groupofproperties_referencedocumentguid_foreign` (`referenceDocumentGUID`),
  ADD KEY `gop_lineage_idx` (`GUID`,`versionNumber`,`revisionNumber`);

--
-- Indexes for table `physical_quantities`
--
ALTER TABLE `physical_quantities`
  ADD PRIMARY KEY (`guid`),
  ADD UNIQUE KEY `physical_quantities_name_languageisocode_unique` (`name`,`languageIsoCode`),
  ADD KEY `physical_quantities_dimension_guid_foreign` (`dimension_guid`);

--
-- Indexes for table `productdatatemplates`
--
ALTER TABLE `productdatatemplates`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `productdatatemplates_referencedocumentguid_foreign` (`referenceDocumentGUID`),
  ADD KEY `productdatatemplates_constructionobjectguid_foreign` (`constructionObjectGUID`),
  ADD KEY `pdt_lineage_idx` (`GUID`,`versionNumber`,`revisionNumber`);

--
-- Indexes for table `properties`
--
ALTER TABLE `properties`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `properties_guid_foreign` (`GUID`),
  ADD KEY `properties_gopid_foreign` (`gopID`),
  ADD KEY `properties_pdtid_foreign` (`pdtID`),
  ADD KEY `properties_referencedocumentguid_foreign` (`referenceDocumentGUID`),
  ADD KEY `properties_propertyid_foreign` (`propertyId`);

--
-- Indexes for table `propertiesdatadictionaries`
--
ALTER TABLE `propertiesdatadictionaries`
  ADD PRIMARY KEY (`Id`),
  ADD KEY `propertiesdatadictionaries_guid_index` (`GUID`),
  ADD KEY `dict_lineage_idx` (`GUID`,`versionNumber`,`revisionNumber`);

--
-- Indexes for table `property_dependencies`
--
ALTER TABLE `property_dependencies`
  ADD PRIMARY KEY (`id`),
  ADD KEY `propdep_source_idx` (`sourcePropertyGuid`);

--
-- Indexes for table `property_dependency_targets`
--
ALTER TABLE `property_dependency_targets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `propdeptgt_dep_idx` (`dependencyId`),
  ADD KEY `propdeptgt_target_idx` (`targetPropertyGuid`);

--
-- Indexes for table `referencedocuments`
--
ALTER TABLE `referencedocuments`
  ADD PRIMARY KEY (`GUID`);

--
-- Indexes for table `units`
--
ALTER TABLE `units`
  ADD PRIMARY KEY (`guid`),
  ADD UNIQUE KEY `units_code_unique` (`code`),
  ADD KEY `units_physical_quantity_guid_foreign` (`physical_quantity_guid`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `entity_relationships`
--
ALTER TABLE `entity_relationships`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `groupofproperties`
--
ALTER TABLE `groupofproperties`
  MODIFY `Id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `productdatatemplates`
--
ALTER TABLE `productdatatemplates`
  MODIFY `Id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `properties`
--
ALTER TABLE `properties`
  MODIFY `Id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `propertiesdatadictionaries`
--
ALTER TABLE `propertiesdatadictionaries`
  MODIFY `Id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_dependencies`
--
ALTER TABLE `property_dependencies`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `property_dependency_targets`
--
ALTER TABLE `property_dependency_targets`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `constructionobjects`
--
ALTER TABLE `constructionobjects`
  ADD CONSTRAINT `constructionobjects_referencedocumentguid_foreign` FOREIGN KEY (`referenceDocumentGUID`) REFERENCES `referencedocuments` (`GUID`);

--
-- Constraints for table `groupofproperties`
--
ALTER TABLE `groupofproperties`
  ADD CONSTRAINT `groupofproperties_pdtid_foreign` FOREIGN KEY (`pdtId`) REFERENCES `productdatatemplates` (`Id`),
  ADD CONSTRAINT `groupofproperties_referencedocumentguid_foreign` FOREIGN KEY (`referenceDocumentGUID`) REFERENCES `referencedocuments` (`GUID`);

--
-- Constraints for table `physical_quantities`
--
ALTER TABLE `physical_quantities`
  ADD CONSTRAINT `physical_quantities_dimension_guid_foreign` FOREIGN KEY (`dimension_guid`) REFERENCES `dimensions` (`guid`) ON DELETE SET NULL;

--
-- Constraints for table `productdatatemplates`
--
ALTER TABLE `productdatatemplates`
  ADD CONSTRAINT `productdatatemplates_constructionobjectguid_foreign` FOREIGN KEY (`constructionObjectGUID`) REFERENCES `constructionobjects` (`GUID`),
  ADD CONSTRAINT `productdatatemplates_referencedocumentguid_foreign` FOREIGN KEY (`referenceDocumentGUID`) REFERENCES `referencedocuments` (`GUID`);

--
-- Constraints for table `properties`
--
ALTER TABLE `properties`
  ADD CONSTRAINT `properties_gopid_foreign` FOREIGN KEY (`gopID`) REFERENCES `groupofproperties` (`Id`),
  ADD CONSTRAINT `properties_guid_foreign` FOREIGN KEY (`GUID`) REFERENCES `propertiesdatadictionaries` (`GUID`),
  ADD CONSTRAINT `properties_pdtid_foreign` FOREIGN KEY (`pdtID`) REFERENCES `productdatatemplates` (`Id`),
  ADD CONSTRAINT `properties_propertyid_foreign` FOREIGN KEY (`propertyId`) REFERENCES `propertiesdatadictionaries` (`Id`),
  ADD CONSTRAINT `properties_referencedocumentguid_foreign` FOREIGN KEY (`referenceDocumentGUID`) REFERENCES `referencedocuments` (`GUID`);

--
-- Constraints for table `property_dependency_targets`
--
ALTER TABLE `property_dependency_targets`
  ADD CONSTRAINT `property_dependency_targets_dependencyid_foreign` FOREIGN KEY (`dependencyId`) REFERENCES `property_dependencies` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `units`
--
ALTER TABLE `units`
  ADD CONSTRAINT `units_physical_quantity_guid_foreign` FOREIGN KEY (`physical_quantity_guid`) REFERENCES `physical_quantities` (`guid`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
