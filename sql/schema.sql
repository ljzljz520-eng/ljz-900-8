-- =====================================================================
-- 仓库现场 5S 检查系统 数据库结构
-- MySQL 8.0+ / 5.7 兼容；字符集 utf8mb4
-- 表前缀 wa_  （对应 .env DATABASE.PREFIX）
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `warehouse_5s`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `warehouse_5s`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 1. 系统用户（管理员 / 老板）
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `wa_admin_user`;
CREATE TABLE `wa_admin_user` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '主键',
  `username`   VARCHAR(50)  NOT NULL COMMENT '登录名',
  `password`   VARCHAR(255) NOT NULL COMMENT 'password_hash 加密',
  `real_name`  VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '姓名',
  `role`       TINYINT      NOT NULL DEFAULT 1 COMMENT '角色：1管理员 2老板(只读看板)',
  `status`     TINYINT      NOT NULL DEFAULT 1 COMMENT '状态：1启用 0停用',
  `login_ip`   VARCHAR(45)           DEFAULT NULL COMMENT '最近登录IP',
  `login_at`   DATETIME              DEFAULT NULL COMMENT '最近登录时间',
  `create_time` DATETIME             DEFAULT NULL,
  `update_time` DATETIME             DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='后台用户（管理员/老板）';

-- ---------------------------------------------------------------------
-- 2. 仓库区域
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `wa_area`;
CREATE TABLE `wa_area` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(80)  NOT NULL COMMENT '区域名称，如 A区货架',
  `code`        VARCHAR(30)           DEFAULT NULL COMMENT '区域编码',
  `manager`     VARCHAR(50)           DEFAULT '' COMMENT '区域负责人',
  `sort`        INT          NOT NULL DEFAULT 0 COMMENT '排序',
  `status`      TINYINT      NOT NULL DEFAULT 1 COMMENT '1启用 0停用',
  `create_time` DATETIME              DEFAULT NULL,
  `update_time` DATETIME              DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='仓库区域';

-- ---------------------------------------------------------------------
-- 3. 仓库员工（扫码整改人）
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `wa_employee`;
CREATE TABLE `wa_employee` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(50)  NOT NULL COMMENT '姓名',
  `job_no`      VARCHAR(30)           DEFAULT NULL COMMENT '工号',
  `phone`       VARCHAR(20)           DEFAULT '' COMMENT '手机号',
  `pin`         VARCHAR(255) NOT NULL COMMENT '4位整改口令(password_hash)',
  `area_id`     INT UNSIGNED          DEFAULT NULL COMMENT '所属区域',
  `status`      TINYINT      NOT NULL DEFAULT 1 COMMENT '1在职 0离职',
  `create_time` DATETIME              DEFAULT NULL,
  `update_time` DATETIME              DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_job_no` (`job_no`),
  KEY `idx_area` (`area_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='仓库员工';

-- ---------------------------------------------------------------------
-- 4. 5S 检查项
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `wa_check_item`;
CREATE TABLE `wa_check_item` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category`    VARCHAR(10)  NOT NULL COMMENT '5S 维度：整理/整顿/清扫/清洁/素养',
  `content`     VARCHAR(255) NOT NULL COMMENT '检查内容',
  `max_score`   TINYINT      NOT NULL DEFAULT 5 COMMENT '标准扣分分值',
  `sort`        INT          NOT NULL DEFAULT 0,
  `status`      TINYINT      NOT NULL DEFAULT 1 COMMENT '1启用 0停用',
  `create_time` DATETIME              DEFAULT NULL,
  `update_time` DATETIME              DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='5S 检查项';

-- ---------------------------------------------------------------------
-- 5. 检查问题单（核心表）
--    一条记录 = 一张「问题图 ↔ 整改后图」图片对
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `wa_issue`;
CREATE TABLE `wa_issue` (
  `id`                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`              VARCHAR(20)  NOT NULL COMMENT '问题单号（二维码内容）',
  `area_id`           INT UNSIGNED NOT NULL COMMENT '所在区域',
  `check_item_id`     INT UNSIGNED NOT NULL COMMENT '检查项',
  `inspector_id`      INT UNSIGNED NOT NULL COMMENT '开单管理员',
  `employee_id`       INT UNSIGNED          DEFAULT NULL COMMENT '整改责任人',
  `check_date`        DATE         NOT NULL COMMENT '检查日期',
  `deduct_score`      TINYINT      NOT NULL DEFAULT 0 COMMENT '本次扣分',
  `description`       VARCHAR(500) NOT NULL DEFAULT '' COMMENT '问题描述',
  `photo_before`      VARCHAR(255)          DEFAULT NULL COMMENT '问题图（相对路径）',
  `photo_after`       VARCHAR(255)          DEFAULT NULL COMMENT '整改后图；NULL=未整改占位提醒',
  `rectify_remark`    VARCHAR(500)          DEFAULT '' COMMENT '整改说明',
  `status`            TINYINT      NOT NULL DEFAULT 0 COMMENT '0待整改 1已整改 2已复核',
  `rectified_at`     DATETIME              DEFAULT NULL COMMENT '整改提交时间',
  `reviewed_at`       DATETIME              DEFAULT NULL COMMENT '复核时间',
  `create_time`       DATETIME              DEFAULT NULL,
  `update_time`       DATETIME              DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`),
  KEY `idx_area_date`   (`area_id`, `check_date`),
  KEY `idx_employee`    (`employee_id`),
  KEY `idx_status_date` (`status`, `check_date`),
  KEY `idx_check_item`  (`check_item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='5S 检查问题单';

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 基础数据（账号口令请执行： php think install:seed 重新安全生成）
--   admin / admin123   管理员
--   boss  / boss123    老板（只读看板）
--   员工整改口令默认： 1234
-- =====================================================================
INSERT INTO `wa_admin_user` (`username`,`password`,`real_name`,`role`,`status`,`create_time`,`update_time`) VALUES
('admin', '__BCRYPT_ADMIN__', '系统管理员', 1, 1, NOW(), NOW()),
('boss',  '__BCRYPT_BOSS__',  '老板',     2, 1, NOW(), NOW());

INSERT INTO `wa_area` (`name`,`code`,`manager`,`sort`,`status`,`create_time`,`update_time`) VALUES
('A区-高位货架', 'A-01', '张师傅', 10, 1, NOW(), NOW()),
('B区-拣货区',   'B-01', '李师傅', 20, 1, NOW(), NOW()),
('C区-收货月台', 'C-01', '王师傅', 30, 1, NOW(), NOW()),
('D区-暂存区',   'D-01', '赵师傅', 40, 1, NOW(), NOW()),
('E区-办公休息区','E-01','孙姐',  50, 1, NOW(), NOW());

INSERT INTO `wa_employee` (`name`,`job_no`,`phone`,`pin`,`area_id`,`status`,`create_time`,`update_time`) VALUES
('陈强', 'WH001', '13800000001', '__BCRYPT_PIN__', 1, 1, NOW(), NOW()),
('刘洋', 'WH002', '13800000002', '__BCRYPT_PIN__', 2, 1, NOW(), NOW()),
('周鹏', 'WH003', '13800000003', '__BCRYPT_PIN__', 3, 1, NOW(), NOW()),
('吴敏', 'WH004', '13800000004', '__BCRYPT_PIN__', 4, 1, NOW(), NOW());

INSERT INTO `wa_check_item` (`category`,`content`,`max_score`,`sort`,`status`,`create_time`,`update_time`) VALUES
('整理', '通道无闲置托盘、废纸箱等无用物品', 5, 10, 1, NOW(), NOW()),
('整理', '呆废料、报废品按标识隔离处理',     3, 20, 1, NOW(), NOW()),
('整顿', '货物按库位/标识五距摆放，无超高',  5, 30, 1, NOW(), NOW()),
('整顿', '消防器材、电箱前无遮挡',           5, 40, 1, NOW(), NOW()),
('整顿', '工具定置定位、形迹管理',           3, 50, 1, NOW(), NOW()),
('清扫', '地面无积水、油污、杂物',           4, 60, 1, NOW(), NOW()),
('清扫', '货架、设备表面无明显积灰',         3, 70, 1, NOW(), NOW()),
('清洁', '责任区点检表按时填写并保持',       2, 80, 1, NOW(), NOW()),
('清洁', '照明完好、反光标识清晰',           2, 90, 1, NOW(), NOW()),
('素养', '按规定着装、佩戴劳保用品',         3, 100,1, NOW(), NOW()),
('素养', '现场吸烟、饮食等违纪行为为零',     5, 110,1, NOW(), NOW());
