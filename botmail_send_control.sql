/*
Navicat MySQL Data Transfer

Source Server         : LOCALHOST
Source Server Version : 50505
Source Host           : localhost:3306
Source Database       : contratafashion_347

Target Server Type    : MYSQL
Target Server Version : 50505
File Encoding         : 65001

Date: 2026-02-06 15:21:42
*/

SET FOREIGN_KEY_CHECKS=0;

-- ----------------------------
-- Table structure for botmail_send_control
-- ----------------------------
DROP TABLE IF EXISTS `botmail_send_control`;
CREATE TABLE `botmail_send_control` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `to_email` varchar(255) NOT NULL,
  `email_category` varchar(100) NOT NULL,
  `fk_contrata_usuario` int(11) DEFAULT NULL,
  `status` enum('sent','failed') NOT NULL DEFAULT 'sent',
  `sent_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated` datetime DEFAULT NULL,
  `deleted` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_to_email` (`to_email`),
  KEY `idx_template_key` (`email_category`)
) ENGINE=InnoDB AUTO_INCREMENT=538 DEFAULT CHARSET=latin1;
