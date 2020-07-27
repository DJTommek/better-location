CREATE DATABASE `better_location` DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
USE `better_location`;

CREATE TABLE `better_location_user` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_telegram_id` int(11) NOT NULL,
  `user_telegram_name` varchar(45) COLLATE utf8_bin DEFAULT NULL,
  `user_registered` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_last_update` datetime NOT NULL,
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_telegram_id` (`user_telegram_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

CREATE TABLE `better_location_chat` (
  `chat_id` int(11) NOT NULL AUTO_INCREMENT,
  `chat_telegram_id` bigint(8) NOT NULL,
  `chat_telegram_type` varchar(20) COLLATE utf8_bin NOT NULL,
  `chat_telegram_name` varchar(45) COLLATE utf8_bin DEFAULT NULL,
  `chat_registered` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `chat_last_update` datetime NOT NULL,
  PRIMARY KEY (`chat_id`),
  UNIQUE KEY `chat_telegram_id` (`chat_telegram_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COLLATE=utf8_bin;
