-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- 主機： localhost
-- 產生時間： 2022 年 10 月 24 日 06:42
-- 伺服器版本： 8.0.26
-- PHP 版本： 7.4.32

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫： `fhmemberdb`
--

-- --------------------------------------------------------

--
-- 資料表結構 `accesscode`
--

CREATE TABLE `accesscode` (
  `id` int NOT NULL,
  `vid` varchar(32) NOT NULL,
  `code` varchar(32) NOT NULL,
  `meetingid` varchar(32) NOT NULL,
  `updatetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- 資料表結構 `attachement`
--

CREATE TABLE `attachement` (
  `id` int NOT NULL,
  `insurance_id` varchar(32) NOT NULL,
  `remote_insurance_id` varchar(32) NOT NULL,
  `person_id` varchar(32) NOT NULL,
  `attache_graph` longblob,
  `attache_path` varchar(256) DEFAULT NULL,
  `updatetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- 資料表結構 `countrylog`
--

CREATE TABLE `countrylog` (
  `id` int NOT NULL,
  `Person_id` varchar(32) NOT NULL,
  `Insurance_id` varchar(32) NOT NULL,
  `remote_Insurance_id` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `countrycode` varchar(5) NOT NULL,
  `updatetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- 資料表結構 `facecomparelog`
--

CREATE TABLE `facecomparelog` (
  `id` int NOT NULL,
  `Insurance_no` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `remote_Insurance_no` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `Person_id` varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `face1` longblob,
  `face2` longblob,
  `confidence` varchar(10) DEFAULT NULL,
  `updatetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- 資料表結構 `gomeeting`
--

CREATE TABLE `gomeeting` (
  `id` int NOT NULL,
  `insurance_no` varchar(32) NOT NULL,
  `remote_Insurance_no` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `vmr` varchar(10) DEFAULT NULL,
  `meetingid` int NOT NULL,
  `accesscode` varchar(32) NOT NULL,
  `starttime` datetime NOT NULL,
  `stoptime` datetime NOT NULL,
  `count` int NOT NULL,
  `updatetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- 資料表結構 `idphoto`
--

CREATE TABLE `idphoto` (
  `id` int NOT NULL,
  `front` longblob,
  `back` longblob,
  `updatedtime` datetime NOT NULL,
  `person_id` varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `insurance_no` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `remote_insurance_no ` varchar(32) NOT NULL,
  `frontpath` varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `backpath` varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `saveType` varchar(5) NOT NULL DEFAULT 'DB'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- 資料表結構 `meetinglog`
--

CREATE TABLE `meetinglog` (
  `id` int NOT NULL,
  `meetingid` int NOT NULL,
  `insurance_no` varchar(32) NOT NULL,
  `remote_insurance_no ` varchar(32) NOT NULL,
  `video_time` int NOT NULL,
  `bSaved` int NOT NULL DEFAULT '-1',
  `bDownload` tinyint NOT NULL DEFAULT '0',
  `bookstarttime` datetime NOT NULL,
  `bookstoptime` datetime NOT NULL,
  `starttime` datetime DEFAULT NULL,
  `stoptime` datetime DEFAULT NULL,
  `log` varchar(256) DEFAULT NULL,
  `filename` varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `proposer_id` varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `proposer_gps` varchar(64) DEFAULT NULL,
  `proposer_addr` varchar(256) DEFAULT NULL,
  `insured_id` varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `insured_gps` varchar(64) DEFAULT NULL,
  `insured_addr` varchar(256) DEFAULT NULL,
  `legalRep_id` varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `legalRep_gps` varchar(64) DEFAULT NULL,
  `legalRep_addr` varchar(256) DEFAULT NULL,
  `agent_id` varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `agent_gps` varchar(64) DEFAULT NULL,
  `agent_addr` varchar(256) DEFAULT NULL,
  `updatetime` datetime NOT NULL,
  `vid` varchar(10) NOT NULL,
  `bStop` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- 資料表結構 `memberinfo`
--

CREATE TABLE `memberinfo` (
  `mid` int NOT NULL,
  `person_id` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mobile_no` varchar(255) DEFAULT NULL,
  `member_name` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `pid_pic` longblob,
  `signature_pic` longblob,
  `notificationToken` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT 'caJAUTmVOUK8hYhyxrFyzp:APA91bGAJHQzPmIaRZgR37VYoxqtOtstj3RLqNh8_2cC8D142LSXK9Vjb1pI6svHRxCt9uWf021sexPhEiWnUPefsltW6atr2PyS54qWTfSRpjWq3AOqD6HHzmvgRWmilFLK3BnXh-Et',
  `member_trash` tinyint(1) NOT NULL DEFAULT '0',
  `inputdttime` datetime DEFAULT NULL,
  `updatedttime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- 資料表結構 `notificationlog`
--

CREATE TABLE `notificationlog` (
  `id` int NOT NULL,
  `Person_id` varchar(256) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `role` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `msg` varchar(256) DEFAULT NULL,
  `fcmresult` varchar(256) DEFAULT NULL,
  `updatetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- 資料表結構 `orderinfo`
--

CREATE TABLE `orderinfo` (
  `rid` int NOT NULL,
  `insurance_no ` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `remote_Insurance_no` varchar(32) NOT NULL,
  `sales_id` varchar(32) NOT NULL DEFAULT '',
  `person_id` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mobile_no` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `role` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `verification_code` varchar(10) DEFAULT NULL,
  `order_status` int NOT NULL DEFAULT '0',
  `notificationToken` varchar(255) DEFAULT NULL,
  `order_trash` tinyint(1) NOT NULL DEFAULT '0',
  `inputdttime` datetime DEFAULT NULL,
  `updatedttime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- 資料表結構 `orderlog`
--

CREATE TABLE `orderlog` (
  `oid` int NOT NULL,
  `insurance_no` varchar(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `remote_insurance_no` varchar(32) NOT NULL DEFAULT '',
  `sales_id` varchar(32) NOT NULL DEFAULT '',
  `person_id` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `mobile_no` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `member_type` tinyint(1) NOT NULL DEFAULT '0',
  `order_status` int NOT NULL DEFAULT '0',
  `log_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- 資料表結構 `salesinfo`
--

CREATE TABLE `salesinfo` (
  `sid` int NOT NULL,
  `person_id` varchar(10) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `mobile_no` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `sales_id` varchar(32) DEFAULT NULL,
  `sales_name` varchar(255) DEFAULT NULL,
  `notificationToken` varchar(255) DEFAULT NULL,
  `sales_trash` tinyint(1) NOT NULL DEFAULT '0',
  `inputdttime` datetime DEFAULT NULL,
  `updatedttime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- 資料表結構 `sysuser`
--

CREATE TABLE `sysuser` (
  `sid` int NOT NULL,
  `user_id` varchar(128) DEFAULT NULL,
  `group_id` int NOT NULL DEFAULT '0',
  `user_name` varchar(128) DEFAULT NULL,
  `user_pwd` varchar(32) DEFAULT NULL,
  `user_mobile` varchar(32) DEFAULT NULL,
  `reset_code` varchar(16) DEFAULT NULL,
  `user_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_updated_at` timestamp NULL DEFAULT NULL,
  `user_created_by` varchar(255) DEFAULT NULL,
  `user_updated_by` varchar(255) DEFAULT NULL,
  `user_trash` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

-- --------------------------------------------------------

--
-- 資料表結構 `vmrinfo`
--

CREATE TABLE `vmrinfo` (
  `id` int NOT NULL,
  `vid` int NOT NULL,
  `status` int NOT NULL,
  `vmr` varchar(10) NOT NULL,
  `checkvmr` int DEFAULT '1',
  `updatetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3;

--
-- 已傾印資料表的索引
--

--
-- 資料表索引 `accesscode`
--
ALTER TABLE `accesscode`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `attachement`
--
ALTER TABLE `attachement`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `countrylog`
--
ALTER TABLE `countrylog`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `facecomparelog`
--
ALTER TABLE `facecomparelog`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `gomeeting`
--
ALTER TABLE `gomeeting`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `idphoto`
--
ALTER TABLE `idphoto`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `meetinglog`
--
ALTER TABLE `meetinglog`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `memberinfo`
--
ALTER TABLE `memberinfo`
  ADD PRIMARY KEY (`mid`),
  ADD KEY `person_id` (`person_id`,`mobile_no`);

--
-- 資料表索引 `notificationlog`
--
ALTER TABLE `notificationlog`
  ADD PRIMARY KEY (`id`);

--
-- 資料表索引 `orderinfo`
--
ALTER TABLE `orderinfo`
  ADD PRIMARY KEY (`rid`),
  ADD KEY `order_no` (`insurance_no `,`sales_id`,`person_id`);

--
-- 資料表索引 `orderlog`
--
ALTER TABLE `orderlog`
  ADD PRIMARY KEY (`oid`),
  ADD KEY `order_no_1` (`insurance_no`,`sales_id`,`person_id`);

--
-- 資料表索引 `salesinfo`
--
ALTER TABLE `salesinfo`
  ADD PRIMARY KEY (`sid`),
  ADD KEY `sales_no` (`person_id`,`mobile_no`,`sales_id`);

--
-- 資料表索引 `sysuser`
--
ALTER TABLE `sysuser`
  ADD PRIMARY KEY (`sid`);

--
-- 資料表索引 `vmrinfo`
--
ALTER TABLE `vmrinfo`
  ADD PRIMARY KEY (`id`);

--
-- 在傾印的資料表使用自動遞增(AUTO_INCREMENT)
--

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `accesscode`
--
ALTER TABLE `accesscode`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `attachement`
--
ALTER TABLE `attachement`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `countrylog`
--
ALTER TABLE `countrylog`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `facecomparelog`
--
ALTER TABLE `facecomparelog`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `gomeeting`
--
ALTER TABLE `gomeeting`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `idphoto`
--
ALTER TABLE `idphoto`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `meetinglog`
--
ALTER TABLE `meetinglog`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `memberinfo`
--
ALTER TABLE `memberinfo`
  MODIFY `mid` int NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `notificationlog`
--
ALTER TABLE `notificationlog`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `orderinfo`
--
ALTER TABLE `orderinfo`
  MODIFY `rid` int NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `orderlog`
--
ALTER TABLE `orderlog`
  MODIFY `oid` int NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `salesinfo`
--
ALTER TABLE `salesinfo`
  MODIFY `sid` int NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `sysuser`
--
ALTER TABLE `sysuser`
  MODIFY `sid` int NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `vmrinfo`
--
ALTER TABLE `vmrinfo`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
