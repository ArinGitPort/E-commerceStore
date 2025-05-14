-- Database Backup for bunnishop
-- Generated: 2025-05-13 15:12:34

SET FOREIGN_KEY_CHECKS=0;

-- Table structure for table `archived_order_details`

DROP TABLE IF EXISTS `archived_order_details`;

CREATE TABLE `archived_order_details` (
  `archived_order_detail_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`archived_order_detail_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `archived_order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `archived_orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `archived_order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `archived_order_details`
INSERT INTO `archived_order_details` VALUES (1, 4, 4, 5, 900.00, '2025-05-06 19:57:28', '2025-05-06 19:57:28');

-- Table structure for table `archived_orders`

DROP TABLE IF EXISTS `archived_orders`;

CREATE TABLE `archived_orders` (
  `order_id` int NOT NULL,
  `customer_id` int NOT NULL,
  `order_date` datetime DEFAULT NULL,
  `order_status` enum('Completed','Returned','Rejected') NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `shipping_address` text,
  `shipping_phone` varchar(20) DEFAULT NULL,
  `delivery_method_id` int NOT NULL,
  `discount` decimal(10,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  `viewed` tinyint(1) DEFAULT '0',
  `estimated_delivery` date DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `customer_id` (`customer_id`),
  KEY `delivery_method_id` (`delivery_method_id`),
  CONSTRAINT `archived_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `archived_orders_ibfk_2` FOREIGN KEY (`delivery_method_id`) REFERENCES `delivery_methods` (`delivery_method_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `archived_orders`
INSERT INTO `archived_orders` VALUES (4, 6, '2025-05-06 19:57:28', 'Completed', 1058.00, 'Cut-cot Pulilan Bulacan, Ellen\'s Food House Pulilan Bulacan Public Market\n0967 BENATIONAL RD. 9091 Villa Priscilla\nBULACAN', NULL, 1, 0.00, '2025-05-06 19:57:28', '2025-05-13 20:53:39', 1, NULL);

-- Table structure for table `audit_logs`

DROP TABLE IF EXISTS `audit_logs`;

CREATE TABLE `audit_logs` (
  `log_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int NOT NULL,
  `timestamp` datetime DEFAULT CURRENT_TIMESTAMP,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `affected_data` json DEFAULT NULL,
  `action_type` enum('CREATE','READ','UPDATE','DELETE','LOGIN','LOGOUT','SYSTEM') NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  KEY `idx_timestamp` (`timestamp`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_table_name` (`table_name`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=84 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `audit_logs`
INSERT INTO `audit_logs` VALUES (1, NULL, 'Product created: AMC Keychain', 'products', 4, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"AMC Keychain\", \"price\": 180.0, \"stock\": 5}', 'CREATE'),
(2, NULL, 'Product created: AAC Art Print', 'products', 5, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"AAC Art Print\", \"price\": 69.0, \"stock\": 45}', 'CREATE'),
(3, NULL, 'Product created: ALI Button Pin', 'products', 6, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"ALI Button Pin\", \"price\": 160.0, \"stock\": 15}', 'CREATE'),
(4, NULL, 'Product created: Box 6 Sticker Pack', 'products', 7, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"Box 6 Sticker Pack\", \"price\": 120.0, \"stock\": 82}', 'CREATE'),
(5, NULL, 'Product created: CCC Bag', 'products', 8, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"CCC Bag\", \"price\": 250.0, \"stock\": 12}', 'CREATE'),
(6, NULL, 'Product created: CH Sticker', 'products', 9, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"CH Sticker\", \"price\": 50.0, \"stock\": 44}', 'CREATE'),
(7, NULL, 'Product created: DCS Journal', 'products', 10, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"DCS Journal\", \"price\": 189.0, \"stock\": 20}', 'CREATE'),
(8, NULL, 'Product created: DNG Earrings Pair', 'products', 11, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"DNG Earrings Pair\", \"price\": 120.0, \"stock\": 18}', 'CREATE'),
(9, NULL, 'Product created: Dainty Wanderess Dreamcatcher', 'products', 12, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"Dainty Wanderess Dreamcatcher\", \"price\": 300.0, \"stock\": 8}', 'CREATE'),
(10, NULL, 'Product created: DWS Badge', 'products', 13, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"DWS Badge\", \"price\": 35.0, \"stock\": 50}', 'CREATE'),
(11, NULL, 'Product created: Elianne Art Card', 'products', 14, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"Elianne Art Card\", \"price\": 80.0, \"stock\": 35}', 'CREATE'),
(12, NULL, 'Product created: elles_jewelry Ring', 'products', 15, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"elles_jewelry Ring\", \"price\": 99.0, \"stock\": 23}', 'CREATE'),
(13, NULL, 'Product created: EWEKNITSS Knit', 'products', 16, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"EWEKNITSS Knit\", \"price\": 150.0, \"stock\": 10}', 'CREATE'),
(14, NULL, 'Product created: FLIMSY Pin', 'products', 17, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"FLIMSY Pin\", \"price\": 70.0, \"stock\": 29}', 'CREATE'),
(15, NULL, 'Product created: Add-on Gift Wrap', 'products', 18, '2025-05-06 15:36:54', NULL, NULL, '{\"name\": \"Add-on Gift Wrap\", \"price\": 20.0, \"stock\": 100}', 'CREATE'),
(16, 6, 'User login', 'users', 6, '2025-05-06 17:01:31', NULL, NULL, NULL, 'LOGIN'),
(17, 6, 'User logged out', 'users', 6, '2025-05-06 17:07:15', 'localhost', '', NULL, 'LOGOUT'),
(18, 6, 'User login', 'users', 6, '2025-05-06 17:07:19', NULL, NULL, NULL, 'LOGIN'),
(19, 6, 'Created Order', 'orders', 1, '2025-05-06 19:24:08', NULL, NULL, NULL, 'CREATE'),
(20, 6, 'Created Order', 'orders', 2, '2025-05-06 19:30:04', NULL, NULL, NULL, 'CREATE'),
(21, 6, 'Created Order', 'orders', 3, '2025-05-06 19:31:17', NULL, NULL, NULL, 'CREATE'),
(22, NULL, 'Product updated: AAC Art Print', 'products', 5, '2025-05-06 19:31:17', NULL, NULL, '{\"stock\": {\"new\": 44, \"old\": 45}}', 'UPDATE'),
(23, NULL, 'Inventory changed for product: AAC Art Print', 'products', 5, '2025-05-06 19:31:17', NULL, NULL, '{\"new_stock\": 44, \"old_stock\": 45, \"difference\": -1}', 'UPDATE'),
(24, NULL, 'Product updated: ALI Button Pin', 'products', 6, '2025-05-06 19:31:17', NULL, NULL, '{\"stock\": {\"new\": 14, \"old\": 15}}', 'UPDATE'),
(25, NULL, 'Inventory changed for product: ALI Button Pin', 'products', 6, '2025-05-06 19:31:17', NULL, NULL, '{\"new_stock\": 14, \"old_stock\": 15, \"difference\": -1}', 'UPDATE'),
(26, 6, 'User logged out', 'users', 6, '2025-05-06 19:38:14', 'localhost', '', NULL, 'LOGOUT'),
(27, 6, 'User login', 'users', 6, '2025-05-06 19:38:27', NULL, NULL, NULL, 'LOGIN'),
(28, 6, 'User logged out', 'users', 6, '2025-05-06 19:38:36', 'localhost', '', NULL, 'LOGOUT'),
(29, 6, 'User login', 'users', 6, '2025-05-06 19:38:41', NULL, NULL, NULL, 'LOGIN'),
(30, 6, 'User logged out', 'users', 6, '2025-05-06 19:55:39', 'localhost', '', NULL, 'LOGOUT'),
(31, 6, 'User login', 'users', 6, '2025-05-06 19:56:17', NULL, NULL, NULL, 'LOGIN'),
(32, 7, 'User login', 'users', 7, '2025-05-06 19:56:23', NULL, NULL, NULL, 'LOGIN'),
(33, 6, 'Created Order', 'orders', 4, '2025-05-06 19:57:28', NULL, NULL, NULL, 'CREATE'),
(34, NULL, 'Product updated: AMC Keychain', 'products', 4, '2025-05-06 19:57:28', NULL, NULL, '{\"stock\": {\"new\": 0, \"old\": 5}}', 'UPDATE'),
(35, NULL, 'Inventory changed for product: AMC Keychain', 'products', 4, '2025-05-06 19:57:28', NULL, NULL, '{\"new_stock\": 0, \"old_stock\": 5, \"difference\": -5}', 'UPDATE'),
(36, 7, 'User login', 'users', 7, '2025-05-08 20:34:53', NULL, NULL, NULL, 'LOGIN'),
(37, 7, 'User logged out', 'users', 7, '2025-05-08 20:34:57', 'localhost', '', NULL, 'LOGOUT'),
(38, 6, 'User login', 'users', 6, '2025-05-08 20:37:01', NULL, NULL, NULL, 'LOGIN'),
(39, 7, 'User login', 'users', 7, '2025-05-13 18:31:49', NULL, NULL, NULL, 'LOGIN'),
(40, 7, 'User logged out', 'users', 7, '2025-05-13 18:31:54', 'localhost', '', NULL, 'LOGOUT'),
(41, 7, 'User logged out', 'users', 7, '2025-05-13 18:31:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGOUT'),
(42, 6, 'User login', 'users', 6, '2025-05-13 18:32:09', NULL, NULL, NULL, 'LOGIN'),
(43, 6, 'User logged out', 'users', 6, '2025-05-13 18:32:13', 'localhost', '', NULL, 'LOGOUT'),
(44, 6, 'User logged out', 'users', 6, '2025-05-13 18:32:13', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGOUT'),
(45, 7, 'User login', 'users', 7, '2025-05-13 18:35:44', NULL, NULL, NULL, 'LOGIN'),
(46, 7, 'User logged out', 'users', 7, '2025-05-13 18:35:49', 'localhost', '', NULL, 'LOGOUT'),
(47, 7, 'User logged out', 'users', 7, '2025-05-13 18:35:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGOUT'),
(48, 7, 'User login', 'users', 7, '2025-05-13 18:35:56', NULL, NULL, NULL, 'LOGIN'),
(49, 7, 'User logged out', 'users', 7, '2025-05-13 18:40:39', 'localhost', '', NULL, 'LOGOUT'),
(50, 7, 'User logged out', 'users', 7, '2025-05-13 18:40:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGOUT'),
(51, 7, 'User login', 'users', 7, '2025-05-13 18:40:47', NULL, NULL, NULL, 'LOGIN'),
(52, 7, 'User logged out', 'users', 7, '2025-05-13 18:40:53', 'localhost', '', NULL, 'LOGOUT'),
(53, 7, 'User logged out', 'users', 7, '2025-05-13 18:40:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGOUT'),
(54, 7, 'User role changed from Customer to Super Admin', 'users', 7, '2025-05-13 18:42:01', NULL, NULL, '{\"changed_at\": \"2025-05-13 18:42:01.000000\", \"new_role_id\": 5, \"old_role_id\": 1, \"new_role_name\": \"Super Admin\", \"old_role_name\": \"Customer\"}', 'UPDATE'),
(55, 7, 'User login', 'users', 7, '2025-05-13 18:42:10', NULL, NULL, NULL, 'LOGIN'),
(56, 7, 'User logged out', 'users', 7, '2025-05-13 19:13:12', 'localhost', '', NULL, 'LOGOUT'),
(57, 7, 'User logged out', 'users', 7, '2025-05-13 19:13:12', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGOUT'),
(58, 7, 'User login', 'users', 7, '2025-05-13 19:13:22', NULL, NULL, NULL, 'LOGIN'),
(59, 7, 'User logged in', 'users', 7, '2025-05-13 19:13:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGIN'),
(60, 7, 'Database PHP backup created', 'system', 0, '2025-05-13 19:13:47', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"../backups/bunnishop-phpbackup-2025-05-13-13-13-47.sql\", \"time\": \"2025-05-13 13:13:47\"}', 'SYSTEM'),
(61, 7, 'Database PHP backup created', 'system', 0, '2025-05-13 19:24:11', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"../backups/bunnishop-phpbackup-2025-05-13-13-24-11.sql\", \"time\": \"2025-05-13 13:24:11\"}', 'SYSTEM'),
(62, 7, 'Backup schedule created', 'backup_schedules', 1, '2025-05-13 19:25:31', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"hour\": 0, \"minute\": 0, \"frequency\": \"daily\", \"is_active\": 1, \"day_of_week\": null, \"day_of_month\": null, \"retention_days\": 30}', 'CREATE'),
(63, 7, 'Backup schedule deleted', 'backup_schedules', 1, '2025-05-13 19:25:39', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"schedule_id\": \"1\"}', 'DELETE'),
(64, 7, 'Backup schedule created', 'backup_schedules', 2, '2025-05-13 19:25:43', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"hour\": 0, \"minute\": 0, \"frequency\": \"daily\", \"is_active\": 1, \"day_of_week\": null, \"day_of_month\": null, \"retention_days\": 365}', 'CREATE'),
(65, 7, 'Backup schedule deactivated', 'backup_schedules', 2, '2025-05-13 19:27:27', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"new_status\": 0, \"schedule_id\": \"2\"}', 'UPDATE'),
(66, 7, 'Backup schedule activated', 'backup_schedules', 2, '2025-05-13 19:27:28', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"new_status\": 1, \"schedule_id\": \"2\"}', 'UPDATE'),
(67, 7, 'Database PHP backup created', 'system', 0, '2025-05-13 19:27:44', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"../backups/bunnishop-phpbackup-2025-05-13-13-27-44.sql\", \"time\": \"2025-05-13 13:27:44\"}', 'SYSTEM'),
(68, 7, 'Backup file deleted', 'system', 0, '2025-05-13 19:27:49', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"bunnishop-phpbackup-2025-05-13-13-27-44.sql\", \"time\": \"2025-05-13 13:27:49\"}', 'DELETE'),
(69, 7, 'Backup file deleted', 'system', 0, '2025-05-13 19:27:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"bunnishop-backup-2025-05-13-13-24-10.sql\", \"time\": \"2025-05-13 13:27:51\"}', 'DELETE'),
(70, 7, 'Backup file deleted', 'system', 0, '2025-05-13 19:27:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"bunnishop-phpbackup-2025-05-13-13-24-11.sql\", \"time\": \"2025-05-13 13:27:52\"}', 'DELETE'),
(71, 7, 'Backup file deleted', 'system', 0, '2025-05-13 19:27:54', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"bunnishop-backup-2025-05-13-13-13-47.sql\", \"time\": \"2025-05-13 13:27:54\"}', 'DELETE'),
(72, 7, 'Backup file deleted', 'system', 0, '2025-05-13 19:27:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"bunnishop-phpbackup-2025-05-13-13-13-47.sql\", \"time\": \"2025-05-13 13:27:55\"}', 'DELETE'),
(73, 7, 'Database PHP restored', 'system', 0, '2025-05-13 20:26:14', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"bunnishop-phpbackup-2025-05-13-13-28-08.sql\", \"time\": \"2025-05-13 14:26:14\"}', 'SYSTEM'),
(74, 7, 'Database PHP restored', 'system', 0, '2025-05-13 20:51:45', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"bunnishop-phpbackup-2025-05-13-14-26-58.sql\", \"time\": \"2025-05-13 14:51:45\"}', 'SYSTEM'),
(75, 7, 'Backup file deleted', 'system', 0, '2025-05-13 20:51:51', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"bunnishop-phpbackup-2025-05-13-14-28-04.sql\", \"time\": \"2025-05-13 14:51:51\"}', 'DELETE'),
(76, 7, 'Backup file deleted', 'system', 0, '2025-05-13 20:51:52', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"bunnishop-phpbackup-2025-05-13-14-26-58.sql\", \"time\": \"2025-05-13 14:51:52\"}', 'DELETE'),
(77, 7, 'Database PHP restored', 'system', 0, '2025-05-13 20:54:53', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"bunnishop-phpbackup-2025-05-13-14-53-50.sql\", \"time\": \"2025-05-13 14:54:53\"}', 'SYSTEM'),
(78, 7, 'Backup file deleted', 'system', 0, '2025-05-13 20:54:55', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"bunnishop-phpbackup-2025-05-13-14-53-50.sql\", \"time\": \"2025-05-13 14:54:55\"}', 'DELETE'),
(79, 7, 'Updated product: AMC Keychain', 'products', 4, '2025-05-13 20:55:04', NULL, NULL, NULL, 'CREATE'),
(80, 7, 'Database PHP restored', 'system', 0, '2025-05-13 20:56:57', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"bunnishop-phpbackup-2025-05-13-14-55-50.sql\", \"time\": \"2025-05-13 14:56:57\"}', 'SYSTEM'),
(81, 7, 'Database PHP backup created', 'system', 0, '2025-05-13 21:05:33', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"../backups/bunnishop-phpbackup-2025-05-13-15-05-33.sql\", \"time\": \"2025-05-13 15:05:33\"}', 'SYSTEM'),
(82, 7, 'Backup file deleted', 'system', 0, '2025-05-13 21:05:48', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"bunnishop-phpbackup-2025-05-13-15-05-33.sql\", \"time\": \"2025-05-13 15:05:48\"}', 'DELETE'),
(83, 7, 'Backup file deleted', 'system', 0, '2025-05-13 21:11:38', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\": \"bunnishop-phpbackup-2025-05-13-14-55-50.sql\", \"time\": \"2025-05-13 15:11:38\"}', 'DELETE');

-- Table structure for table `backup_schedules`

DROP TABLE IF EXISTS `backup_schedules`;

CREATE TABLE `backup_schedules` (
  `id` int NOT NULL AUTO_INCREMENT,
  `frequency` enum('daily','weekly','monthly') NOT NULL,
  `day_of_week` int DEFAULT NULL,
  `day_of_month` int DEFAULT NULL,
  `hour` int NOT NULL DEFAULT '0',
  `minute` int NOT NULL DEFAULT '0',
  `retention_days` int NOT NULL DEFAULT '30',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `last_run` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `backup_schedules_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `backup_schedules`
INSERT INTO `backup_schedules` VALUES (2, 'daily', NULL, NULL, 0, 0, 365, 1, 7, '2025-05-13 19:25:43', NULL);

-- Table structure for table `cart_items`

DROP TABLE IF EXISTS `cart_items`;

CREATE TABLE `cart_items` (
  `cart_item_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`cart_item_id`),
  UNIQUE KEY `user_id` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `cart_items_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `cart_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `cart_items`
INSERT INTO `cart_items` VALUES (15, 7, 4, 5, '2025-05-06 19:56:42', '2025-05-06 19:56:42');

-- Table structure for table `categories`

DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `category_id` int NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `categories`
INSERT INTO `categories` VALUES (3, 'Accessories'),
(4, 'Add-on'),
(7, 'AMAZEBALL CRAFT (AMC)'),
(8, 'ART IN A BOX (BOX)'),
(5, 'Artaftercoffee (AAC)'),
(6, 'ARTLIYAAAAH (ALI)'),
(10, 'Cchi House (CH)'),
(9, 'CRISTINE\'S CROCHET CASTLE (CCC)'),
(13, 'Dainty Wanderess'),
(12, 'DANGLES (DNG)'),
(11, 'DREAMERS\' CREATES (DCS)'),
(14, 'DWNSTUDIOS (DWS)'),
(15, 'Elianne Art'),
(16, 'elles_jewelry (ELL)'),
(17, 'EWEKNITSS (EWT)'),
(18, 'FLIMSY (FLM)'),
(1, 'Plushies'),
(2, 'Stationery');

-- Table structure for table `delivery_methods`

DROP TABLE IF EXISTS `delivery_methods`;

CREATE TABLE `delivery_methods` (
  `delivery_method_id` int NOT NULL AUTO_INCREMENT,
  `method_name` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `estimated_days` int DEFAULT '3',
  PRIMARY KEY (`delivery_method_id`),
  UNIQUE KEY `method_name` (`method_name`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `delivery_methods`
INSERT INTO `delivery_methods` VALUES (1, 'Standard Shipping', '2025-05-06 15:36:47', '2025-05-06 15:36:47', 3),
(2, 'Express Shipping', '2025-05-06 15:36:47', '2025-05-06 15:36:47', 1),
(3, 'Same Day Delivery', '2025-05-06 15:36:47', '2025-05-06 15:36:47', 0),
(4, 'Pickup In-Store', '2025-05-06 15:36:47', '2025-05-06 15:36:47', 0);

-- Table structure for table `email_change_requests`

DROP TABLE IF EXISTS `email_change_requests`;

CREATE TABLE `email_change_requests` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `new_email` varchar(100) NOT NULL,
  `verification_token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `verification_token` (`verification_token`),
  CONSTRAINT `email_change_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table structure for table `membership_discounts`

DROP TABLE IF EXISTS `membership_discounts`;

CREATE TABLE `membership_discounts` (
  `discount_id` int NOT NULL AUTO_INCREMENT,
  `membership_type_id` int NOT NULL,
  `category_id` int DEFAULT NULL,
  `product_id` int DEFAULT NULL,
  `discount_rate` decimal(4,2) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`discount_id`),
  KEY `membership_type_id` (`membership_type_id`),
  KEY `category_id` (`category_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `membership_discounts_ibfk_1` FOREIGN KEY (`membership_type_id`) REFERENCES `membership_types` (`membership_type_id`) ON DELETE CASCADE,
  CONSTRAINT `membership_discounts_ibfk_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE,
  CONSTRAINT `membership_discounts_ibfk_3` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table structure for table `membership_types`

DROP TABLE IF EXISTS `membership_types`;

CREATE TABLE `membership_types` (
  `membership_type_id` int NOT NULL AUTO_INCREMENT,
  `type_name` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT '0.00',
  `description` text,
  `can_access_exclusive` tinyint(1) DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`membership_type_id`),
  UNIQUE KEY `type_name` (`type_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `membership_types`
INSERT INTO `membership_types` VALUES (1, 'Free', 0.00, NULL, 0, '2025-05-06 15:36:47', '2025-05-06 15:36:47'),
(2, 'Dreamy Nook', 150.00, NULL, 1, '2025-05-06 15:36:47', '2025-05-06 15:36:47'),
(3, 'Secret Paper Stash (Stationery Tier)', 500.00, NULL, 1, '2025-05-06 15:36:47', '2025-05-06 15:36:47'),
(4, 'Crafty Wonderland (Tutorial Tier)', 750.00, NULL, 1, '2025-05-06 15:36:47', '2025-05-06 15:36:47'),
(5, 'Little Charm Box (Clay Tier)', 1100.00, NULL, 1, '2025-05-06 15:36:47', '2025-05-06 15:36:47'),
(6, 'Bunni\'s Enchanted Garden (All-in Tier)', 2000.00, NULL, 1, '2025-05-06 15:36:47', '2025-05-06 15:36:47');

-- Table structure for table `memberships`

DROP TABLE IF EXISTS `memberships`;

CREATE TABLE `memberships` (
  `membership_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `start_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `membership_type_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`membership_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `membership_type_id` (`membership_type_id`),
  CONSTRAINT `memberships_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `memberships_ibfk_2` FOREIGN KEY (`membership_type_id`) REFERENCES `membership_types` (`membership_type_id`) ON DELETE RESTRICT,
  CONSTRAINT `memberships_chk_1` CHECK (((`expiry_date` > `start_date`) or (`expiry_date` is null)))
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `memberships`
INSERT INTO `memberships` VALUES (1, 1, '2024-01-01', NULL, 1, '2025-05-06 15:36:48', '2025-05-06 15:36:50'),
(2, 2, '2024-01-01', NULL, 1, '2025-05-06 15:36:48', '2025-05-06 15:36:50'),
(3, 3, '2024-01-01', NULL, 1, '2025-05-06 15:36:48', '2025-05-06 15:36:50'),
(4, 6, '2025-05-06', NULL, 1, '2025-05-06 17:01:21', '2025-05-06 17:01:21'),
(5, 7, '2025-05-06', NULL, 1, '2025-05-06 19:56:08', '2025-05-06 19:56:08');

-- Table structure for table `notification_membership_targets`

DROP TABLE IF EXISTS `notification_membership_targets`;

CREATE TABLE `notification_membership_targets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notification_id` int NOT NULL,
  `membership_type_id` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `membership_type_id` (`membership_type_id`),
  KEY `idx_notification_targets` (`notification_id`,`membership_type_id`),
  CONSTRAINT `notification_membership_targets_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE,
  CONSTRAINT `notification_membership_targets_ibfk_2` FOREIGN KEY (`membership_type_id`) REFERENCES `membership_types` (`membership_type_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `notification_membership_targets`
INSERT INTO `notification_membership_targets` VALUES (1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 2, 3);

-- Table structure for table `notification_recipients`

DROP TABLE IF EXISTS `notification_recipients`;

CREATE TABLE `notification_recipients` (
  `id` int NOT NULL AUTO_INCREMENT,
  `notification_id` int NOT NULL,
  `user_id` int NOT NULL,
  `is_read` tinyint(1) DEFAULT '0',
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_id` (`notification_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notification_recipients_ibfk_1` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`notification_id`) ON DELETE CASCADE,
  CONSTRAINT `notification_recipients_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table structure for table `notification_templates`

DROP TABLE IF EXISTS `notification_templates`;

CREATE TABLE `notification_templates` (
  `template_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_by` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`template_id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `notification_templates_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table structure for table `notifications`

DROP TABLE IF EXISTS `notifications`;

CREATE TABLE `notifications` (
  `notification_id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_by` int NOT NULL,
  `is_active` tinyint(1) DEFAULT '1',
  `start_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`notification_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_notifications_dates` (`start_date`,`expiry_date`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `notifications`
INSERT INTO `notifications` VALUES (1, 'Holiday Promo!', 'Everyone gets 10% off this week only!', 5, 1, '2025-05-06', '2025-05-13', '2025-05-06 15:36:48'),
(2, 'VIP Gift Incoming!', 'Hey VIP! Your mystery gift is on the way 🎁', 5, 1, '2025-05-06', '2025-06-05', '2025-05-06 15:36:48');

-- Table structure for table `order_details`

DROP TABLE IF EXISTS `order_details`;

CREATE TABLE `order_details` (
  `order_detail_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`order_detail_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `order_details`
INSERT INTO `order_details` VALUES (1, 1, 5, 1, 69.00, '2025-05-06 19:24:08', '2025-05-06 19:24:08'),
(2, 2, 5, 2, 138.00, '2025-05-06 19:30:04', '2025-05-06 19:30:04'),
(3, 2, 6, 1, 160.00, '2025-05-06 19:30:04', '2025-05-06 19:30:04'),
(4, 3, 5, 1, 69.00, '2025-05-06 19:31:17', '2025-05-06 19:31:17'),
(5, 3, 6, 1, 160.00, '2025-05-06 19:31:17', '2025-05-06 19:31:17');

-- Table structure for table `orders`

DROP TABLE IF EXISTS `orders`;

CREATE TABLE `orders` (
  `order_id` int NOT NULL AUTO_INCREMENT,
  `customer_id` int NOT NULL,
  `order_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `shipping_name` varchar(255) NOT NULL,
  `shipping_address` text NOT NULL,
  `shipping_phone` varchar(20) NOT NULL,
  `order_status` enum('Pending','Shipped','Delivered','Received','Cancelled','Returned') NOT NULL DEFAULT 'Pending',
  `total_price` decimal(10,2) NOT NULL,
  `delivery_method_id` int NOT NULL,
  `discount` decimal(10,2) DEFAULT '0.00',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `viewed` tinyint(1) DEFAULT '0',
  `estimated_delivery` date DEFAULT NULL,
  `cancel_reason` text,
  PRIMARY KEY (`order_id`),
  KEY `customer_id` (`customer_id`),
  KEY `delivery_method_id` (`delivery_method_id`),
  CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  CONSTRAINT `orders_ibfk_2` FOREIGN KEY (`delivery_method_id`) REFERENCES `delivery_methods` (`delivery_method_id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `orders`
INSERT INTO `orders` VALUES (1, 6, '2025-05-06 19:24:08', 'monochrome', 'Cut-cot Pulilan Bulacan, Ellen\'s Food House Pulilan Bulacan Public Market\n0967 BENATIONAL RD. 9091 Villa Priscilla\nBULACAN', 9663787625, 'Pending', 127.28, 1, 0.00, '2025-05-06 19:24:08', '2025-05-13 20:51:47', 1, NULL, NULL),
(2, 6, '2025-05-06 19:30:04', 'monochrome', 'Cut-cot Pulilan Bulacan, Ellen\'s Food House Pulilan Bulacan Public Market\n0967 BENATIONAL RD. 9091 Villa Priscilla\nBULACAN', 9663787625, 'Pending', 383.76, 1, 0.00, '2025-05-06 19:30:04', '2025-05-13 20:51:47', 1, NULL, NULL),
(3, 6, '2025-05-06 19:31:17', 'monochrome', 'Cut-cot Pulilan Bulacan, Ellen\'s Food House Pulilan Bulacan Public Market\n0967 BENATIONAL RD. 9091 Villa Priscilla\nBULACAN', 9663787625, 'Shipped', 306.48, 1, 0.00, '2025-05-06 19:31:17', '2025-05-13 20:55:42', 1, NULL, NULL);

-- Table structure for table `password_resets`

DROP TABLE IF EXISTS `password_resets`;

CREATE TABLE `password_resets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `token` (`token`),
  CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table structure for table `payment_methods`

DROP TABLE IF EXISTS `payment_methods`;

CREATE TABLE `payment_methods` (
  `payment_method_id` int NOT NULL AUTO_INCREMENT,
  `method_name` varchar(50) NOT NULL,
  PRIMARY KEY (`payment_method_id`),
  UNIQUE KEY `method_name` (`method_name`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `payment_methods`
INSERT INTO `payment_methods` VALUES (5, 'Bank Transfer'),
(4, 'Cash on Delivery'),
(2, 'Credit Card'),
(3, 'Debit Card'),
(1, 'GCash');

-- Table structure for table `payments`

DROP TABLE IF EXISTS `payments`;

CREATE TABLE `payments` (
  `payment_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `payment_method_id` int NOT NULL,
  `payment_status` enum('Pending','Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`payment_id`),
  KEY `order_id` (`order_id`),
  KEY `payment_method_id` (`payment_method_id`),
  CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`payment_method_id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `payments`
INSERT INTO `payments` VALUES (1, 1, 4, 'Pending', '2025-05-06 19:24:08', '2025-05-06 19:24:08'),
(2, 2, 4, 'Pending', '2025-05-06 19:30:04', '2025-05-06 19:30:04'),
(3, 3, 4, 'Pending', '2025-05-06 19:31:17', '2025-05-06 19:31:17');

-- Table structure for table `product_images`

DROP TABLE IF EXISTS `product_images`;

CREATE TABLE `product_images` (
  `image_id` int NOT NULL AUTO_INCREMENT,
  `product_id` int NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT '0',
  `alt_text` varchar(100) DEFAULT NULL,
  `sort_order` int DEFAULT '0',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`image_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table structure for table `products`

DROP TABLE IF EXISTS `products`;

CREATE TABLE `products` (
  `product_id` int NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `description` text,
  `price` decimal(10,2) NOT NULL,
  `stock` int NOT NULL,
  `is_exclusive` tinyint(1) DEFAULT '0',
  `min_membership_level` int DEFAULT NULL,
  `category_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`),
  KEY `min_membership_level` (`min_membership_level`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE RESTRICT,
  CONSTRAINT `products_ibfk_2` FOREIGN KEY (`min_membership_level`) REFERENCES `membership_types` (`membership_type_id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `products`
INSERT INTO `products` VALUES (1, 'Fluffy Bunny Plush', 'PLUSH001', 'Soft and cuddly bunny plush perfect for snuggles.', 14.99, 50, 1, 2, 1, '2025-05-06 15:36:48', '2025-05-06 15:36:48'),
(2, 'Kawaii Bunny Notebook', 'NOTE001', 'Adorable bunny-themed notebook for journaling.', 6.49, 100, 0, NULL, 2, '2025-05-06 15:36:48', '2025-05-06 15:36:48'),
(3, 'Bunny Enamel Pin', 'PIN001', 'A cute enamel pin to decorate your bag or jacket.', 3.99, 75, 0, NULL, 3, '2025-05-06 15:36:48', '2025-05-06 15:36:48'),
(4, 'AMC Keychain', 'AMC-KCN', '', 180.00, 0, 0, NULL, 4, '2025-05-06 15:36:54', '2025-05-13 20:55:04'),
(5, 'AAC Art Print', 'AAC-AP', NULL, 69.00, 44, 0, NULL, 2, '2025-05-06 15:36:54', '2025-05-06 19:31:17'),
(6, 'ALI Button Pin', 'ALI-BPT', NULL, 160.00, 14, 0, NULL, 3, '2025-05-06 15:36:54', '2025-05-06 19:31:17'),
(7, 'Box 6 Sticker Pack', 'BOX-6SP', NULL, 120.00, 82, 0, NULL, 5, '2025-05-06 15:36:54', '2025-05-06 15:36:54'),
(8, 'CCC Bag', 'CCC-BG', NULL, 250.00, 12, 0, NULL, 6, '2025-05-06 15:36:54', '2025-05-06 15:36:54'),
(9, 'CH Sticker', 'CH-STC', NULL, 50.00, 44, 0, NULL, 7, '2025-05-06 15:36:54', '2025-05-06 15:36:54'),
(10, 'DCS Journal', 'DCS-JRN', NULL, 189.00, 20, 0, NULL, 8, '2025-05-06 15:36:54', '2025-05-06 15:36:54'),
(11, 'DNG Earrings Pair', 'DNG-ERP', NULL, 120.00, 18, 0, NULL, 9, '2025-05-06 15:36:54', '2025-05-06 15:36:54'),
(12, 'Dainty Wanderess Dreamcatcher', 'DWN-DRC', NULL, 300.00, 8, 0, NULL, 10, '2025-05-06 15:36:54', '2025-05-06 15:36:54'),
(13, 'DWS Badge', 'DWS-BDG', NULL, 35.00, 50, 0, NULL, 11, '2025-05-06 15:36:54', '2025-05-06 15:36:54'),
(14, 'Elianne Art Card', 'ELA-CARD', NULL, 80.00, 35, 0, NULL, 12, '2025-05-06 15:36:54', '2025-05-06 15:36:54'),
(15, 'elles_jewelry Ring', 'ELL-RNG', NULL, 99.00, 23, 0, NULL, 13, '2025-05-06 15:36:54', '2025-05-06 15:36:54'),
(16, 'EWEKNITSS Knit', 'EWT-KNT', NULL, 150.00, 10, 0, NULL, 14, '2025-05-06 15:36:54', '2025-05-06 15:36:54'),
(17, 'FLIMSY Pin', 'FLM-PIN', NULL, 70.00, 29, 0, NULL, 15, '2025-05-06 15:36:54', '2025-05-06 15:36:54'),
(18, 'Add-on Gift Wrap', 'ADD-GFT', NULL, 20.00, 100, 0, NULL, 1, '2025-05-06 15:36:54', '2025-05-06 15:36:54');

-- Table structure for table `return_details`

DROP TABLE IF EXISTS `return_details`;

CREATE TABLE `return_details` (
  `return_detail_id` int NOT NULL AUTO_INCREMENT,
  `return_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  PRIMARY KEY (`return_detail_id`),
  KEY `return_id` (`return_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `return_details_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`return_id`) ON DELETE CASCADE,
  CONSTRAINT `return_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table structure for table `return_items`

DROP TABLE IF EXISTS `return_items`;

CREATE TABLE `return_items` (
  `return_item_id` int NOT NULL AUTO_INCREMENT,
  `return_id` int NOT NULL,
  `product_id` int NOT NULL,
  `quantity` int NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `restocking_fee` decimal(10,2) DEFAULT '0.00',
  `reason` varchar(255) DEFAULT NULL,
  `item_condition` enum('New','Opened','Damaged') DEFAULT 'New',
  PRIMARY KEY (`return_item_id`),
  KEY `return_id` (`return_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `return_items_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`return_id`) ON DELETE CASCADE,
  CONSTRAINT `return_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE RESTRICT,
  CONSTRAINT `return_items_chk_1` CHECK ((`quantity` > 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table structure for table `return_status_history`

DROP TABLE IF EXISTS `return_status_history`;

CREATE TABLE `return_status_history` (
  `history_id` int NOT NULL AUTO_INCREMENT,
  `return_id` int NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `changed_by` int DEFAULT NULL,
  `change_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `notes` text,
  PRIMARY KEY (`history_id`),
  KEY `return_id` (`return_id`),
  KEY `changed_by` (`changed_by`),
  CONSTRAINT `return_status_history_ibfk_1` FOREIGN KEY (`return_id`) REFERENCES `returns` (`return_id`) ON DELETE CASCADE,
  CONSTRAINT `return_status_history_ibfk_2` FOREIGN KEY (`changed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table structure for table `returns`

DROP TABLE IF EXISTS `returns`;

CREATE TABLE `returns` (
  `return_id` int NOT NULL AUTO_INCREMENT,
  `order_id` int NOT NULL,
  `is_archived` tinyint(1) NOT NULL,
  `archived_order_id` int NOT NULL,
  `return_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_by` int DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `return_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `status_history` json DEFAULT NULL,
  `last_status_update` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`return_id`),
  KEY `archived_order_id` (`archived_order_id`),
  KEY `processed_by` (`processed_by`),
  KEY `idx_return_status` (`return_status`),
  CONSTRAINT `returns_ibfk_1` FOREIGN KEY (`archived_order_id`) REFERENCES `archived_orders` (`order_id`) ON DELETE CASCADE,
  CONSTRAINT `returns_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table structure for table `roles`

DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `role_id` int NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `roles`
INSERT INTO `roles` VALUES (1, 'Customer', '2025-05-06 15:36:47', '2025-05-06 15:36:47'),
(2, 'Member', '2025-05-06 15:36:47', '2025-05-06 15:36:47'),
(3, 'Staff', '2025-05-06 15:36:47', '2025-05-06 15:36:47'),
(4, 'Admin', '2025-05-06 15:36:47', '2025-05-06 15:36:47'),
(5, 'Super Admin', '2025-05-06 15:36:47', '2025-05-06 15:36:47'),
(6, 'Brand Partners', '2025-05-06 15:36:47', '2025-05-06 15:36:47');

-- Table structure for table `subscriptions_audit`

DROP TABLE IF EXISTS `subscriptions_audit`;

CREATE TABLE `subscriptions_audit` (
  `audit_id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `membership_type_id` int NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `payment_method` enum('credit_card','paypal','gcash','bank_transfer','other') NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'completed',
  `reference_number` varchar(50) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`audit_id`),
  KEY `user_id` (`user_id`),
  KEY `membership_type_id` (`membership_type_id`),
  CONSTRAINT `subscriptions_audit_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
  CONSTRAINT `subscriptions_audit_ibfk_2` FOREIGN KEY (`membership_type_id`) REFERENCES `membership_types` (`membership_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table structure for table `users`

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `user_id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text,
  `role_id` int NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `modified_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `last_login_at` datetime DEFAULT NULL,
  `last_logout_at` datetime DEFAULT NULL,
  `last_activity_at` datetime DEFAULT NULL,
  `activation_token` varchar(32) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT '0',
  `oauth_provider` varchar(20) DEFAULT NULL,
  `oauth_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`) ON DELETE RESTRICT
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES (1, 'User Free', 'free@bunniwinkle.com', 'hashed_pw', NULL, NULL, 1, '2025-05-06 15:36:48', '2025-05-06 15:36:48', NULL, NULL, NULL, NULL, 1, NULL, NULL),
(2, 'User Premium', 'premium@bunniwinkle.com', 'hashed_pw', NULL, NULL, 1, '2025-05-06 15:36:48', '2025-05-06 15:36:48', NULL, NULL, NULL, NULL, 1, NULL, NULL),
(3, 'User VIP', 'vip@bunniwinkle.com', 'hashed_pw', NULL, NULL, 1, '2025-05-06 15:36:48', '2025-05-06 15:36:48', NULL, NULL, NULL, NULL, 1, NULL, NULL),
(4, 'Admin User', 'admin@bunniwinkle.com', 'hashed_pw', NULL, NULL, 2, '2025-05-06 15:36:48', '2025-05-06 15:36:48', NULL, NULL, NULL, NULL, 1, NULL, NULL),
(5, 'Super Admin', 'superadmin@bunniwinkle.com', 'hashed_pw', NULL, NULL, 4, '2025-05-06 15:36:48', '2025-05-06 15:36:48', NULL, NULL, NULL, NULL, 1, NULL, NULL),
(6, 'monochrome', 'allen@gmail.com', '$2y$12$2bhTSxlmF7qm6Xoo/H68Uer1u5Q3j.67plxK2A6H3Plg1VeWvk1YG', 9663787625, 'Cut-cot Pulilan Bulacan, Ellen\'s Food House Pulilan Bulacan Public Market\n0967 BENATIONAL RD. 9091 Villa Priscilla\nBULACAN', 1, '2025-05-06 17:01:21', '2025-05-13 18:32:13', '2025-05-13 18:32:09', '2025-05-13 18:32:13', '2025-05-13 18:32:13', NULL, 1, NULL, NULL),
(7, 'rimue', 'monochrome@gmail.com', '$2y$12$Zil0p3kpzCTsErTLjgP/ZeMBIwJ9TfU7Hruf4.FpDdx9SRMHjf3ua', 9663787625, 'BULACAN', 5, '2025-05-06 19:56:08', '2025-05-13 21:12:34', '2025-05-13 19:13:22', '2025-05-13 19:13:12', '2025-05-13 21:12:34', NULL, 1, NULL, NULL);

SET FOREIGN_KEY_CHECKS=1;
