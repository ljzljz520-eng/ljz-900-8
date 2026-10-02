-- =====================================================================
-- 仓库现场 5S 检查网站  数据库结构 + 演示数据
-- 适配 MySQL 8.0+ / MySQL 5.7  字符集 utf8mb4
-- 默认表前缀: ws5s_
-- 账号统一密码: 123456
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- 用户表: 管理员 / 仓库员工 / 老板 三种角色
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `ws5s_user`;
CREATE TABLE `ws5s_user` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `username`      VARCHAR(50)  NOT NULL COMMENT '登录账号',
  `password`      VARCHAR(255) NOT NULL COMMENT '密码(bcrypt)',
  `real_name`     VARCHAR(50)  NOT NULL COMMENT '姓名/昵称',
  `role`          TINYINT UNSIGNED NOT NULL DEFAULT 2 COMMENT '角色:1管理员 2员工 3老板',
  `phone`         VARCHAR(20)  DEFAULT NULL COMMENT '手机号',
  `status`        TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态:1启用 0停用',
  `create_time`   DATETIME DEFAULT NULL COMMENT '创建时间',
  `update_time`   DATETIME DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_role_status` (`role`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统用户(管理员/员工/老板)';

-- ---------------------------------------------------------------------
-- 仓库区域表: 每个区域生成独立扫码整改二维码(scan_token)
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `ws5s_area`;
CREATE TABLE `ws5s_area` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '区域ID',
  `name`          VARCHAR(100) NOT NULL COMMENT '区域名称 如:原料仓A区',
  `code`          VARCHAR(50)  DEFAULT NULL COMMENT '区域编码',
  `location`      VARCHAR(200) DEFAULT NULL COMMENT '位置描述',
  `scan_token`    CHAR(32)     NOT NULL COMMENT '扫码令牌(二维码内容)',
  `status`        TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态:1启用 0停用',
  `sort`          INT NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time`   DATETIME DEFAULT NULL,
  `update_time`   DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_scan_token` (`scan_token`),
  KEY `idx_status_sort` (`status`,`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='仓库检查区域';

-- ---------------------------------------------------------------------
-- 5S 检查项字典
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `ws5s_check_item`;
CREATE TABLE `ws5s_check_item` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '检查项ID',
  `category`      ENUM('SEIRI','SEITON','SEISO','SEIKETSU','SHITSUKE') NOT NULL COMMENT '5S分类:整理/整顿/清扫/清洁/素养',
  `title`         VARCHAR(200) NOT NULL COMMENT '检查项标题',
  `standard`      VARCHAR(500) DEFAULT NULL COMMENT '判定标准说明',
  `default_score` DECIMAL(5,2) UNSIGNED NOT NULL DEFAULT 10.00 COMMENT '默认扣分值',
  `status`        TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '状态:1启用 0停用',
  `sort`          INT NOT NULL DEFAULT 0 COMMENT '排序',
  `create_time`   DATETIME DEFAULT NULL,
  `update_time`   DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category_status` (`category`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='5S检查项字典';

-- ---------------------------------------------------------------------
-- 检查问题记录(管理员创建): 问题图 + 检查项 + 扣分
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `ws5s_inspection`;
CREATE TABLE `ws5s_inspection` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '问题记录ID',
  `inspect_no`     VARCHAR(32)  NOT NULL COMMENT '问题单号 如 INSP202610020001',
  `area_id`        INT UNSIGNED NOT NULL COMMENT '所在区域',
  `check_item_id`  INT UNSIGNED NOT NULL COMMENT '检查项',
  `inspector_id`   INT UNSIGNED NOT NULL COMMENT '检查人(管理员)',
  `assignee_id`    INT UNSIGNED DEFAULT NULL COMMENT '指定整改员工,空=区域内任意员工可领',
  `problem_image`  VARCHAR(255) NOT NULL COMMENT '问题图片(存储相对路径)',
  `description`    VARCHAR(500) DEFAULT NULL COMMENT '问题描述',
  `deduct_score`   DECIMAL(5,2) UNSIGNED NOT NULL DEFAULT 0.00 COMMENT '扣分',
  `status`         TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '0待整改 1已整改',
  `inspect_date`   DATE NOT NULL COMMENT '检查日期',
  `deadline`       DATE DEFAULT NULL COMMENT '整改期限',
  `create_time`    DATETIME DEFAULT NULL,
  `update_time`    DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inspect_no` (`inspect_no`),
  KEY `idx_area` (`area_id`),
  KEY `idx_assignee` (`assignee_id`),
  KEY `idx_status_date` (`status`,`inspect_date`),
  KEY `idx_check_item` (`check_item_id`),
  CONSTRAINT `fk_insp_area`    FOREIGN KEY (`area_id`)       REFERENCES `ws5s_area` (`id`),
  CONSTRAINT `fk_insp_item`    FOREIGN KEY (`check_item_id`) REFERENCES `ws5s_check_item` (`id`),
  CONSTRAINT `fk_insp_user`    FOREIGN KEY (`inspector_id`)  REFERENCES `ws5s_user` (`id`),
  CONSTRAINT `fk_insp_assignee` FOREIGN KEY (`assignee_id`)  REFERENCES `ws5s_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='5S检查问题记录';

-- ---------------------------------------------------------------------
-- 整改记录(员工扫码提交): 整改图, 与问题 1:1
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `ws5s_rectification`;
CREATE TABLE `ws5s_rectification` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '整改记录ID',
  `inspection_id`    INT UNSIGNED NOT NULL COMMENT '对应问题记录',
  `repairer_id`      INT UNSIGNED NOT NULL COMMENT '整改员工',
  `repair_image`     VARCHAR(255) NOT NULL COMMENT '整改图片(存储相对路径)',
  `remark`           VARCHAR(500) DEFAULT NULL COMMENT '整改说明',
  `repair_time`      DATETIME NOT NULL COMMENT '整改提交时间',
  `create_time`      DATETIME DEFAULT NULL,
  `update_time`      DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inspection` (`inspection_id`),
  KEY `idx_repairer` (`repairer_id`),
  CONSTRAINT `fk_rect_insp`  FOREIGN KEY (`inspection_id`) REFERENCES `ws5s_inspection` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_rect_user`  FOREIGN KEY (`repairer_id`)  REFERENCES `ws5s_user` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='问题整改记录';

-- =====================================================================
-- 演示数据
-- =====================================================================

-- 用户(密码均为 123456)
INSERT INTO `ws5s_user` (`id`,`username`,`password`,`real_name`,`role`,`phone`,`status`,`create_time`,`update_time`) VALUES
(1,'admin',   '$2y$10$k8ppUhs0oQEobe036XtHMua9EnUfbaqHqQ316OYpm9VyEf.rBM4BS','系统管理员',1,'13800000001',1,NOW(),NOW()),
(2,'boss',    '$2y$10$k8ppUhs0oQEobe036XtHMua9EnUfbaqHqQ316OYpm9VyEf.rBM4BS','王总',    3,'13800000002',1,NOW(),NOW()),
(3,'zhangsan','$2y$10$k8ppUhs0oQEobe036XtHMua9EnUfbaqHqQ316OYpm9VyEf.rBM4BS','张三',    2,'13900000001',1,NOW(),NOW()),
(4,'lisi',    '$2y$10$k8ppUhs0oQEobe036XtHMua9EnUfbaqHqQ316OYpm9VyEf.rBM4BS','李四',    2,'13900000002',1,NOW(),NOW());

-- 区域
INSERT INTO `ws5s_area` (`id`,`name`,`code`,`location`,`scan_token`,`status`,`sort`,`create_time`,`update_time`) VALUES
(1,'原料仓A区','YL-A','一号厂房一层东侧','a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6',1,10,NOW(),NOW()),
(2,'成品仓B区','CP-B','一号厂房一层西侧','b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7',1,20,NOW(),NOW()),
(3,'包材区',  'BC-C','二号厂房二层',     'c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8',1,30,NOW(),NOW());

-- 5S 检查项
INSERT INTO `ws5s_check_item` (`id`,`category`,`title`,`standard`,`default_score`,`status`,`sort`,`create_time`,`update_time`) VALUES
(1,'SEIRI',   '通道无杂物堵塞',     '通道保持畅通,无闲置物料、卡板占用',10.00,1,10,NOW(),NOW()),
(2,'SEIRI',   '清除无用物品',       '现场无报废品、长期滞留物料',     10.00,1,20,NOW(),NOW()),
(3,'SEITON',  '物料定点定位摆放',   '物料按区域线、标识牌定点存放',   8.00, 1,30,NOW(),NOW()),
(4,'SEITON',  '货架标识清晰',       '货架/货位标识完整、朝外可读',    5.00, 1,40,NOW(),NOW()),
(5,'SEISO',   '地面清洁无污渍',     '地面无油污、积水、灰尘堆积',     8.00, 1,50,NOW(),NOW()),
(6,'SEISO',   '设备表面无积尘',     '设备、工作台无明显积尘杂物',     5.00, 1,60,NOW(),NOW()),
(7,'SEIKETSU','消防器材前无遮挡',   '灭火器/消防栓前1米无遮挡',      15.00,1,70,NOW(),NOW()),
(8,'SEIKETSU','目视化看板更新',     '看板内容按周更新,信息完整',     5.00, 1,80,NOW(),NOW()),
(9,'SHITSUKE','劳保用品按规佩戴',   '进入作业区按规定穿戴劳保用品',   10.00,1,90,NOW(),NOW()),
(10,'SHITSUKE','作业纪律',          '无玩手机、串岗、睡岗现象',       10.00,1,100,NOW(),NOW());

-- 检查问题(前2条已整改,第3条待整改用于占位提醒演示)
INSERT INTO `ws5s_inspection`
(`id`,`inspect_no`,`area_id`,`check_item_id`,`inspector_id`,`assignee_id`,`problem_image`,`description`,`deduct_score`,`status`,`inspect_date`,`deadline`,`create_time`,`update_time`) VALUES
(1,'INSP20261001001',1,1,1,3,'demo/before-1.svg','主通道被闲置卡板占用,影响叉车通行',10.00,1,'2026-10-01','2026-10-03',NOW(),NOW()),
(2,'INSP20261001002',2,5,1,4,'demo/before-2.svg','成品仓地面有油污,存在滑倒隐患',   8.00, 1,'2026-10-01','2026-10-02',NOW(),NOW()),
(3,'INSP20261002001',3,7,1,NULL,'demo/before-3.svg','包材区灭火器前堆放纸箱,遮挡消防器材',15.00,0,'2026-10-02','2026-10-04',NOW(),NOW());

-- 整改记录
INSERT INTO `ws5s_rectification`
(`id`,`inspection_id`,`repairer_id`,`repair_image`,`remark`,`repair_time`,`create_time`,`update_time`) VALUES
(1,1,3,'demo/after-1.svg','卡板已移至存放区,通道恢复畅通',          '2026-10-02 09:20:00',NOW(),NOW()),
(2,2,4,'demo/after-2.svg','油污已清理并撒吸油粉,设置防滑警示牌',    '2026-10-02 10:05:00',NOW(),NOW());

SET FOREIGN_KEY_CHECKS = 1;
