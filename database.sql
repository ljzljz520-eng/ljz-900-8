-- ======================================================================
-- 仓库现场5S检查系统 数据库脚本（MySQL 5.7+ / 8.0）
-- 字符集：utf8mb4    引擎：InnoDB
-- ======================================================================
CREATE DATABASE IF NOT EXISTS `warehouse_5s` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `warehouse_5s`;

-- ----------------------------------------------------------------------
-- 用户表：admin=管理员 staff=仓库员工 boss=老板
-- ----------------------------------------------------------------------
CREATE TABLE `users` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username`   VARCHAR(50)  NOT NULL COMMENT '登录账号',
  `password`   VARCHAR(255) NOT NULL COMMENT '密码（password_hash）',
  `real_name`  VARCHAR(50)  NOT NULL DEFAULT '' COMMENT '姓名',
  `role`       ENUM('admin','staff','boss') NOT NULL DEFAULT 'staff' COMMENT '角色',
  `status`     TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '状态：1正常 0禁用',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`)
) ENGINE=InnoDB COMMENT='用户表';

-- ----------------------------------------------------------------------
-- 区域表：code 用于生成区域二维码（/staff/scan?code=xxx）
-- ----------------------------------------------------------------------
CREATE TABLE `areas` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '区域ID',
  `name`       VARCHAR(100) NOT NULL COMMENT '区域名称',
  `code`       VARCHAR(64)  NOT NULL COMMENT '区域编码（二维码内容）',
  `sort`       INT          NOT NULL DEFAULT 0 COMMENT '排序',
  `status`     TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '状态：1启用 0停用',
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_code` (`code`)
) ENGINE=InnoDB COMMENT='仓库区域表';

-- ----------------------------------------------------------------------
-- 5S检查项表：整理/整顿/清扫/清洁/素养 + 扣分标准
-- ----------------------------------------------------------------------
CREATE TABLE `inspection_items` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '检查项ID',
  `name`         VARCHAR(100) NOT NULL COMMENT '检查项名称',
  `category`     ENUM('整理','整顿','清扫','清洁','素养') NOT NULL DEFAULT '整理' COMMENT '5S分类',
  `deduct_score` DECIMAL(5,1) NOT NULL DEFAULT 1.0 COMMENT '扣分标准',
  `status`       TINYINT(1)   NOT NULL DEFAULT 1 COMMENT '状态：1启用 0停用',
  `created_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`)
) ENGINE=InnoDB COMMENT='5S检查项表';

-- ----------------------------------------------------------------------
-- 检查问题记录表：管理员发布的问题（问题图 + 检查项 + 扣分）
-- status：0待整改 1已整改
-- ----------------------------------------------------------------------
CREATE TABLE `inspections` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '问题ID',
  `area_id`       INT UNSIGNED NOT NULL COMMENT '区域ID',
  `item_id`       INT UNSIGNED NOT NULL COMMENT '检查项ID',
  `admin_id`      INT UNSIGNED NOT NULL COMMENT '检查人（管理员）ID',
  `assignee_id`   INT UNSIGNED DEFAULT NULL COMMENT '指派整改员工ID（可空）',
  `problem_image` VARCHAR(255) NOT NULL COMMENT '问题图片路径',
  `deduct_score`  DECIMAL(5,1) NOT NULL DEFAULT 0.0 COMMENT '本次扣分',
  `remark`        VARCHAR(500) NOT NULL DEFAULT '' COMMENT '问题描述',
  `status`        TINYINT(1)   NOT NULL DEFAULT 0 COMMENT '状态：0待整改 1已整改',
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '发布时间',
  `updated_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_area_status` (`area_id`, `status`),
  KEY `idx_item` (`item_id`),
  KEY `idx_status_created` (`status`, `created_at`),
  KEY `idx_created` (`created_at`),
  CONSTRAINT `fk_ins_area`     FOREIGN KEY (`area_id`)     REFERENCES `areas` (`id`),
  CONSTRAINT `fk_ins_item`     FOREIGN KEY (`item_id`)     REFERENCES `inspection_items` (`id`),
  CONSTRAINT `fk_ins_admin`    FOREIGN KEY (`admin_id`)    REFERENCES `users` (`id`),
  CONSTRAINT `fk_ins_assignee` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB COMMENT='检查问题记录表';

-- ----------------------------------------------------------------------
-- 整改记录表：员工上传整改后图片（与问题记录一对一）
-- ----------------------------------------------------------------------
CREATE TABLE `rectifications` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '整改ID',
  `inspection_id`   INT UNSIGNED NOT NULL COMMENT '问题记录ID',
  `user_id`         INT UNSIGNED NOT NULL COMMENT '整改员工ID',
  `rectified_image` VARCHAR(255) NOT NULL COMMENT '整改后图片路径',
  `remark`          VARCHAR(500) NOT NULL DEFAULT '' COMMENT '整改说明',
  `created_at`      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '整改时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inspection` (`inspection_id`),
  KEY `idx_user_created` (`user_id`, `created_at`),
  CONSTRAINT `fk_rect_inspection` FOREIGN KEY (`inspection_id`) REFERENCES `inspections` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rect_user`       FOREIGN KEY (`user_id`)       REFERENCES `users` (`id`)
) ENGINE=InnoDB COMMENT='整改记录表';

-- ======================================================================
-- 种子数据（密码均为 123456，登录后请自行修改）
-- ======================================================================
INSERT INTO `users` (`username`, `password`, `real_name`, `role`) VALUES
('admin',    '$2y$10$aIGAmQdJwbEI5Sr9WDrzLOCl9JZ3Gcp7kYaqUCp5EZHfX6aYv3Kwu', '张管理', 'admin'),
('boss',     '$2y$10$aIGAmQdJwbEI5Sr9WDrzLOCl9JZ3Gcp7kYaqUCp5EZHfX6aYv3Kwu', '王老板', 'boss'),
('zhangsan', '$2y$10$aIGAmQdJwbEI5Sr9WDrzLOCl9JZ3Gcp7kYaqUCp5EZHfX6aYv3Kwu', '张三',   'staff'),
('lisi',     '$2y$10$aIGAmQdJwbEI5Sr9WDrzLOCl9JZ3Gcp7kYaqUCp5EZHfX6aYv3Kwu', '李四',   'staff');

INSERT INTO `areas` (`name`, `code`, `sort`) VALUES
('A区-原材料库', 'A01', 1),
('B区-成品库',   'B02', 2),
('C区-分拣区',   'C03', 3),
('D区-发货月台', 'D04', 4);

INSERT INTO `inspection_items` (`name`, `category`, `deduct_score`) VALUES
('通道堆放杂物',         '整理', 2.0),
('货物未按标识定位摆放', '整顿', 2.0),
('消防通道被占用',       '整顿', 5.0),
('地面有垃圾、积水',     '清扫', 1.0),
('货架积尘未清洁',       '清洁', 1.0),
('未佩戴安全帽',         '素养', 3.0);
