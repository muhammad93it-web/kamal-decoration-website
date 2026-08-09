-- ============================================================
--  دیکۆراتی ڕەوەند — RAWAND DECORATION
--  Database schema + Kurdish sample content
--  MySQL 5.7+ / MariaDB 10.3+  —  utf8mb4
--  Import via phpMyAdmin or the /install wizard.
--  NOTE: the admin user is created by the installer, not here.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

-- ───────────────────────── users & roles ─────────────────────────

DROP TABLE IF EXISTS `user_roles`;
DROP TABLE IF EXISTS `roles`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(190) NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `display_name` VARCHAR(120) NOT NULL DEFAULT '',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(50) NOT NULL,
  `label` VARCHAR(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_roles` (
  `user_id` INT UNSIGNED NOT NULL,
  `role_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`user_id`, `role_id`),
  CONSTRAINT `fk_ur_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ur_role` FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `roles` (`id`,`name`,`label`) VALUES
(1,'super_admin','بەڕێوەبەری باڵا'),
(2,'editor','دەستکار');

-- ───────────────────────── settings ─────────────────────────

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`setting_key`,`setting_value`) VALUES
('site_name','دیکۆراتی ڕەوەند'),
('site_name_latin','Rawand Decoration'),
('tagline','جوانکاری و دیکۆری ناوماڵ — ڕانیە'),
('logo_path',''),
('favicon_path',''),
('phone','0750 103 8181'),
('whatsapp','9647501038181'),
('email',''),
('address','ڕانیە — کوردستان'),
('maps_link','https://maps.google.com/?q=Ranya,Kurdistan'),
('working_hours','شەممە – پێنجشەممە: ٩ی بەیانی – ٧ی ئێوارە'),
('footer_about','دیکۆراتی ڕەوەند لە ڕانیە — هەموو پێداویستییەکانی جوانکاری و دیکۆری ناوماڵ: دیوارپۆش، WPC، کاغەزی دیوار، فۆمی سێ ڕەهەندی، مەرمەڕی PVC و زیاتر.'),
('hero_title','ماڵەکەت جوانتر بکە لەگەڵ دیکۆراتی ڕەوەند'),
('hero_subtitle','باشترین جۆرەکانی دیوارپۆش، WPC، کاغەزی دیوار و کەرەستەی دیکۆر — لە ڕانیە'),
('hero_btn1_text','بینینی بەرهەمەکان'),
('hero_btn1_url','products.php'),
('hero_btn2_text','پرۆژەکانمان'),
('hero_btn2_url','projects.php'),
('hero_btn3_text','پەیوەندی بە واتسئاپ'),
('seo_title','دیکۆراتی ڕەوەند — جوانکاری و دیکۆری ناوماڵ لە ڕانیە'),
('seo_description','دیکۆراتی ڕەوەند لە ڕانیە: دیوارپۆش، WPC، کاغەزی دیوار، فۆمی سێ ڕەهەندی، مەرمەڕی PVC، سەقفی هەڵواسراو و هەموو کەرەستەکانی دیکۆر. پەیوەندی: 0750 103 8181'),
('og_image',''),
('currency_symbol','د.ع'),
('show_prices','1'),
('maintenance','0'),
('posts_per_page','12'),
('site_url',''),
('color_accent','#46549B'),
('color_dark','#232120'),
('color_bg','#FAF7F2'),
('announce_text','');

-- ───────────────────────── social links ─────────────────────────

DROP TABLE IF EXISTS `social_links`;
CREATE TABLE `social_links` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(80) NOT NULL,
  `platform` VARCHAR(40) NOT NULL DEFAULT 'link',
  `url` VARCHAR(500) NOT NULL DEFAULT '',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `show_header` TINYINT(1) NOT NULL DEFAULT 0,
  `show_footer` TINYINT(1) NOT NULL DEFAULT 1,
  `show_contact` TINYINT(1) NOT NULL DEFAULT 1,
  `show_floating` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `social_links` (`name`,`platform`,`url`,`sort_order`,`is_active`,`show_header`,`show_footer`,`show_contact`,`show_floating`) VALUES
('واتسئاپ','whatsapp','https://wa.me/9647501038181',1,1,1,1,1,1),
('پەیوەندی تەلەفۆنی','phone','tel:+9647501038181',2,1,0,1,1,0),
('شوێنمان لە نەخشە','maps','https://maps.google.com/?q=Ranya,Kurdistan',3,1,0,1,1,0),
('فەیسبووک','facebook','',4,0,0,1,1,0),
('ئینستاگرام','instagram','',5,0,0,1,1,0),
('تیکتۆک','tiktok','',6,0,0,1,1,0);

-- ───────────────────────── categories ─────────────────────────

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `type` ENUM('product','project','post') NOT NULL DEFAULT 'product',
  `description` TEXT NULL,
  `image` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_categories_slug` (`slug`),
  KEY `idx_categories_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `categories` (`id`,`name`,`slug`,`type`,`description`,`image`,`sort_order`,`is_featured`,`is_active`) VALUES
(1,'WPC و پانێڵی دیوار','wpc','product','پانێڵی WPC بە جۆر و ڕەنگی جیاواز — بەرگری بەرز و ڕوخساری داری سروشتی','media/cat-wpc.jpg',1,1,1),
(2,'دیوارپۆش','wall-covering','product','دیوارپۆشی مۆدێرن بۆ ژووری دانیشتن، نووستن و شوێنە بازرگانییەکان','media/cat-wallcover.jpg',2,1,1),
(3,'کاغەزی دیوار','wallpaper','product','کاغەزی دیوار بە دیزاینی کلاسیک و مۆدێرن، جۆری دەرەکی و ناوخۆیی','media/cat-wallpaper.jpg',3,1,1),
(4,'مەرمەڕی PVC','pvc-marble','product','شیتی مەرمەڕی PVC — دیمەنی مەرمەڕی ڕاستەقینە بە نرخێکی گونجاو','media/cat-marble.jpg',4,1,1),
(5,'فۆمی سێ ڕەهەندی','foam-3d','product','فۆمی دیوار ٣D — سووک، جوان و ئاسان بۆ دانان','media/cat-foam.jpg',5,1,1),
(6,'سەقفی هەڵواسراو','ceilings','product','سەقفی جیبسبۆرد و PVC لەگەڵ ڕووناکی شاراوە','media/cat-ceiling.jpg',6,1,1),
(7,'تەختە و پانێڵ','wood-panels','product','تەختەی دیکۆر و پانێڵی داری بەدیل بۆ دیوار و زەوی','media/cat-panel.jpg',7,0,1),
(8,'دیکۆری ناوماڵ','home-decor','product','کەرەستە و ئامرازەکانی جوانکاری ناوماڵ','media/cat-interior.jpg',8,0,1),
(9,'مۆڵدینگ و چوارچێوە','moulding','product','مۆڵدینگی دیوار و سەقف بە ڕەنگی سپی و زێڕی','products/prod-moulding.jpg',9,0,1),
(10,'کەرەستەی تر','other-materials','product','هەموو کەرەستەکانی تری دیکۆر و جوانکاری','media/hero-ceiling.jpg',10,0,1),
(11,'نیشتەجێبوون','residential','project','پرۆژەکانی ماڵ و ڤیلا',NULL,1,0,1),
(12,'بازرگانی','commercial','project','پرۆژەکانی کافێ، ئۆفیس و فرۆشگا',NULL,2,0,1),
(13,'هەواڵ','news','post','نوێترین هەواڵەکانی دیکۆراتی ڕەوەند',NULL,1,0,1),
(14,'ڕێنمایی','guides','post','ڕێنمایی و ئامۆژگاری بۆ دیکۆری ماڵ',NULL,2,0,1);

-- ───────────────────────── palettes & shades ─────────────────────────

DROP TABLE IF EXISTS `palette_shades`;
DROP TABLE IF EXISTS `palettes`;

CREATE TABLE `palettes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `code` VARCHAR(40) NOT NULL,
  `family` VARCHAR(80) NULL,
  `description` TEXT NULL,
  `cover_image` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_palettes_slug` (`slug`),
  UNIQUE KEY `uq_palettes_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `palette_shades` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `palette_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(180) NOT NULL,
  `code` VARCHAR(40) NOT NULL,
  `hex_color` CHAR(7) NOT NULL DEFAULT '#CCCCCC',
  `position` INT NOT NULL DEFAULT 0,
  `image` VARCHAR(255) NULL,
  `qr_path` VARCHAR(255) NULL,
  `barcode_path` VARCHAR(255) NULL,
  `notes` TEXT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_shades_slug` (`slug`),
  UNIQUE KEY `uq_shades_code` (`code`),
  KEY `idx_shades_palette` (`palette_id`),
  CONSTRAINT `fk_shades_palette` FOREIGN KEY (`palette_id`) REFERENCES `palettes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `palettes` (`id`,`name`,`slug`,`code`,`family`,`description`,`cover_image`,`sort_order`,`is_featured`,`is_active`) VALUES
(1,'قاوەیی داریین','brown-woods','KD-P100','قاوەیی','پاڵێتی ڕەنگە قاوەییە دارییەکان — لە ئێسپرێسۆی تاریکەوە تا کرێمی داری ڕووناک. گونجاوە بۆ WPC، دیوارپۆش و تەختەی دیکۆر.','palettes/pal-brown.jpg',1,1,1),
(2,'بێژی گەرم','warm-beige','KD-P200','بێژ','پاڵێتی بێژە گەرمەکان — هێمنی و گەرمی بۆ ژووری دانیشتن و نووستن.','palettes/pal-beige.jpg',2,1,1),
(3,'خۆڵەمێشی مۆدێرن','modern-gray','KD-P300','خۆڵەمێشی','پاڵێتی خۆڵەمێشییە مۆدێرنەکان — بۆ دیزاینی هاوچەرخ و شوێنە بازرگانییەکان.','palettes/pal-gray.jpg',3,1,1);

INSERT INTO `palette_shades` (`palette_id`,`name`,`slug`,`code`,`hex_color`,`position`,`is_active`) VALUES
(1,'ئێسپرێسۆ','espresso','KD-S101','#2B1B10',1,1),
(1,'شەکلاتی تاریک','dark-chocolate','KD-S102','#3A2417',2,1),
(1,'گوێزی تاریک','dark-walnut','KD-S103','#4C2F1D',3,1),
(1,'قاوەیی کلاسیک','classic-brown','KD-S104','#5F3D26',4,1),
(1,'کارامێل','caramel','KD-S105','#7A5233',5,1),
(1,'بەلووطی','oak','KD-S106','#96683F',6,1),
(1,'خورمایی کاڵ','light-date','KD-S107','#B98A5E',7,1),
(1,'کرێمی داریین','wood-cream','KD-S108','#D9BC96',8,1),
(2,'قاوەیی خاکی','earth-brown','KD-S201','#6B5744',1,1),
(2,'بێژی تاریک','dark-beige','KD-S202','#8A7458',2,1),
(2,'بێژی ڕەسەن','classic-beige','KD-S203','#A98F6F',3,1),
(2,'بێژی کاڵ','soft-beige','KD-S204','#C4AB8A',4,1),
(2,'کرێمی','cream','KD-S205','#DCC7A9',5,1),
(2,'سپیی مرواری','pearl-white','KD-S206','#F0E4D0',6,1),
(3,'ڕەشی زوخاڵی','charcoal-black','KD-S301','#1C1C1E',1,1),
(3,'خۆڵەمێشی تاریک','dark-gray','KD-S302','#3A3A3C',2,1),
(3,'خۆڵەمێشی مامناوەند','mid-gray','KD-S303','#6E6E70',3,1),
(3,'خۆڵەمێشی بەردی','stone-gray','KD-S304','#9C9A96',4,1),
(3,'خۆڵەمێشی کاڵ','light-gray','KD-S305','#C7C4BF',5,1),
(3,'سپیی خۆڵەمێشی','off-white','KD-S306','#E8E6E3',6,1);

-- ───────────────────────── products ─────────────────────────

DROP TABLE IF EXISTS `product_palettes`;
DROP TABLE IF EXISTS `product_images`;
DROP TABLE IF EXISTS `products`;

CREATE TABLE `products` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NULL,
  `name` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `code` VARCHAR(40) NULL,
  `short_desc` VARCHAR(500) NULL,
  `description` MEDIUMTEXT NULL,
  `specifications` TEXT NULL,
  `price` DECIMAL(12,0) NULL,
  `unit` VARCHAR(60) NULL,
  `main_image` VARCHAR(255) NULL,
  `qr_path` VARCHAR(255) NULL,
  `barcode_path` VARCHAR(255) NULL,
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `views` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_products_slug` (`slug`),
  UNIQUE KEY `uq_products_code` (`code`),
  KEY `idx_products_category` (`category_id`),
  KEY `idx_products_featured` (`is_featured`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT UNSIGNED NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pimages_product` (`product_id`),
  CONSTRAINT `fk_pimages_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `product_palettes` (
  `product_id` INT UNSIGNED NOT NULL,
  `palette_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`product_id`,`palette_id`),
  CONSTRAINT `fk_pp_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pp_palette` FOREIGN KEY (`palette_id`) REFERENCES `palettes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `products` (`id`,`category_id`,`name`,`slug`,`code`,`short_desc`,`description`,`specifications`,`price`,`unit`,`main_image`,`is_available`,`is_featured`,`is_active`,`sort_order`) VALUES
(1,1,'پانێڵی WPC گوێزی','wpc-panel-walnut','KD-PR01','پانێڵی WPC بە ڕەنگی گوێزی تاریک — بەرگری بەرز بەرامبەر شێ و گەرمی','پانێڵی WPC (تەختەی پلاستیکی داریین) یەکێکە لە باشترین بژاردەکان بۆ دیوارپۆشکردنی مۆدێرن. ئەم جۆرە بەرگری بەرزی هەیە بەرامبەر شێ، گەرمی و مشەخۆر، و دیمەنێکی داری سروشتی دەبەخشێتە دیوارەکانت.\n\nگونجاوە بۆ ژووری دانیشتن، ژووری نووستن، کۆرidor و شوێنە بازرگانییەکان. دانانی ئاسانە و پاککردنەوەی تەنها بە پەڕۆیەکی تەڕ دەبێت.','درێژی: 290 سم\nپانی: 17 سم\nئەستووری: 9 ملم\nماددە: WPC (دار + پلاستیک)\nبەرگری شێ: بەرزە\nڕەنگ: گوێزی تاریک',12500,'بۆ هەر پارچە','products/prod-wpc-panel.jpg',1,1,1,1),
(2,3,'کاغەزی دیواری دەمەسک','wallpaper-damask-gold','KD-PR02','کاغەزی دیواری کلاسیک بە نەخشی دەمەسکی زێڕی لەسەر بێژی گەرم','کاغەزی دیواری دەمەسک بە نەخشێکی کلاسیکی جوان کە شکۆمەندی دەبەخشێتە هەر ژوورێک. ڕەنگە زێڕییەکەی لەگەڵ زۆربەی ڕەنگی مۆبیلیا دەگونجێت.\n\nجۆری ئاڵمانی، بەرگری بەرز بەرامبەر تیشکی خۆر و ڕەنگ نەگۆڕان. شوشتنی ئاسانە.','درێژی ڕۆڵ: 10 مەتر\nپانی: 53 سم\nجۆر: ڤینیل لەسەر کاغەز\nشوشتن: بەڵێ\nنەخش: دەمەسکی کلاسیک',18000,'بۆ هەر ڕۆڵ','products/prod-wallpaper.jpg',1,1,1,2),
(3,5,'فۆمی دیواری ٣D سپی','foam-3d-white','KD-PR03','فۆمی سێ ڕەهەندی بە شێوەی خشت — سووک و ئاسان بۆ دانان','فۆمی دیواری سێ ڕەهەندی بە دیزاینی خشتی سپی. زۆر سووکە و بە ئاسانی بە چەسپ دەلکێت بە دیوارەوە بەبێ پێویستی بە وەستا.\n\nدژە دەنگە و عایەقی گەرمییە. گونجاوە بۆ ژووری نووستن، ژووری منداڵان و پشتی تەلەفزیۆن.','قەبارە: 70×77 سم\nئەستووری: 8 ملم\nماددە: فۆمی XPE\nڕەنگ: سپی\nدانان: خۆلکێن (چەسپدار)',3500,'بۆ هەر پارچە','products/prod-foam3d.jpg',1,1,1,3),
(4,4,'شیتی مەرمەڕی PVC کرێمی','pvc-marble-cream','KD-PR04','شیتی مەرمەڕی PVC بە دیمەنی مەرمەڕی سروشتی — بۆ دیوار و حەمام','شیتی مەرمەڕی PVC دیمەنی مەرمەڕی ڕاستەقینەت پێدەدات بە نرخێکی زۆر گونجاوتر. ڕووکەشێکی بریسکەداری هەیە و بەرگری تەواوی هەیە بەرامبەر ئاو.\n\nگونجاوە بۆ حەمام، چێشتخانە، دیواری TV و شوێنە بازرگانییەکان. دانانی خێرایە و درزی نییە.','قەبارە: 122×244 سم\nئەستووری: 3 ملم\nماددە: PVC + مەرمەڕی ورد\nبەرگری ئاو: ١٠٠٪\nڕووکەش: بریسکەدار (UV)',22000,'بۆ هەر شیت','products/prod-marble-sheet.jpg',1,1,1,4),
(5,7,'تەختەی داری بەدیل','wood-alternative-board','KD-PR05','تەختەی کۆمپۆزیت بۆ دەرەوە و ناوەوە — بەرگری ئاو و خۆر','تەختەی داری بەدیل (کۆمپۆزیت) بۆ زەوی بالکۆن، باخچە و دیواری دەرەوە. هیچ بۆیاخ و چاکردنەوەیەکی ناوێت و تەمەنی درێژە.\n\nلە بەرامبەر ئاو، خۆر و گۆڕانی کەشوهەوا بەرگری تەواوی هەیە.','درێژی: 220 سم\nپانی: 14 سم\nماددە: WPC کۆمپۆزیت\nبەکارهێنان: دەرەوە و ناوەوە\nڕەنگ: قاوەیی داریین',NULL,NULL,'products/prod-wood-alt.jpg',1,0,1,5),
(6,9,'مۆڵدینگی زێڕی','gold-moulding','KD-PR06','مۆڵدینگی دیوار بە هێڵی زێڕی — بۆ دیزاینی کلاسیک و نیۆکلاسیک','مۆڵدینگی پۆلیستایرین بە ڕەنگی سپی و هێڵی زێڕی بۆ جوانکاری دیوار و سەقف. زۆر سووکە و بە ئاسانی دەبڕدرێت و دەلکێت.\n\nگونجاوە بۆ دیزاینی نیۆکلاسیک کە ئێستا زۆر بەناوبانگە لە کوردستان.','درێژی: 240 سم\nپانی: 8 سم\nماددە: پۆلیستایرین PS\nڕەنگ: سپی + زێڕی\nدانان: چەسپی تایبەت',NULL,NULL,'products/prod-moulding.jpg',1,0,1,6);

INSERT INTO `product_images` (`product_id`,`image`,`sort_order`) VALUES
(1,'media/cat-wpc.jpg',1),
(1,'media/hero-living.jpg',2),
(2,'media/cat-wallpaper.jpg',1),
(3,'media/cat-foam.jpg',1),
(4,'media/cat-marble.jpg',1),
(5,'media/cat-panel.jpg',1),
(6,'media/cat-interior.jpg',1);

INSERT INTO `product_palettes` (`product_id`,`palette_id`) VALUES
(1,1),(2,2),(4,2),(5,1),(6,2),(4,3);

-- ───────────────────────── projects ─────────────────────────

DROP TABLE IF EXISTS `project_images`;
DROP TABLE IF EXISTS `projects`;

CREATE TABLE `projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NULL,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `client_name` VARCHAR(150) NULL,
  `location` VARCHAR(150) NULL,
  `completed_at` DATE NULL,
  `description` MEDIUMTEXT NULL,
  `main_image` VARCHAR(255) NULL,
  `before_image` VARCHAR(255) NULL,
  `after_image` VARCHAR(255) NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `views` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_projects_slug` (`slug`),
  KEY `idx_projects_category` (`category_id`),
  CONSTRAINT `fk_projects_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `project_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `caption` VARCHAR(255) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_prjimages_project` (`project_id`),
  CONSTRAINT `fk_prjimages_project` FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `projects` (`id`,`category_id`,`title`,`slug`,`client_name`,`location`,`completed_at`,`description`,`main_image`,`before_image`,`after_image`,`is_featured`,`is_active`,`sort_order`) VALUES
(1,11,'دیکۆری تەواوی هۆڵی ڤیلا','villa-hall-ranya','خێزانی بەڕێز','ڕانیە','2026-05-20','لەم پرۆژەیەدا هۆڵی سەرەکی ڤیلایەک بە تەواوی نوێکرایەوە: دیوارەکان بە پانێڵی WPC گوێزی پۆشران، سەقفی جیبسبۆرد لەگەڵ ڕووناکی شاراوە دانرا، و زەوییەکە بە مەرمەڕی PVC تەواو کرا.\n\nکارەکە لە ماوەی ١٢ ڕۆژدا تەواو بوو و خاوەن ماڵ زۆر دڵخۆش بوو بە ئەنجامەکە.','projects/proj-villa.jpg','projects/proj-before.jpg','projects/proj-after.jpg',1,1,1),
(2,12,'دیکۆری کافێی مۆدێرن','modern-cafe-ranya','کافێی ناوەند','ڕانیە — شەقامی سەرەکی','2026-03-14','دیزاین و جێبەجێکردنی تەواوی دیکۆری کافێیەکی مۆدێرن: دیواری تەختەی داری دەنگ کوژێنەوە، ڕووناکی هەڵواسراو و ڕەنگە گەرمەکان.\n\nئامانج ئەوە بوو شوێنێکی ئارام و جوان دروست بکرێت بۆ دانیشتنی درێژخایەن.','projects/proj-cafe.jpg',NULL,NULL,1,1,2),
(3,12,'پێشوازی ئۆفیسی کۆمپانیا','office-reception','کۆمپانیای بازرگانی','ڕانیە','2026-01-30','دیکۆری بەشی پێشوازی ئۆفیسێکی بازرگانی: دیواری پانێڵی زوخاڵی لەگەڵ مۆڵدینگی زێڕی، مێزی داری و ڕووناکی خاڵی.\n\nدیزاینەکە وا کراوە یەکەم بەرچاوی میوان کاریگەری بەهێز بێت.','projects/proj-office.jpg',NULL,NULL,1,1,3);

INSERT INTO `project_images` (`project_id`,`image`,`caption`,`sort_order`) VALUES
(1,'projects/proj-villa.jpg','هۆڵی سەرەکی دوای تەواوبوون',1),
(1,'projects/proj-after.jpg','دیواری WPC و ڕووناکی شاراوە',2),
(1,'media/hero-living.jpg','گۆشەی دانیشتن',3),
(2,'projects/proj-cafe.jpg','دیمەنی گشتی کافێ',1),
(2,'media/cat-panel.jpg','دیواری تەختەی دەنگ کوژێنەوە',2),
(3,'projects/proj-office.jpg','بەشی پێشوازی',1),
(3,'media/cat-interior.jpg','گۆشەی چاوەڕوانی',2);

-- ───────────────────────── posts ─────────────────────────

DROP TABLE IF EXISTS `post_images`;
DROP TABLE IF EXISTS `posts`;

CREATE TABLE `posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NULL,
  `author_id` INT UNSIGNED NULL,
  `title` VARCHAR(200) NOT NULL,
  `slug` VARCHAR(220) NOT NULL,
  `excerpt` VARCHAR(500) NULL,
  `body` MEDIUMTEXT NULL,
  `cover_image` VARCHAR(255) NULL,
  `is_published` TINYINT(1) NOT NULL DEFAULT 1,
  `published_at` DATETIME NULL,
  `views` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_posts_slug` (`slug`),
  KEY `idx_posts_category` (`category_id`),
  KEY `idx_posts_published` (`is_published`,`published_at`),
  CONSTRAINT `fk_posts_category` FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_posts_author` FOREIGN KEY (`author_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `post_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` INT UNSIGNED NOT NULL,
  `image` VARCHAR(255) NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_postimages_post` (`post_id`),
  CONSTRAINT `fk_postimages_post` FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `posts` (`id`,`category_id`,`author_id`,`title`,`slug`,`excerpt`,`body`,`cover_image`,`is_published`,`published_at`) VALUES
(1,13,NULL,'مۆدێلە نوێیەکانی WPC گەیشتن','new-wpc-models-arrived','مۆدێلە نوێیەکانی پانێڵی WPC بە ڕەنگ و نەخشی جیاواز گەیشتنە فرۆشگاکەمان لە ڕانیە.','<p>بە دڵخۆشییەوە ڕایدەگەیەنین کە کۆمەڵێک مۆدێلی نوێی پانێڵی WPC گەیشتنە فرۆشگاکەمان. ئەم مۆدێلانە بە ڕەنگە داری و مۆدێرنەکان بەردەستن و بۆ هەموو شوێنێک دەگونجێن.</p><p>سەردانمان بکەن بۆ بینینی نموونەکان لە نزیکەوە، یان لە ڕێگەی واتسئاپ داوای وێنە و نرخ بکەن.</p><p><strong>ژمارەی پەیوەندی: 0750 103 8181</strong></p>','media/cat-wpc.jpg',1,'2026-07-28 10:00:00'),
(2,14,NULL,'٥ هۆکار بۆ هەڵبژاردنی فۆمی ٣D','five-reasons-3d-foam','بۆچی فۆمی سێ ڕەهەندی باشترین بژاردەیە بۆ ژووری نووستن و ژووری منداڵان؟','<p>فۆمی دیواری سێ ڕەهەندی لەم ساڵانەدا زۆر بەناوبانگ بووە. ئەمانە گرنگترین هۆکارەکانن:</p><ol><li><strong>سووکە و ئاسانە</strong> — بەبێ وەستا خۆت دەتوانیت دایبنێیت.</li><li><strong>دژە دەنگە</strong> — ژوورەکەت ئارامتر دەکات.</li><li><strong>عایەقی گەرمییە</strong> — لە زستان گەرمی ڕادەگرێت.</li><li><strong>پارێزەرە بۆ منداڵان</strong> — نەرمە و لە پێکدادان ناترسێیت.</li><li><strong>نرخی گونجاوە</strong> — لە چاو جۆرەکانی تر هەرزانترە.</li></ol><p>بۆ بینینی جۆرەکان سەردانی بەشی <a href="products.php">بەرهەمەکان</a> بکە.</p>','media/cat-foam.jpg',1,'2026-07-15 12:00:00'),
(3,13,NULL,'پێش و دوای: گۆڕانکارییەکی سەرسوڕهێنەر','before-after-transformation','بینینی جیاوازی نێوان ژوورێکی سادە و دوای دیکۆرکردنی تەواو — وێنەکان خۆیان قسە دەکەن.','<p>زۆر جار کڕیارەکانمان دەپرسن: «ئایا دیکۆر شایەنی ئەو تێچووەیە؟» — وەڵامەکە لە وێنەکاندایە.</p><p>لەم پرۆژەیەدا ژوورێکی سادەی بێ ڕەنگ گۆڕدرا بۆ شوێنێکی شکۆدار بە پانێڵی WPC، سەقفی نوێ و ڕووناکی شاراوە.</p><p>بۆ بینینی وردەکاری زیاتر سەیری بەشی <a href="projects.php">پرۆژەکان</a> بکە، یان بە واتسئاپ پەیوەندیمان پێوە بکە بۆ ڕاوێژکاری بێ بەرامبەر.</p>','projects/proj-after.jpg',1,'2026-06-20 09:30:00');

INSERT INTO `post_images` (`post_id`,`image`,`sort_order`) VALUES
(3,'projects/proj-before.jpg',1),
(3,'projects/proj-after.jpg',2);

-- ───────────────────────── sliders (hero) ─────────────────────────

DROP TABLE IF EXISTS `sliders`;
CREATE TABLE `sliders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NULL,
  `subtitle` VARCHAR(300) NULL,
  `image` VARCHAR(255) NOT NULL,
  `button_text` VARCHAR(100) NULL,
  `button_url` VARCHAR(300) NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `starts_at` DATETIME NULL,
  `ends_at` DATETIME NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `sliders` (`title`,`subtitle`,`image`,`button_text`,`button_url`,`sort_order`,`is_active`) VALUES
('ماڵەکەت جوانتر بکە','باشترین جۆرەکانی دیوارپۆش و WPC بۆ ژووری دانیشتن','media/hero-01.jpg','بینینی بەرهەمەکان','products.php',1,1),
('ژووری نووستنی خەونەکانت','پانێڵی ٣D و سەرتەختی ناسک بە دیزاینی هەمەجۆر','media/hero-02.jpg','پاڵێتی ڕەنگەکان','palettes.php',2,1),
('سەقفی مۆدێرن و ڕووناکی شاراوە','دیزاین و جێبەجێکردن بە دەستی وەستای شارەزا','media/hero-03.jpg','پرۆژەکانمان','projects.php',3,1),
('حەمامی خەونەکانت','ڕووپۆشی مەڕمەڕی PVC — جوانی مەڕمەڕ بە نرخێکی گونجاو','media/hero-04.jpg','بینینی بەرهەمەکان','products.php',4,1),
('کاغەزی دیوار بە هەموو جۆرەکان','سەدان دیزاینی جیاواز بۆ هەر ژوورێکی ماڵەکەت','media/hero-05.jpg','بینینی بەرهەمەکان','products.php',5,1),
('دیواری تەلەفزیۆن بە ستایلی نوێ','مەڕمەڕ و دار — دیزاینی تایبەت بە ماڵەکەت','media/hero-06.jpg','پرۆژەکانمان','projects.php',6,1),
('ژووری نانخواردنی شیک','دیوارپۆشی کلاسیک و ڕووناکی گەرم','media/hero-07.jpg','گەلەری','gallery.php',7,1),
('ئۆفیس و شوێنی کار','دیزاینێکی جددی و ئارام بۆ کارکردن','media/hero-08.jpg','پەیوەندیمان پێوە بکە','contact.php',8,1),
('ڕاڕەو و مەدخەلی ماڵ','یەکەم شوێنی بەرچاو — با جوانترین بێت','media/hero-09.jpg','گەلەری','gallery.php',9,1),
('ژووری منداڵان بە خەیاڵەوە','دیزاینی خۆش و ڕەنگی ئارام بۆ منداڵەکانت','media/hero-10.jpg','پەیوەندیمان پێوە بکە','contact.php',10,1);

-- ───────────────────────── media library ─────────────────────────

DROP TABLE IF EXISTS `media`;
CREATE TABLE `media` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(255) NOT NULL,
  `path` VARCHAR(255) NOT NULL,
  `thumb_path` VARCHAR(255) NULL,
  `mime` VARCHAR(100) NOT NULL DEFAULT '',
  `size_bytes` INT UNSIGNED NOT NULL DEFAULT 0,
  `width` INT NULL,
  `height` INT NULL,
  `alt_text` VARCHAR(255) NULL,
  `uploaded_by` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_media_created` (`created_at`),
  CONSTRAINT `fk_media_user` FOREIGN KEY (`uploaded_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────── testimonials ─────────────────────────

DROP TABLE IF EXISTS `testimonials`;
CREATE TABLE `testimonials` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `location` VARCHAR(120) NULL,
  `quote` TEXT NOT NULL,
  `rating` TINYINT NOT NULL DEFAULT 5,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `testimonials` (`name`,`location`,`quote`,`rating`,`is_active`,`sort_order`) VALUES
('کاک هێمن','ڕانیە','هۆڵی ماڵەکەمان بە WPC پۆشرا، کارەکە زۆر خاوێن و خێرا بوو. دەستخۆشی لە تیمی ڕەوەند دەکەم.',5,1,1),
('خاتوو شنیار','قەڵادزێ','کاغەزی دیواری ژووری نووستنم لێرە کڕی، جۆرەکەی زۆر باشە و نرخەکەی گونجاو بوو.',5,1,2),
('کاک ڕێبوار','ڕانیە','بۆ کافێکەم دیکۆری تەواویان بۆ کردم. مشتەرییەکانم هەمیشە باسی جوانی شوێنەکە دەکەن.',5,1,3),
('کاک ئارام','حاجیاوا','فۆمی ٣D بۆ ژووری منداڵەکانم دانا، خۆم دامنا بە ئاسانی. پێشنیاری دەکەم بۆ هەموو کەس.',4,1,4);

-- ───────────────────────── contact messages ─────────────────────────

DROP TABLE IF EXISTS `contact_messages`;
CREATE TABLE `contact_messages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(40) NOT NULL DEFAULT '',
  `email` VARCHAR(190) NULL,
  `subject` VARCHAR(200) NULL,
  `message` TEXT NOT NULL,
  `ip` VARCHAR(45) NOT NULL DEFAULT '',
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_messages_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ───────────────────────── logs ─────────────────────────

DROP TABLE IF EXISTS `searches`;
CREATE TABLE `searches` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `query` VARCHAR(300) NOT NULL,
  `normalized` VARCHAR(300) NOT NULL DEFAULT '',
  `results` INT NOT NULL DEFAULT 0,
  `ip` VARCHAR(45) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_searches_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `scan_logs`;
CREATE TABLE `scan_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` VARCHAR(60) NOT NULL,
  `target_type` VARCHAR(20) NULL,
  `target_id` INT UNSIGNED NULL,
  `found` TINYINT(1) NOT NULL DEFAULT 0,
  `ip` VARCHAR(45) NOT NULL DEFAULT '',
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_scans_code` (`code`),
  KEY `idx_scans_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `page_views`;
CREATE TABLE `page_views` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `path` VARCHAR(300) NOT NULL,
  `page_type` VARCHAR(30) NULL,
  `target_id` INT UNSIGNED NULL,
  `ip` VARCHAR(45) NOT NULL DEFAULT '',
  `user_agent` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_views_created` (`created_at`),
  KEY `idx_views_type` (`page_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `action` VARCHAR(60) NOT NULL,
  `entity` VARCHAR(60) NULL,
  `entity_id` INT UNSIGNED NULL,
  `details` VARCHAR(500) NULL,
  `ip` VARCHAR(45) NOT NULL DEFAULT '',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_activity_created` (`created_at`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `login_attempts`;
CREATE TABLE `login_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL DEFAULT '',
  `ip` VARCHAR(45) NOT NULL DEFAULT '',
  `success` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_attempts_ip` (`ip`,`created_at`),
  KEY `idx_attempts_user` (`username`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
--  کۆتایی — End of file
-- ============================================================
