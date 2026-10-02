# 仓库现场 5S 检查整改系统

基于 **ThinkPHP8 + MySQL** 的仓库现场 5S（整理/整顿/清扫/清洁/素养）检查整改系统。

## 功能一览

| 角色 | 功能 |
| --- | --- |
| 管理员 admin | 发布问题（上传问题图 + 选择检查项 + 扣分）、检查项管理、区域二维码管理、问题记录查询/删除 |
| 仓库员工 staff | 扫描区域二维码 → 查看该区域待整改问题 → 上传整改后照片完成整改 |
| 老板 boss | 整改报告：按 **员工 / 区域 / 日期** 筛选查看整改前后**图片对**，未整改项显示**占位提醒**，全局统计 |

## 技术栈

- ThinkPHP 8（单应用模式，控制器按角色分目录）
- think-orm（模型关联：belongsTo / hasOne / hasMany）
- think-view（Think 模板引擎）
- MySQL 5.7+ / 8.0（InnoDB + utf8mb4 + 外键约束）

## 目录结构

```
├── app/
│   ├── controller/
│   │   ├── Index.php              # 入口：按角色跳转工作台
│   │   ├── Auth.php               # 登录/退出
│   │   ├── admin/                 # 管理员端
│   │   │   ├── Inspection.php     #   发布问题（问题图+检查项+扣分）
│   │   │   ├── Item.php           #   检查项管理
│   │   │   └── Area.php           #   区域与二维码
│   │   ├── staff/
│   │   │   └── Rectify.php        # 员工端：扫码整改
│   │   └── boss/
│   │       └── Report.php         # 老板端：图片对报告
│   ├── middleware/Auth.php        # 登录与角色校验中间件
│   ├── model/                     # User/Area/InspectionItem/Inspection/Rectification
│   └── view/                      # 模板（按角色分目录）
├── config/                        # 配置文件
├── route/app.php                  # 路由（按角色分组 + 中间件）
├── public/                        # 入口目录（index.php / static / storage上传目录）
├── database.sql                   # 建库建表 + 种子数据
└── composer.json
```

## 数据库设计

| 表 | 说明 | 关键字段 |
| --- | --- | --- |
| `users` | 用户表 | `role`（admin/staff/boss）、`username` 唯一 |
| `areas` | 区域表 | `code` 唯一，用于二维码内容 |
| `inspection_items` | 检查项表 | `category`（5S分类枚举）、`deduct_score` 扣分标准 |
| `inspections` | 问题记录表 | `problem_image`、`deduct_score`、`status`(0待整改/1已整改) |
| `rectifications` | 整改记录表 | `inspection_id` 唯一（一对一）、`rectified_image`、`user_id` 整改人 |

设计要点：

- `inspections` 与 `rectifications` **一对一**（`uk_inspection` 唯一索引），整改提交用事务同时写整改记录并更新问题状态，保证"图片对"完整；
- 外键约束齐全（删除问题时整改记录级联删除 `ON DELETE CASCADE`）；
- 常用筛选维度均建索引：`(area_id,status)`、`(status,created_at)`、`(user_id,created_at)`；
- 时间戳由 MySQL `DEFAULT CURRENT_TIMESTAMP` 维护。

## 安装部署

```bash
# 1. 安装依赖（PHP >= 8.0）
composer install

# 2. 创建数据库并导入
mysql -uroot -p < database.sql

# 3. 配置数据库连接
cp .env.example .env   # 修改 [DATABASE] 段的账号密码

# 4. 启动（开发）
php think run -p 8000
# 生产环境请将 Web 根目录指向 public/，并确保 runtime/ 与 public/storage/ 可写
```

访问 http://localhost:8000

## 默认账号（密码均为 `123456`）

| 账号 | 角色 |
| --- | --- |
| admin | 管理员 |
| boss | 老板 |
| zhangsan / lisi | 仓库员工 |

## 使用流程

1. **管理员**登录 →「区域二维码」添加区域，页面会生成二维码（内容为 `/staff/scan?code=区域编码`），打印张贴到对应区域；
2. **管理员**「发布问题」：选区域、检查项（自动带出标准扣分，可改）、上传问题照片、可指定整改人；
3. **员工**手机扫码 → 登录（自动跳回扫码页）→ 看到该区域待整改列表 → 拍照上传整改后照片；
4. **老板**打开「整改报告」：按员工/区域/日期/状态筛选，查看整改前后图片对；**未整改项显示虚线占位框和红色提醒**，顶部有全局统计。

## 说明

- 上传图片存放在 `public/storage/`（问题图 `problem/`、整改图 `rectified/`），限制 jpg/png/gif/webp 且 ≤10MB；
- 二维码使用 qrcodejs（CDN）在前端生成，离线内网可改用任意工具按"扫码链接"生成；
- 登录后密码修改、用户管理等可在 `users` 表直接维护或按需扩展。
