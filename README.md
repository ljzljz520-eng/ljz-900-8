# 仓库现场 5S 检查系统

基于 **ThinkPHP 8 + MySQL 8** 的仓库现场 5S（整理 / 整顿 / 清扫 / 清洁 / 素养）检查闭环系统：

1. **管理员**巡检发现问题 → 上传**问题图**、选择**检查项**和**扣分** → 系统生成该问题单专属二维码；
2. **仓库员工**用手机扫描现场二维码 → 选择本人、输入 4 位整改口令 → 拍照上传**整改后图**完成整改；
3. **老板**在看板按 **员工 / 区域 / 日期** 查看「问题图 → 整改图」**图片对**、扣分排行与整改率；
4. **未整改项**在详情、列表、老板看板、整改页均显示醒目的 ⏳ 占位提醒。

---

## 一、角色与页面

| 角色 | 入口 | 功能 |
| --- | --- | --- |
| 管理员 | `/login`（admin） | 工作台统计、现场开单（问题图+检查项+扣分）、问题单列表/筛选、打印/查看二维码、复核通过/驳回、区域/检查项/员工基础数据维护 |
| 仓库员工 | 扫码 `/r/{code}`（无需登录） | 查看问题图与要求，凭 4 位口令提交整改后照片 |
| 老板 | `/login`（boss） | `/boss` 只读看板：员工、区域、日期三维度筛选，图片对卡片、TOP10 扣分排行、区域统计 |

> 默认账号：管理员 `admin / admin123`，老板 `boss / boss123`，员工整改口令默认 `1234`。
> **生产环境务必修改。**

---

## 二、业务流程

```
管理员现场开单                 员工扫码整改                    复核闭环
─────────────                ─────────────                 ─────────────
选区域/检查项/扣分     ──▶   微信/相机扫现场二维码    ──▶    管理员看图片对
上传问题图                    选择本人 + 输入4位口令          ✅ 复核通过(状态2)
生成二维码(单号 code)          拍摄上传整改后照片             ❌ 驳回(清空整改图)
状态: 待整改(0)               状态: 已整改(1)                 员工可重新扫码整改
```

---

## 三、目录结构（多应用分层）

```
.
├── app/
│   ├── admin/            管理端应用
│   │   ├── controller/   Index(工作台) Issue Area CheckItem Employee
│   │   └── validate/     IssueValidate
│   ├── rectify/          员工扫码整改（移动端）
│   │   └── controller/   Rectify
│   ├── boss/             老板看板（只读）
│   │   └── controller/   Boss
│   ├── auth/             登录/退出
│   ├── common/
│   │   ├── model/        AdminUser Area Employee CheckItem Issue
│   │   └── service/      QrcodeService(二维码) UploadService(图片)
│   ├── command/          install:seed 安装命令
│   ├── middleware/       Auth(角色鉴权) JsonBody
│   ├── BaseController.php
│   └── common.php        img_url/status_text 等公共函数
├── config/               TP8 配置（database/session/view ...）
├── route/app.php         全部路由（角色中间件）
├── sql/schema.sql        建表 + 基础数据（口令占位符）
├── public/
│   ├── index.php         Web 入口
│   ├── router.php        内置服务器路由
│   ├── static/css,js     前端资源
│   └── uploads/          上传目录（issues 问题图 / rectify 整改图）
└── view/                 ThinkTemplate 模板
    ├── layout/base.html
    ├── auth/ admin/ rectify/ boss/
```

---

## 四、数据库设计（表前缀 `wa_`）

| 表 | 说明 | 关键字段 |
| --- | --- | --- |
| `wa_admin_user` | 后台用户 | `role` 1管理员 / 2老板，`password` 为 password_hash |
| `wa_area` | 仓库区域 | 名称、编码、负责人、排序、状态 |
| `wa_employee` | 整改员工 | 工号(唯一)、`pin` 4位口令哈希、所属区域 |
| `wa_check_item` | 5S 检查项 | `category`(整理/整顿/清扫/清洁/素养)、内容、标准扣分 |
| `wa_issue` | **问题单（核心）** | `code` 单号(二维码)、`area_id`、`check_item_id`、`inspector_id`、`employee_id`、`check_date`、`deduct_score`、`photo_before` 问题图、`photo_after` 整改图(NULL=未整改)、`rectified_at`、`status` 0待整改/1已整改/2已复核 |

索引设计：`uk_code`、`idx_area_date(area_id,check_date)`、`idx_employee`、`idx_status_date(status,check_date)`，支撑老板看板按区域/员工/日期的组合查询。

> 图片对 = `wa_issue` 一行中的 `photo_before` ↔ `photo_after`；未整改时 `photo_after IS NULL`，前端用占位块渲染提醒。

---

## 五、安装部署

### 1. 环境要求
- PHP **>= 8.0**，扩展：`pdo_mysql`、`gd`（二维码）、`mbstring`、`fileinfo`、`json`
- MySQL 5.7 / 8.0
- Composer 2.x

### 2. 获取依赖
```bash
composer install
```

### 3. 配置环境变量
```bash
cp .env.example .env
# 编辑 .env：数据库地址、库名、账号、密码、表前缀 wa_
```

### 4. 初始化数据库（二选一）

**方式 A：命令生成含安全口令哈希的安装 SQL（推荐）**
```bash
php think install:seed                      # 默认口令
# 或自定义： php think install:seed --admin-pass=你的密码 --boss-pass=你的密码 --pin=4位口令
mysql -u root -p < sql/install.lock.sql
```

**方式 B：先导入再手工设置密码**
```bash
mysql -u root -p < sql/schema.sql
```
> `schema.sql` 中口令为占位符，导入后请用应用内修改或自行执行
> `UPDATE wa_admin_user SET password='<bcrypt>' ...` 重置。

### 5. 目录权限
```bash
chmod -R 755 runtime public/uploads
```

### 6. 启动

开发（内置服务器）：
```bash
php -S 0.0.0.0:8000 -t public public/router.php
```

Nginx（站点根目录指向 `public/`）：
```nginx
server {
    listen 80;
    server_name 5s.example.com;
    root /var/www/inspection5s/public;
    index index.php;

    location / {
        if (!-e $request_filename) {
            rewrite ^(.*)$ /index.php?s=$1 last;
        }
    }
    location ~ \.php$ {
        fastcgi_pass   unix:/run/php/php8.2-fpm.sock;
        fastcgi_index  index.php;
        fastcgi_param  SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include        fastcgi_params;
    }
    location ^~ /uploads/ { expires 7d; }
}
```

---

## 六、二维码说明

- 每张问题单的二维码内容为：`http(s)://域名/r/{code}`（如 `/r/5S260913A1B2C3`）。
- 二维码由 `endroid/qr-code` 服务端实时生成（GD），地址：
  `GET /admin/issues/{id}/qrcode`，无需落地文件；问题详情页直接展示，可右键保存打印。

## 七、安全要点

- 后台所有写操作走 `Auth` 中间件 + 角色区分（老板账号无法进入管理端）。
- 员工整改无后台会话，以「员工 + 4 位口令（bcrypt 校验）」做轻量身份确认，口令不可见、不明文存储。
- 上传校验扩展名（jpg/jpeg/png/webp/gif）与大小（≤10MB），文件名随机化。
- 模板默认转义输出；生产环境 `APP_DEBUG=false`。
