-- phpMyAdmin SQL Dump
-- version 5.1.1
-- https://www.phpmyadmin.net/
--
-- 主機： 127.0.0.1
-- 產生時間： 2022-11-01 05:46:14
-- 伺服器版本： 10.4.22-MariaDB
-- PHP 版本： 7.4.27

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- 資料庫: `fhmemberdb`
--

-- --------------------------------------------------------

--
-- 資料表結構 `accesscode`
--

CREATE TABLE `accesscode` (
  `id` int(11) NOT NULL,
  `vid` varchar(32) NOT NULL,
  `code` varchar(32) NOT NULL,
  `meetingid` varchar(32) NOT NULL,
  `updatetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `attachement`
--

CREATE TABLE `attachement` (
  `id` int(11) NOT NULL,
  `insurance_no` varchar(32) CHARACTER SET utf8 NOT NULL,
  `remote_insurance_no` varchar(32) CHARACTER SET utf8 NOT NULL,
  `person_id` varchar(32) CHARACTER SET utf8 NOT NULL,
  `attache_title` varchar(500) CHARACTER SET utf8 NOT NULL,
  `attache_graph` longblob DEFAULT NULL,
  `attache_path` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `createtime` datetime NOT NULL,
  `updatetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `countrylog`
--

CREATE TABLE `countrylog` (
  `id` int(11) NOT NULL,
  `Person_id` varchar(32) NOT NULL,
  `Insurance_no` varchar(32) NOT NULL,
  `remote_Insurance_no` varchar(32) NOT NULL,
  `countrycode` varchar(5) NOT NULL,
  `updatetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `facecomparelog`
--

CREATE TABLE `facecomparelog` (
  `id` int(11) NOT NULL,
  `Insurance_no` varchar(32) DEFAULT NULL,
  `remote_Insurance_no` varchar(32) NOT NULL,
  `Person_id` varchar(256) NOT NULL,
  `face1` longblob DEFAULT NULL,
  `face2` longblob DEFAULT NULL,
  `confidence` varchar(10) DEFAULT NULL,
  `updatetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `gomeeting`
--

CREATE TABLE `gomeeting` (
  `id` int(11) NOT NULL,
  `insurance_no` varchar(32) NOT NULL,
  `remote_Insurance_no` varchar(32) NOT NULL,
  `vmr` varchar(10) DEFAULT NULL,
  `meetingid` int(11) NOT NULL,
  `accesscode` varchar(32) NOT NULL,
  `starttime` datetime NOT NULL,
  `stoptime` datetime NOT NULL,
  `count` int(11) NOT NULL,
  `updatetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `idphoto`
--

CREATE TABLE `idphoto` (
  `id` int(11) NOT NULL,
  `front` longblob DEFAULT NULL,
  `back` longblob DEFAULT NULL,
  `updatedtime` datetime NOT NULL,
  `person_id` varchar(256) NOT NULL,
  `insurance_no` varchar(32) DEFAULT NULL,
  `remote_insurance_no ` varchar(32) DEFAULT NULL,
  `frontpath` varchar(256) DEFAULT NULL,
  `backpath` varchar(256) DEFAULT NULL,
  `saveType` varchar(5) NOT NULL DEFAULT 'DB'
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `jsonlog`
--

CREATE TABLE `jsonlog` (
  `id` int(11) NOT NULL,
  `insurance_no` varchar(32) CHARACTER SET utf8 NOT NULL,
  `remote_insurance_no` varchar(32) CHARACTER SET utf8 NOT NULL,
  `json_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`json_data`)),
  `order_status` varchar(5) CHARACTER SET utf8 NOT NULL,
  `createtime` datetime NOT NULL,
  `updatetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

--
-- 傾印資料表的資料 `jsonlog`
--

INSERT INTO `jsonlog` (`id`, `insurance_no`, `remote_insurance_no`, `json_data`, `order_status`, `createtime`, `updatetime`) VALUES
(1, 'appl2022', 'num123', '{\"acceptId\":\"Ins1996\",\"dueTime\":\"2022/10/31 23:00:00\",\"applToken\":\"appl\",\"prodID\":\"A\",\"partnerCode\":\"tonlu\",\"insuredToken\":\"ins2022\",\"repreToken\":\"rep2022\",\"applyNo\":\"appl1998\",\"numbering\":\"phone2022\",\"polSummary\":[{\"applyNo\":\"appl2022\",\"numbering\":\"num123\",\"applyVersion\":\"1.0\",\"productName\":\"主約險種名稱\",\"productCode\":\"B\",\"policyCode\":\"\",\"rolesInfo\":[{\"name\":\"張三\",\"idcard\":\"A123456789\",\"tel\":\"0912-345-777\",\"roleName\":\"要保人\",\"roleKey\":\"proposer\"},{\"name\":\"李四\",\"idcard\":\"B123456789\",\"tel\":\"0912-345-888\",\"roleName\":\"要保人\",\"roleKey\":\"insured\"},{\"name\":\"王五\",\"idcard\":\"C123456789\",\"tel\":\"0912-345-999\",\"roleName\":\"法定代理人\",\"roleKey\":\"legalRepresentative\"},{\"name\":\"業務\",\"idcard\":\"E123456789\",\"tel\":\"0912-345-111\",\"roleName\":\"業務員\",\"roleKey\":\"agentOne\"}]}],\"applicationData\":[{\"attacheCode\":\"pdf001\",\"attacheName\":\"要保書\",\"attacheContent\":\"base64\",\"policyOwnerFlag\":\"Y\",\"insuredFlag\":\"Y\",\"representFlag\":\"Y\",\"agentFlag\":\"Y\",\"signTagSetting\":\"tag01\",\"policyTagSetting\":\"tag02\",\"applDateTagSetting\":\"tag03\"}],\"uploadData\":[{\"attacheCode\":\"attache001\",\"attacheName\":\"附件一\",\"policyOwnerFlag\":\"Y\",\"insuredFlag\":\"Y\",\"representFlag\":\"Y\"}]}', 'A2', '2022-11-01 09:58:01', '2022-11-01 09:58:01');

-- --------------------------------------------------------

--
-- 資料表結構 `meetinglog`
--

CREATE TABLE `meetinglog` (
  `id` int(11) NOT NULL,
  `meetingid` int(11) NOT NULL,
  `insurance_no` varchar(32) NOT NULL,
  `remote_insurance_no ` varchar(32) NOT NULL,
  `video_time` int(11) NOT NULL,
  `bSaved` int(11) NOT NULL DEFAULT -1,
  `bDownload` tinyint(4) NOT NULL DEFAULT 0,
  `bookstarttime` datetime NOT NULL,
  `bookstoptime` datetime NOT NULL,
  `starttime` datetime DEFAULT NULL,
  `stoptime` datetime DEFAULT NULL,
  `log` varchar(256) DEFAULT NULL,
  `filename` varchar(256) DEFAULT NULL,
  `proposer_id` varchar(256) DEFAULT NULL,
  `proposer_gps` varchar(64) DEFAULT NULL,
  `proposer_addr` varchar(256) DEFAULT NULL,
  `insured_id` varchar(256) DEFAULT NULL,
  `insured_gps` varchar(64) DEFAULT NULL,
  `insured_addr` varchar(256) DEFAULT NULL,
  `legalRep_id` varchar(256) DEFAULT NULL,
  `legalRep_gps` varchar(64) DEFAULT NULL,
  `legalRep_addr` varchar(256) DEFAULT NULL,
  `agent_id` varchar(256) DEFAULT NULL,
  `agent_gps` varchar(64) DEFAULT NULL,
  `agent_addr` varchar(256) DEFAULT NULL,
  `updatetime` datetime NOT NULL,
  `vid` varchar(10) NOT NULL,
  `bStop` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `memberinfo`
--

CREATE TABLE `memberinfo` (
  `mid` int(11) NOT NULL,
  `insurance_no` varchar(32) NOT NULL,
  `remote_Insurance_no` varchar(32) NOT NULL,
  `person_id` varchar(10) NOT NULL DEFAULT '',
  `role` varchar(32) NOT NULL,
  `mobile_no` varchar(255) DEFAULT NULL,
  `member_name` varchar(255) NOT NULL DEFAULT '',
  `pid_pic` longblob DEFAULT NULL,
  `signature_pic` longblob DEFAULT NULL,
  `notificationToken` varchar(255) DEFAULT 'caJAUTmVOUK8hYhyxrFyzp:APA91bGAJHQzPmIaRZgR37VYoxqtOtstj3RLqNh8_2cC8D142LSXK9Vjb1pI6svHRxCt9uWf021sexPhEiWnUPefsltW6atr2PyS54qWTfSRpjWq3AOqD6HHzmvgRWmilFLK3BnXh-Et',
  `member_trash` tinyint(1) NOT NULL DEFAULT 0,
  `inputdttime` datetime DEFAULT NULL,
  `updatedttime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `notificationlog`
--

CREATE TABLE `notificationlog` (
  `id` int(11) NOT NULL,
  `insurance_no` varchar(32) NOT NULL DEFAULT '',
  `remote_Insurance_no` varchar(32) NOT NULL DEFAULT '',
  `person_id` varchar(256) NOT NULL,
  `role` varchar(32) DEFAULT NULL,
  `msg` varchar(256) DEFAULT NULL,
  `fcmresult` varchar(256) DEFAULT NULL,
  `updatetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `orderinfo`
--

CREATE TABLE `orderinfo` (
  `rid` int(11) NOT NULL,
  `policy_number` varchar(100) DEFAULT NULL,
  `insurance_no` varchar(32) NOT NULL DEFAULT '',
  `remote_insurance_no` varchar(32) NOT NULL,
  `sales_id` varchar(32) NOT NULL DEFAULT '',
  `person_id` varchar(10) NOT NULL DEFAULT '',
  `mobile_no` varchar(255) DEFAULT NULL,
  `role` varchar(32) NOT NULL,
  `verification_code` varchar(10) DEFAULT NULL,
  `order_status` varchar(5) NOT NULL DEFAULT '0',
  `notificationToken` varchar(255) DEFAULT NULL,
  `order_trash` tinyint(1) NOT NULL DEFAULT 0,
  `inputdttime` datetime DEFAULT NULL,
  `updatedttime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 傾印資料表的資料 `orderinfo`
--

INSERT INTO `orderinfo` (`rid`, `policy_number`, `insurance_no`, `remote_insurance_no`, `sales_id`, `person_id`, `mobile_no`, `role`, `verification_code`, `order_status`, `notificationToken`, `order_trash`, `inputdttime`, `updatedttime`) VALUES
(1, NULL, 'appl2022', 'num123', 'E123456789', 'A123456789', '03N9pmvXQXx28oJRMT+/yQ==', 'proposer', NULL, 'A2', NULL, 0, '2022-11-01 09:59:46', NULL),
(2, NULL, 'appl2022', 'num123', 'E123456789', 'B123456789', '03N9pmvXQXx28oJRMT+/yQ==', 'insured', NULL, 'A2', NULL, 0, '2022-11-01 09:59:46', NULL),
(3, NULL, 'appl2022', 'num123', 'E123456789', 'C123456789', '03N9pmvXQXx28oJRMT+/yQ==', 'legalRepresentative', NULL, 'A2', NULL, 0, '2022-11-01 09:59:46', NULL),
(4, NULL, 'appl2022', 'num123', 'E123456789', 'E123456789', '03N9pmvXQXx28oJRMT+/yQ==', 'agentOne', NULL, 'A2', NULL, 0, '2022-11-01 09:59:46', NULL);

-- --------------------------------------------------------

--
-- 資料表結構 `orderlog`
--

CREATE TABLE `orderlog` (
  `oid` int(11) NOT NULL,
  `insurance_no` varchar(32) NOT NULL DEFAULT '',
  `remote_insurance_no` varchar(32) NOT NULL DEFAULT '',
  `sales_id` varchar(32) NOT NULL DEFAULT '',
  `person_id` varchar(10) NOT NULL DEFAULT '',
  `mobile_no` varchar(255) DEFAULT NULL,
  `member_type` tinyint(1) NOT NULL DEFAULT 0,
  `order_status` varchar(5) NOT NULL DEFAULT '0',
  `log_date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- 傾印資料表的資料 `orderlog`
--

INSERT INTO `orderlog` (`oid`, `insurance_no`, `remote_insurance_no`, `sales_id`, `person_id`, `mobile_no`, `member_type`, `order_status`, `log_date`) VALUES
(1, 'appl2022', 'num123', 'E123456789', 'A123456789', '03N9pmvXQXx28oJRMT+/yQ==', 0, 'A2', '2022-11-01 09:59:46'),
(2, 'appl2022', 'num123', 'E123456789', 'B123456789', '03N9pmvXQXx28oJRMT+/yQ==', 0, 'A2', '2022-11-01 09:59:46'),
(3, 'appl2022', 'num123', 'E123456789', 'C123456789', '03N9pmvXQXx28oJRMT+/yQ==', 0, 'A2', '2022-11-01 09:59:46'),
(4, 'appl2022', 'num123', 'E123456789', 'E123456789', '03N9pmvXQXx28oJRMT+/yQ==', 0, 'A2', '2022-11-01 09:59:46');

-- --------------------------------------------------------

--
-- 資料表結構 `pdflog`
--

CREATE TABLE `pdflog` (
  `id` int(20) NOT NULL,
  `insurance_no` varchar(32) NOT NULL,
  `remote_insurance_no` varchar(32) NOT NULL,
  `title` varchar(100) NOT NULL,
  `pdf_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pdf_data`)),
  `pdf_path` varchar(256) DEFAULT NULL,
  `order_status` varchar(5) NOT NULL,
  `upatetime` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------

--
-- 資料表結構 `salesinfo`
--

CREATE TABLE `salesinfo` (
  `sid` int(11) NOT NULL,
  `person_id` varchar(10) DEFAULT NULL,
  `mobile_no` varchar(255) DEFAULT NULL,
  `sales_id` varchar(32) DEFAULT NULL,
  `sales_name` varchar(255) DEFAULT NULL,
  `notificationToken` varchar(255) DEFAULT NULL,
  `sales_trash` tinyint(1) NOT NULL DEFAULT 0,
  `inputdttime` datetime DEFAULT NULL,
  `updatedttime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `sysuser`
--

CREATE TABLE `sysuser` (
  `sid` int(11) NOT NULL,
  `user_id` varchar(128) DEFAULT NULL,
  `group_id` int(11) NOT NULL DEFAULT 0,
  `user_name` varchar(128) DEFAULT NULL,
  `user_pwd` varchar(32) DEFAULT NULL,
  `user_mobile` varchar(32) DEFAULT NULL,
  `reset_code` varchar(16) DEFAULT NULL,
  `user_created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_updated_at` timestamp NULL DEFAULT NULL,
  `user_created_by` varchar(255) DEFAULT NULL,
  `user_updated_by` varchar(255) DEFAULT NULL,
  `user_trash` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- 資料表結構 `vmrinfo`
--

CREATE TABLE `vmrinfo` (
  `id` int(11) NOT NULL,
  `vid` int(11) NOT NULL,
  `status` int(11) NOT NULL,
  `vmr` varchar(10) NOT NULL,
  `checkvmr` int(11) DEFAULT 1,
  `updatetime` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

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
-- 資料表索引 `jsonlog`
--
ALTER TABLE `jsonlog`
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
  ADD KEY `order_no` (`insurance_no`,`sales_id`,`person_id`);

--
-- 資料表索引 `orderlog`
--
ALTER TABLE `orderlog`
  ADD PRIMARY KEY (`oid`),
  ADD KEY `order_no_1` (`insurance_no`,`sales_id`,`person_id`);

--
-- 資料表索引 `pdflog`
--
ALTER TABLE `pdflog`
  ADD PRIMARY KEY (`id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `attachement`
--
ALTER TABLE `attachement`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `countrylog`
--
ALTER TABLE `countrylog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `facecomparelog`
--
ALTER TABLE `facecomparelog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `gomeeting`
--
ALTER TABLE `gomeeting`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `idphoto`
--
ALTER TABLE `idphoto`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `jsonlog`
--
ALTER TABLE `jsonlog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `meetinglog`
--
ALTER TABLE `meetinglog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `memberinfo`
--
ALTER TABLE `memberinfo`
  MODIFY `mid` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `notificationlog`
--
ALTER TABLE `notificationlog`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `orderinfo`
--
ALTER TABLE `orderinfo`
  MODIFY `rid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `orderlog`
--
ALTER TABLE `orderlog`
  MODIFY `oid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `pdflog`
--
ALTER TABLE `pdflog`
  MODIFY `id` int(20) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `salesinfo`
--
ALTER TABLE `salesinfo`
  MODIFY `sid` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `sysuser`
--
ALTER TABLE `sysuser`
  MODIFY `sid` int(11) NOT NULL AUTO_INCREMENT;

--
-- 使用資料表自動遞增(AUTO_INCREMENT) `vmrinfo`
--
ALTER TABLE `vmrinfo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
