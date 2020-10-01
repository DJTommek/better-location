CREATE DATABASE `better_location` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin;
USE `better_location`;

CREATE TABLE `better_location_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_telegram_id` int(11) NOT NULL,
  `user_telegram_name` varchar(45) COLLATE utf8mb4_bin DEFAULT NULL,
  `user_registered` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_last_update` datetime NOT NULL,
  `user_location_lat` DOUBLE(10,6) NULL,
  `user_location_lon` DOUBLE(10,6) NULL,
  `user_location_last_update` DATETIME NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_telegram_id` (`user_telegram_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE `better_location_chat` (
  `chat_id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_telegram_id` bigint(8) NOT NULL,
  `chat_telegram_type` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `chat_telegram_name` varchar(45) COLLATE utf8mb4_bin DEFAULT NULL,
  `chat_registered` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `chat_last_update` datetime NOT NULL,
  PRIMARY KEY (`chat_id`),
  UNIQUE KEY `chat_telegram_id` (`chat_telegram_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

CREATE TABLE IF NOT EXISTS `better_location_favourites` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `status` tinyint NOT NULL,
  `lat` double(10,6) NOT NULL,
  `lon` double(10,6) NOT NULL,
  `title` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`user_id`,`lat`,`lon`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;
ALTER TABLE `better_location_favourites` ADD CONSTRAINT `favourites.user_id` FOREIGN KEY (`user_id`) REFERENCES `better_location_user` (`user_id`);
