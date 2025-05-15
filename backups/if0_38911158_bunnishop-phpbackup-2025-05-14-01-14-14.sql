-- Database Backup for if0_38911158_bunnishop
-- Generated: 2025-05-14 01:14:14

SET FOREIGN_KEY_CHECKS=0;

-- Table structure for table `archived_order_details`

DROP TABLE IF EXISTS `archived_order_details`;

CREATE TABLE `archived_order_details` (
  `archived_order_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  PRIMARY KEY (`archived_order_detail_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table structure for table `archived_orders`

DROP TABLE IF EXISTS `archived_orders`;

CREATE TABLE `archived_orders` (
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `order_date` datetime DEFAULT NULL,
  `order_status` enum('Completed','Returned','Rejected') NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `shipping_address` text DEFAULT NULL,
  `shipping_phone` varchar(20) DEFAULT NULL,
  `delivery_method_id` int(11) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT NULL,
  `modified_at` datetime DEFAULT NULL,
  `viewed` tinyint(1) DEFAULT 0,
  `estimated_delivery` date DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `customer_id` (`customer_id`),
  KEY `delivery_method_id` (`delivery_method_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table structure for table `audit_logs`

DROP TABLE IF EXISTS `audit_logs`;

CREATE TABLE `audit_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `table_name` varchar(50) NOT NULL,
  `record_id` int(11) NOT NULL,
  `timestamp` datetime DEFAULT current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `affected_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`affected_data`)),
  `action_type` enum('CREATE','READ','UPDATE','DELETE','LOGIN','LOGOUT','SYSTEM') NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM AUTO_INCREMENT=27 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `audit_logs`
INSERT INTO `audit_logs` VALUES (1, 7, 'Updated product: CCC Bag', 'products', 8, '2025-05-06 18:46:52', NULL, NULL, NULL, 'CREATE'),
(2, 7, 'Updated product: AAC Art Print', 'products', 5, '2025-05-06 18:48:27', NULL, NULL, NULL, 'CREATE'),
(3, 7, 'Updated product: AAC Art Print', 'products', 5, '2025-05-06 18:48:33', NULL, NULL, NULL, 'CREATE'),
(4, 7, 'Updated product: AMC Keychain', 'products', 4, '2025-05-06 18:49:03', NULL, NULL, NULL, 'CREATE'),
(5, 7, 'Updated product: AMC Keychain', 'products', 4, '2025-05-06 18:49:26', NULL, NULL, NULL, 'CREATE'),
(6, 7, 'Updated product: Box 6 Sticker Pack', 'products', 7, '2025-05-06 18:50:09', NULL, NULL, NULL, 'CREATE'),
(7, 7, 'Updated product: Box 6 Sticker Pack', 'products', 7, '2025-05-06 18:50:14', NULL, NULL, NULL, 'CREATE'),
(8, 7, 'Updated product: Dainty Wanderess Dreamcatcher', 'products', 12, '2025-05-06 18:51:35', NULL, NULL, NULL, 'CREATE'),
(9, 7, 'Updated product: Dainty Wanderess Dreamcatcher', 'products', 12, '2025-05-06 18:51:40', NULL, NULL, NULL, 'CREATE'),
(10, 7, 'Updated product: Bunny Enamel Pin', 'products', 3, '2025-05-06 18:52:15', NULL, NULL, NULL, 'CREATE'),
(11, 7, 'Updated product: Bunny Enamel Pin', 'products', 3, '2025-05-06 18:52:22', NULL, NULL, NULL, 'CREATE'),
(12, 7, 'Updated product: DCS Journal', 'products', 10, '2025-05-06 18:52:57', NULL, NULL, NULL, 'CREATE'),
(13, 7, 'Updated product: DCS Journal', 'products', 10, '2025-05-06 18:53:00', NULL, NULL, NULL, 'CREATE'),
(14, 7, 'Updated product: CH Sticker', 'products', 9, '2025-05-06 18:53:28', NULL, NULL, NULL, 'CREATE'),
(15, 7, 'Updated product: CH Sticker', 'products', 9, '2025-05-06 18:53:31', NULL, NULL, NULL, 'CREATE'),
(16, 6, 'User logged in', 'users', 6, '2025-05-13 03:50:32', '180.190.144.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGIN'),
(17, 7, 'User logged in', 'users', 7, '2025-05-13 03:50:50', '180.190.144.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGIN'),
(18, 7, 'User logged out', 'users', 7, '2025-05-13 03:53:05', '180.190.144.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGOUT'),
(19, 7, 'User logged in', 'users', 7, '2025-05-13 03:53:12', '180.190.144.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGIN'),
(20, 7, 'User logged out', 'users', 7, '2025-05-13 04:00:43', '180.190.144.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGOUT'),
(21, 9, 'User registered', 'users', 9, '2025-05-13 04:01:05', '180.190.144.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"email\":\"allen1@gmail.com\",\"name\":\"monochrome\"}', 'CREATE'),
(22, 7, 'User logged in', 'users', 7, '2025-05-13 04:01:23', '180.190.144.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGIN'),
(23, 7, 'User logged in', 'users', 7, '2025-05-13 08:48:53', '180.190.144.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGIN'),
(24, 7, 'Backup schedule created', 'backup_schedules', 1, '2025-05-13 08:56:02', '180.190.144.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"frequency\":\"daily\",\"day_of_week\":null,\"day_of_month\":null,\"hour\":0,\"minute\":0,\"retention_days\":30,\"is_active\":1}', 'CREATE'),
(25, 7, 'Database PHP restored', 'system', 0, '2025-05-13 09:00:08', '180.190.144.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '{\"file\":\"if0_38911158_bunnishop-phpbackup-2025-05-13-11-57-26.sql\",\"time\":\"2025-05-13 12:00:08\"}', 'SYSTEM'),
(26, 7, 'User logged in', 'users', 7, '2025-05-13 22:13:20', '180.190.144.52', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/136.0.0.0 Safari/537.36', '[]', 'LOGIN');

-- Table structure for table `backup_schedules`

DROP TABLE IF EXISTS `backup_schedules`;

CREATE TABLE `backup_schedules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `frequency` enum('daily','weekly','monthly') NOT NULL,
  `day_of_week` int(11) DEFAULT NULL,
  `day_of_month` int(11) DEFAULT NULL,
  `hour` int(11) NOT NULL DEFAULT 0,
  `minute` int(11) NOT NULL DEFAULT 0,
  `retention_days` int(11) NOT NULL DEFAULT 30,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `last_run` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=2 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `backup_schedules`
INSERT INTO `backup_schedules` VALUES (1, 'daily', NULL, NULL, 0, 0, 30, 1, 7, '2025-05-13 08:56:02', NULL);

-- Table structure for table `cart_items`

DROP TABLE IF EXISTS `cart_items`;

CREATE TABLE `cart_items` (
  `cart_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`cart_item_id`),
  UNIQUE KEY `user_id` (`user_id`,`product_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `cart_items`
INSERT INTO `cart_items` VALUES (5, 6, 3, 1, '2025-05-06 19:11:09', '2025-05-06 19:11:09');

-- Table structure for table `categories`

DROP TABLE IF EXISTS `categories`;

CREATE TABLE `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `category_name` varchar(100) NOT NULL,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `categories`
INSERT INTO `categories` VALUES (1, 'Plushies'),
(2, 'Stationery'),
(3, 'Accessories'),
(4, 'Add-on'),
(5, 'Artaftercoffee (AAC)'),
(6, 'ARTLIYAAAAH (ALI)'),
(7, 'AMAZEBALL CRAFT (AMC)'),
(8, 'ART IN A BOX (BOX)'),
(9, 'CRISTINE\'S CROCHET CASTLE (CCC)'),
(10, 'Cchi House (CH)'),
(11, 'DREAMERS\' CREATES (DCS)'),
(12, 'DANGLES (DNG)'),
(13, 'Dainty Wanderess'),
(14, 'DWNSTUDIOS (DWS)'),
(15, 'Elianne Art'),
(16, 'elles_jewelry (ELL)'),
(17, 'EWEKNITSS (EWT)'),
(18, 'FLIMSY (FLM)');

-- Table structure for table `delivery_methods`

DROP TABLE IF EXISTS `delivery_methods`;

CREATE TABLE `delivery_methods` (
  `delivery_method_id` int(11) NOT NULL AUTO_INCREMENT,
  `method_name` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `modified_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `estimated_days` int(11) DEFAULT 3,
  PRIMARY KEY (`delivery_method_id`),
  UNIQUE KEY `method_name` (`method_name`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `delivery_methods`
INSERT INTO `delivery_methods` VALUES (1, 'Standard Shipping', '2025-05-06 00:48:51', '2025-05-06 00:48:51', 3),
(2, 'Express Shipping', '2025-05-06 00:48:51', '2025-05-06 00:48:51', 1),
(3, 'Same Day Delivery', '2025-05-06 00:48:51', '2025-05-06 00:48:51', 0),
(4, 'Pickup In-Store', '2025-05-06 00:48:51', '2025-05-06 00:48:51', 0);

-- Table structure for table `email_change_requests`

DROP TABLE IF EXISTS `email_change_requests`;

CREATE TABLE `email_change_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `new_email` varchar(100) NOT NULL,
  `verification_token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `verification_token` (`verification_token`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table structure for table `membership_discounts`

DROP TABLE IF EXISTS `membership_discounts`;

CREATE TABLE `membership_discounts` (
  `discount_id` int(11) NOT NULL AUTO_INCREMENT,
  `membership_type_id` int(11) NOT NULL,
  `category_id` int(11) DEFAULT NULL,
  `product_id` int(11) DEFAULT NULL,
  `discount_rate` decimal(4,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `modified_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`discount_id`),
  KEY `membership_type_id` (`membership_type_id`),
  KEY `category_id` (`category_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table structure for table `membership_types`

DROP TABLE IF EXISTS `membership_types`;

CREATE TABLE `membership_types` (
  `membership_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(50) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `description` text DEFAULT NULL,
  `can_access_exclusive` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `modified_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`membership_type_id`),
  UNIQUE KEY `type_name` (`type_name`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `membership_types`
INSERT INTO `membership_types` VALUES (1, 'Free', 0.00, NULL, 0, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(2, 'Dreamy Nook', 150.00, NULL, 1, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(3, 'Secret Paper Stash (Stationery Tier)', 500.00, NULL, 1, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(4, 'Crafty Wonderland (Tutorial Tier)', 750.00, NULL, 1, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(5, 'Little Charm Box (Clay Tier)', 1100.00, NULL, 1, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(6, 'Bunni\'s Enchanted Garden (All-in Tier)', 2000.00, NULL, 1, '2025-05-06 00:48:51', '2025-05-06 00:48:51');

-- Table structure for table `memberships`

DROP TABLE IF EXISTS `memberships`;

CREATE TABLE `memberships` (
  `membership_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `start_date` date NOT NULL,
  `expiry_date` date DEFAULT NULL,
  `membership_type_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `modified_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`membership_id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `membership_type_id` (`membership_type_id`),
  CONSTRAINT `CONSTRAINT_1` CHECK (`expiry_date` > `start_date` or `expiry_date` is null)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `memberships`
INSERT INTO `memberships` VALUES (1, 1, '2024-01-01', '2025-01-01', 1, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(2, 2, '2024-01-01', '2025-01-01', 2, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(3, 3, '2024-01-01', '2025-01-01', 3, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(4, 6, '2025-05-06', '2025-06-06', 5, '2025-05-06 19:10:19', '2025-05-06 19:23:00');

-- Table structure for table `notification_membership_targets`

DROP TABLE IF EXISTS `notification_membership_targets`;

CREATE TABLE `notification_membership_targets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `membership_type_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `membership_type_id` (`membership_type_id`),
  KEY `idx_notification_targets` (`notification_id`,`membership_type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `notification_membership_targets`
INSERT INTO `notification_membership_targets` VALUES (1, 1, 1),
(2, 1, 2),
(3, 1, 3),
(4, 2, 3);

-- Table structure for table `notification_recipients`

DROP TABLE IF EXISTS `notification_recipients`;

CREATE TABLE `notification_recipients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_id` (`notification_id`,`user_id`),
  KEY `user_id` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table structure for table `notification_templates`

DROP TABLE IF EXISTS `notification_templates`;

CREATE TABLE `notification_templates` (
  `template_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`template_id`),
  KEY `created_by` (`created_by`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table structure for table `notifications`

DROP TABLE IF EXISTS `notifications`;

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `created_by` int(11) NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `start_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`notification_id`),
  KEY `created_by` (`created_by`),
  KEY `idx_notifications_dates` (`start_date`,`expiry_date`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `notifications`
INSERT INTO `notifications` VALUES (1, 'Holiday Promo!', 'Everyone gets 10% off this week only!', 5, 1, '2025-05-06', '2025-05-13', '2025-05-06 00:48:51'),
(2, 'VIP Gift Incoming!', 'Hey VIP! Your mystery gift is on the way ?', 5, 1, '2025-05-06', '2025-06-05', '2025-05-06 00:48:51');

-- Table structure for table `order_details`

DROP TABLE IF EXISTS `order_details`;

CREATE TABLE `order_details` (
  `order_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `modified_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`order_detail_id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `order_details`
INSERT INTO `order_details` VALUES (1, 1, 2, 1, 6.49, '2025-05-06 04:31:45', '2025-05-06 04:31:45'),
(2, 1, 3, 1, 3.99, '2025-05-06 04:31:45', '2025-05-06 04:31:45'),
(3, 2, 2, 1, 6.49, '2025-05-06 04:49:11', '2025-05-06 04:49:11'),
(4, 3, 2, 87, 564.63, '2025-05-06 05:04:36', '2025-05-06 05:04:36');

-- Table structure for table `orders`

DROP TABLE IF EXISTS `orders`;

CREATE TABLE `orders` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `shipping_name` varchar(255) NOT NULL,
  `shipping_address` text NOT NULL,
  `shipping_phone` varchar(20) NOT NULL,
  `order_status` enum('Pending','Shipped','Delivered','Received','Cancelled','Returned') NOT NULL DEFAULT 'Pending',
  `total_price` decimal(10,2) NOT NULL,
  `delivery_method_id` int(11) NOT NULL,
  `discount` decimal(10,2) DEFAULT 0.00,
  `created_at` datetime DEFAULT current_timestamp(),
  `modified_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `viewed` tinyint(1) DEFAULT 0,
  `estimated_delivery` date DEFAULT NULL,
  `cancel_reason` text DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `customer_id` (`customer_id`),
  KEY `delivery_method_id` (`delivery_method_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `orders`
INSERT INTO `orders` VALUES (1, 6, '2025-05-06 04:31:45', 'monochrome', 'Cut-cot Pulilan Bulacan, Ellen\'s Food House Pulilan Bulacan Public Market\r\n0967 BENATIONAL RD. 9091 Villa Priscilla\r\nBULACAN', 9663787625, 'Shipped', 61.74, 1, 0.00, '2025-05-06 04:31:45', '2025-05-06 04:54:46', 1, NULL, NULL),
(2, 6, '2025-05-06 04:49:11', 'monochrome', 'Public Market', 9663787625, 'Shipped', 57.27, 1, 0.00, '2025-05-06 04:49:11', '2025-05-06 04:54:47', 1, NULL, NULL),
(3, 8, '2025-05-06 05:04:36', 'dfgh', 'dsrfgdfg', 222345333333, 'Pending', 682.39, 1, 0.00, '2025-05-06 05:04:36', '2025-05-08 05:26:46', 1, NULL, NULL);

-- Table structure for table `password_resets`

DROP TABLE IF EXISTS `password_resets`;

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  UNIQUE KEY `token` (`token`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table structure for table `payment_methods`

DROP TABLE IF EXISTS `payment_methods`;

CREATE TABLE `payment_methods` (
  `payment_method_id` int(11) NOT NULL AUTO_INCREMENT,
  `method_name` varchar(50) NOT NULL,
  PRIMARY KEY (`payment_method_id`),
  UNIQUE KEY `method_name` (`method_name`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `payment_methods`
INSERT INTO `payment_methods` VALUES (1, 'GCash'),
(2, 'Credit Card'),
(3, 'Debit Card'),
(5, 'Bank Transfer');

-- Table structure for table `payments`

DROP TABLE IF EXISTS `payments`;

CREATE TABLE `payments` (
  `payment_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `payment_method_id` int(11) NOT NULL,
  `payment_status` enum('Pending','Paid','Failed','Refunded') NOT NULL DEFAULT 'Pending',
  `created_at` datetime DEFAULT current_timestamp(),
  `modified_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`payment_id`),
  KEY `order_id` (`order_id`),
  KEY `payment_method_id` (`payment_method_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `payments`
INSERT INTO `payments` VALUES (1, 1, 4, 'Pending', '2025-05-06 04:31:45', '2025-05-06 04:31:45'),
(2, 2, 2, 'Pending', '2025-05-06 04:49:11', '2025-05-06 04:49:11'),
(3, 3, 1, 'Pending', '2025-05-06 05:04:36', '2025-05-06 05:04:36');

-- Table structure for table `product_images`

DROP TABLE IF EXISTS `product_images`;

CREATE TABLE `product_images` (
  `image_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `image_url` varchar(255) NOT NULL,
  `is_primary` tinyint(1) DEFAULT 0,
  `alt_text` varchar(100) DEFAULT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `modified_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`image_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM AUTO_INCREMENT=8 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `product_images`
INSERT INTO `product_images` VALUES (1, 5, '681abbeba122e_print.jpg', 1, 'AAC Art Print', 0, '2025-05-06 18:48:27', '2025-05-06 18:48:33'),
(2, 4, '681abc0fd1749_keychain.jpg', 1, 'AMC Keychain', 0, '2025-05-06 18:49:03', '2025-05-06 18:49:26'),
(3, 7, '681abc514316c_sticker.jpg', 1, 'Box 6 Sticker Pack', 0, '2025-05-06 18:50:09', '2025-05-06 18:50:14'),
(4, 12, '681abca7dd4b8_bracelet.jpg', 1, 'Dainty Wanderess Dreamcatcher', 0, '2025-05-06 18:51:35', '2025-05-06 18:51:40'),
(5, 3, '681abccf25dc8_Pin.jpg', 1, 'Bunny Enamel Pin', 0, '2025-05-06 18:52:15', '2025-05-06 18:52:22'),
(6, 10, '681abcf99bfac_journal.jpg', 1, 'DCS Journal', 0, '2025-05-06 18:52:57', '2025-05-06 18:53:00'),
(7, 9, '681abd18054e7_sticker2.jpg', 1, 'CH Sticker', 0, '2025-05-06 18:53:28', '2025-05-06 18:53:31');

-- Table structure for table `products`

DROP TABLE IF EXISTS `products`;

CREATE TABLE `products` (
  `product_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_name` varchar(255) NOT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL,
  `is_exclusive` tinyint(1) DEFAULT 0,
  `min_membership_level` int(11) DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `modified_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`product_id`),
  KEY `category_id` (`category_id`),
  KEY `min_membership_level` (`min_membership_level`)
) ENGINE=MyISAM AUTO_INCREMENT=19 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `products`
INSERT INTO `products` VALUES (1, 'Fluffy Bunny Plush', 'PLUSH001', 'Soft and cuddly bunny plush perfect for snuggles.', 14.99, 50, 1, 2, 1, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(2, 'Kawaii Bunny Notebook', 'NOTE001', 'Adorable bunny-themed notebook for journaling.', 6.49, 11, 0, NULL, 2, '2025-05-06 00:48:51', '2025-05-06 05:04:36'),
(3, 'Bunny Enamel Pin', 'PIN001', 'A cute enamel pin to decorate your bag or jacket.', 3.99, 74, 0, NULL, 3, '2025-05-06 00:48:51', '2025-05-06 04:31:45'),
(4, 'AMC Keychain', 'AMC-KCN', '<br />\r\n<b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>/home/vol9_8/infinityfree.com/if0_38911158/htdocs/pages/inventory_actions.php</b> on line <b>127</b><br />\r\n', 180.00, 5, 0, NULL, 4, '2025-05-06 00:48:51', '2025-05-06 18:49:03'),
(5, 'AAC Art Print', 'AAC-AP', '<br />\r\n<b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>/home/vol9_8/infinityfree.com/if0_38911158/htdocs/pages/inventory_actions.php</b> on line <b>127</b><br />\r\n', 69.00, 45, 0, NULL, 2, '2025-05-06 00:48:51', '2025-05-06 18:48:27'),
(6, 'ALI Button Pin', 'ALI-BPT', NULL, 160.00, 15, 0, NULL, 3, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(7, 'Box 6 Sticker Pack', 'BOX-6SP', '<br />\r\n<b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>/home/vol9_8/infinityfree.com/if0_38911158/htdocs/pages/inventory_actions.php</b> on line <b>127</b><br />\r\n', 120.00, 82, 0, NULL, 5, '2025-05-06 00:48:51', '2025-05-06 18:50:09'),
(8, 'CCC Bag', 'CCC-BG', '', 250.00, 12, 0, NULL, 6, '2025-05-06 00:48:51', '2025-05-06 18:46:52'),
(9, 'CH Sticker', 'CH-STC', '<br />\r\n<b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>/home/vol9_8/infinityfree.com/if0_38911158/htdocs/pages/inventory_actions.php</b> on line <b>127</b><br />\r\n', 50.00, 44, 0, NULL, 7, '2025-05-06 00:48:51', '2025-05-06 18:53:28'),
(10, 'DCS Journal', 'DCS-JRN', '<br />\r\n<b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>/home/vol9_8/infinityfree.com/if0_38911158/htdocs/pages/inventory_actions.php</b> on line <b>127</b><br />\r\n', 189.00, 20, 0, NULL, 8, '2025-05-06 00:48:51', '2025-05-06 18:52:57'),
(11, 'DNG Earrings Pair', 'DNG-ERP', NULL, 120.00, 18, 0, NULL, 9, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(12, 'Dainty Wanderess Dreamcatcher', 'DWN-DRC', '<br />\r\n<b>Deprecated</b>:  htmlspecialchars(): Passing null to parameter #1 ($string) of type string is deprecated in <b>/home/vol9_8/infinityfree.com/if0_38911158/htdocs/pages/inventory_actions.php</b> on line <b>127</b><br />\r\n', 300.00, 8, 0, NULL, 10, '2025-05-06 00:48:51', '2025-05-06 18:51:35'),
(13, 'DWS Badge', 'DWS-BDG', NULL, 35.00, 50, 0, NULL, 11, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(14, 'Elianne Art Card', 'ELA-CARD', NULL, 80.00, 35, 0, NULL, 12, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(15, 'elles_jewelry Ring', 'ELL-RNG', NULL, 99.00, 23, 0, NULL, 13, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(16, 'EWEKNITSS Knit', 'EWT-KNT', NULL, 150.00, 10, 0, NULL, 14, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(17, 'FLIMSY Pin', 'FLM-PIN', NULL, 70.00, 29, 0, NULL, 15, '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(18, 'Add-on Gift Wrap', 'ADD-GFT', NULL, 20.00, 100, 0, NULL, 1, '2025-05-06 00:48:51', '2025-05-06 00:48:51');

-- Table structure for table `return_details`

DROP TABLE IF EXISTS `return_details`;

CREATE TABLE `return_details` (
  `return_detail_id` int(11) NOT NULL AUTO_INCREMENT,
  `return_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`return_detail_id`),
  KEY `return_id` (`return_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table structure for table `return_items`

DROP TABLE IF EXISTS `return_items`;

CREATE TABLE `return_items` (
  `return_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `return_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL CHECK (`quantity` > 0),
  `unit_price` decimal(10,2) NOT NULL,
  `restocking_fee` decimal(10,2) DEFAULT 0.00,
  `reason` varchar(255) DEFAULT NULL,
  `item_condition` enum('New','Opened','Damaged') DEFAULT 'New',
  PRIMARY KEY (`return_item_id`),
  KEY `return_id` (`return_id`),
  KEY `product_id` (`product_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table structure for table `return_status_history`

DROP TABLE IF EXISTS `return_status_history`;

CREATE TABLE `return_status_history` (
  `history_id` int(11) NOT NULL AUTO_INCREMENT,
  `return_id` int(11) NOT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `changed_by` int(11) DEFAULT NULL,
  `change_date` datetime DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `return_id` (`return_id`),
  KEY `changed_by` (`changed_by`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table structure for table `returns`

DROP TABLE IF EXISTS `returns`;

CREATE TABLE `returns` (
  `return_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `is_archived` tinyint(1) NOT NULL,
  `archived_order_id` int(11) NOT NULL,
  `return_date` datetime NOT NULL DEFAULT current_timestamp(),
  `processed_by` int(11) DEFAULT NULL,
  `reason` varchar(255) DEFAULT NULL,
  `return_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `status_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`status_history`)),
  `last_status_update` datetime DEFAULT current_timestamp(),
  PRIMARY KEY (`return_id`),
  KEY `archived_order_id` (`archived_order_id`),
  KEY `processed_by` (`processed_by`),
  KEY `idx_return_status` (`return_status`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Table structure for table `roles`

DROP TABLE IF EXISTS `roles`;

CREATE TABLE `roles` (
  `role_id` int(11) NOT NULL AUTO_INCREMENT,
  `role_name` varchar(50) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `modified_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `roles`
INSERT INTO `roles` VALUES (1, 'Customer', '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(2, 'Member', '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(3, 'Staff', '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(4, 'Admin', '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(5, 'Super Admin', '2025-05-06 00:48:51', '2025-05-06 00:48:51'),
(6, 'Brand Partners', '2025-05-06 00:48:51', '2025-05-06 00:48:51');

-- Table structure for table `subscriptions_audit`

DROP TABLE IF EXISTS `subscriptions_audit`;

CREATE TABLE `subscriptions_audit` (
  `audit_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `membership_type_id` int(11) NOT NULL,
  `payment_amount` decimal(10,2) NOT NULL,
  `payment_date` datetime DEFAULT current_timestamp(),
  `payment_method` enum('credit_card','paypal','gcash','bank_transfer','other') NOT NULL,
  `payment_status` enum('pending','completed','failed','refunded') DEFAULT 'completed',
  `reference_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`audit_id`),
  KEY `user_id` (`user_id`),
  KEY `membership_type_id` (`membership_type_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `subscriptions_audit`
INSERT INTO `subscriptions_audit` VALUES (1, 6, 3, 500.00, '2025-05-06 19:10:19', 'gcash', 'completed', 'DEMO-0F5648D8', NULL),
(2, 6, 4, 750.00, '2025-05-06 19:10:29', 'gcash', 'completed', 'DEMO-958A3AC2', NULL),
(3, 6, 5, 1100.00, '2025-05-06 19:23:00', 'gcash', 'completed', 'DEMO-599F4F8F', NULL);

-- Table structure for table `transaction_logs`

DROP TABLE IF EXISTS `transaction_logs`;

CREATE TABLE `transaction_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('PENDING','COMPLETED','FAILED','ROLLED_BACK') NOT NULL,
  `transaction_date` datetime NOT NULL,
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) DEFAULT 0,
  PRIMARY KEY (`log_id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_status` (`status`),
  KEY `idx_transaction_date` (`transaction_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table structure for table `users`

DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(15) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role_id` int(11) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `modified_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login_at` datetime DEFAULT NULL,
  `last_logout_at` datetime DEFAULT NULL,
  `last_activity_at` datetime DEFAULT NULL,
  `activation_token` varchar(32) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 0,
  `oauth_provider` varchar(20) DEFAULT NULL,
  `oauth_id` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Dumping data for table `users`
INSERT INTO `users` VALUES (1, 'User Free', 'free@bunniwinkle.com', 'hashed_pw', NULL, NULL, 1, '2025-05-06 00:48:51', '2025-05-06 00:48:51', NULL, NULL, NULL, NULL, 1, NULL, NULL),
(2, 'User Premium', 'premium@bunniwinkle.com', 'hashed_pw', NULL, NULL, 1, '2025-05-06 00:48:51', '2025-05-06 00:48:51', NULL, NULL, NULL, NULL, 1, NULL, NULL),
(3, 'User VIP', 'vip@bunniwinkle.com', 'hashed_pw', NULL, NULL, 1, '2025-05-06 00:48:51', '2025-05-06 00:48:51', NULL, NULL, NULL, NULL, 1, NULL, NULL),
(4, 'Admin User', 'admin@bunniwinkle.com', 'hashed_pw', NULL, NULL, 2, '2025-05-06 00:48:51', '2025-05-06 00:48:51', NULL, NULL, NULL, NULL, 1, NULL, NULL),
(5, 'Super Admin', 'superadmin@bunniwinkle.com', 'hashed_pw', NULL, NULL, 4, '2025-05-06 00:48:51', '2025-05-06 00:48:51', NULL, NULL, NULL, NULL, 1, NULL, NULL),
(6, 'monochrome', 'allen@gmail.com', '$2y$10$u663vRmjQJIPSlHbi.zt7u9dydJbnWvBZnBerHMhKYyST7vsf0xB2', 9663787625, 'Public Market', 2, '2025-05-06 02:15:34', '2025-05-13 03:50:38', '2025-05-13 03:50:32', '2025-05-13 03:50:38', '2025-05-13 03:50:38', NULL, 1, NULL, NULL),
(7, 'rimue', 'monochrome@gmail.com', '$2y$10$7q.S8scJf/tBSJIlbeQq1OAhfk3uzwVFIQGrS8cbSnDtmiOwM0yrG', NULL, NULL, 5, '2025-05-06 04:50:05', '2025-05-13 22:14:14', '2025-05-13 22:13:20', '2025-05-13 04:00:43', '2025-05-13 22:14:14', NULL, 1, NULL, NULL),
(8, 'dfgh', 'jeremiahtkdtc@gmail.com', '$2y$10$Y4vxCiAelvJRoDf.5XO2PulY0/1lPkxRnGOb87rwzTiV3x49/tdUa', NULL, NULL, 1, '2025-05-06 05:03:36', '2025-05-06 05:04:39', '2025-05-06 05:03:47', NULL, '2025-05-06 05:04:39', NULL, 1, NULL, NULL),
(9, 'monochrome', 'allen1@gmail.com', '$2y$10$uMyHW1AqnijqHE3fHjIp1Osr9z3eN0MWdH9NrvZbAJpY2kzn0yCf6', NULL, NULL, 1, '2025-05-13 04:01:05', '2025-05-13 04:01:05', NULL, NULL, NULL, NULL, 1, NULL, NULL);

SET FOREIGN_KEY_CHECKS=1;
