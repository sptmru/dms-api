-- Create syntax for TABLE 'person'
CREATE TABLE `person` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `secondName` varchar(50) DEFAULT NULL,
  `lastName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `name_lat` varchar(50) DEFAULT NULL,
  `secondName_lat` varchar(50) DEFAULT NULL,
  `lastName_lat` varchar(50) DEFAULT NULL,
  `dateOfBirth` date DEFAULT NULL,
  `countryOfBirth` varchar(100) DEFAULT NULL,
  `placeOfBirth` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(200) DEFAULT NULL,
  `registrationDate` date DEFAULT NULL,
  `sex` varchar(1) DEFAULT NULL,
  `citizenship` varchar(100) DEFAULT NULL,
  `bsoNumber` varchar(20) DEFAULT NULL,
  `passportNumber` varchar(20) DEFAULT NULL,
  `passportIssueDate` date DEFAULT NULL,
  `passportIssuedBy` varchar(200) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Create syntax for TABLE 'policy'
CREATE TABLE `policy` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `applicationId` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `number` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL DEFAULT '',
  `paymentId` varchar(100) DEFAULT NULL,
  `paymentDate` datetime DEFAULT NULL,
  `personId` int NOT NULL,
  `creationDate` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;