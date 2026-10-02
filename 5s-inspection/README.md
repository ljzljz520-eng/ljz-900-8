# 🏭 仓库现场 5S 检查整改网站（ThinkPHP 8 + MySQL）

面向仓库现场管理的 **5S（整理 / 整顿 / 清扫 / 清洁 / 素养）问题检查与整改闭环** 系统：

- 👷 **管理员**：上传问题照片、选择检查项与扣分、指定区域/责任人、打印区域整改二维码
- 📱 **仓库员工**：微信/相机扫码 → 看到本区域待整改问题 → 现场拍照提交整改（移动端优先）
- 👔 **老板**：按 **员工 / 区域 / 日期** 多维筛选，查看「问题图 → 整改图」图片对；未整改项显示占位提醒

---

## 一、功能总览

| 角色 | 入口 | 主要功能 |
| --- | --- | --- |
| 管理员 `admin` | `/admin` | 看板统计、发布问题（问题图+检查项+扣分）、区域与二维码、检查项维护 |
| 员工 `zhangsan` / `lisi` | 扫二维码 `/scan/:token`、`/employee/tasks` | 查看待整改、拍照上传整改图、整改前后对比 |
| 老板 `boss` | `/boss` | 员工/区域/日期筛选、图片对浏览、未整改占位、整改排行、扣分汇总 |

> 三种角色使用同一登录页 `/login`，登录后按角色自动跳转。演示账号密码统一为 **`123456`**。

## 二、技术栈

- 后端：**ThinkPHP 8.1**（PHP ≥ 8.0）、think-orm v4、think-view
- 数据库：**MySQL 5.7 / 8.0**（utf8mb4，表前缀 `ws5s_`）
- 前端：原生 HTML/CSS/JS（无构建步骤），手机端自适应，图片本地预览 + 拍照上传
- 二维码：二维码页内置在线生成接口（可替换为 `endroid/qr-code` 等本地方案用于内网）

## 三、目录结构（重点）

```
5s-inspection/
├── app/
│   ├── controller/
│   │   ├── Auth.php              登录/登出/角色跳转
│   │   ├── admin/                管理员: Dashboard / Inspection / Area / CheckItem
│   │   ├── employee/             员工端: Scan(扫码) / Task(整改)
│   │   └── boss/Report.php       老板端多维报表
│   ├── model/                    User / Area / CheckItem / Inspection / Rectification
│   ├── middleware/               Auth 基类 + Admin / Employee / Boss 角色中间件
│   └── command/Install.php       php think install 一键初始化
├── config/                       database / filesystem(uploads磁盘) / view / session ...
├── database/install.sql          表结构 + 演示数据
├── public/
│   ├── storage/demo/             演示问题图/整改图(SVG)
│   ├── uploads/{problem,repair}/ 真实上传目录
│   └── static/{css,js}           全局样式与交互
├── route/app.php                 全部路由(分组+角色中间件)
└── view/                         auth / admin / employee / boss 模板
```

## 四、安装部署

```bash
# 1. 安装依赖(已带 vendor 可跳过)
composer install

# 2. 配置环境变量
cp .env.example .env
#   修改 .env 中 MySQL 的 HOSTNAME / DATABASE / USERNAME / PASSWORD

# 3. 创建数据库(任选其一)
#    a) 手工导入:
mysql -uroot -p -e "CREATE DATABASE warehouse_5s DEFAULT CHARSET utf8mb4;"
mysql -uroot -p warehouse_5s < database/install.sql
#    b) 或命令行一键建表+演示数据(需数据库已创建):
php think install            # 已存在表时加 --force 覆盖

# 4. 启动
php think run                # 默认 http://127.0.0.1:8000
#   生产环境用 Nginx/Apache, 站点根目录指向 public/
```

> ⚠️ 确保 `public/uploads/`、`runtime/` 对 PHP 运行用户可写。

### Nginx 伪静态（pathinfo）

```nginx
location / {
    if (!-e $request_filename) {
        rewrite ^(.*)$ /index.php?s=$1 last;
    }
}
```

## 五、业务闭环说明

1. 管理员在「区域与二维码」打印二维码，张贴到仓库各区域现场。
2. 巡检时管理员「发布问题」：选区域、检查项、扣分，上传问题图，可指定责任人与期限。
3. 员工用手机扫码进入区域页，看到待整改问题（含问题图与描述），点「去整改」拍摄整改后照片提交。
4. 系统写入整改记录并把问题置为「已整改」，形成 **问题图 → 整改图** 图片对。
5. 老板在看板按 **员工（整改人）/ 区域 / 检查日期区间 / 状态** 筛选，查看图片对、整改进度、扣分与员工排行；未整改项以 🚧 占位卡片明确提醒。

## 六、数据库设计（5 张表）

| 表 | 说明 | 关键字段 |
| --- | --- | --- |
| `ws5s_user` | 用户（管理员/员工/老板） | `role` 1管理员 2员工 3老板、bcrypt 密码 |
| `ws5s_area` | 仓库区域 | `scan_token` 唯一扫码令牌、状态、排序 |
| `ws5s_check_item` | 5S 检查项字典 | `category`（SEIRI/SEITON/SEISO/SEIKETSU/SHITSUKE）、默认扣分 |
| `ws5s_inspection` | 检查问题记录 | 区域、检查项、检查人、责任人、问题图、扣分、状态、检查日期、期限 |
| `ws5s_rectification` | 整改记录 | 与问题 1:1（`inspection_id` 唯一）、整改人、整改图、整改时间 |

设计要点：外键约束保证引用完整性；`status + inspect_date`、`assignee_id`、`area_id`、`scan_token`
等高频查询字段均建立索引；问题与整改一对一，便于直接渲染图片对。

## 七、安全与约束

- 密码使用 `password_hash` (bcrypt) 存储，登录校验 `password_verify`
- 三个角色独立中间件强制鉴权，越权返回 403
- 上传校验：仅 `jpg/jpeg/png/gif/webp`、最大 8MB、MIME + 扩展双重校验，按目录隔离
- 表单服务端校验区域/检查项/扣分(0~100)/日期格式；AJAX 统一返回 `{code,msg,url}`

## 八、演示账号

| 角色 | 账号 | 密码 |
| --- | --- | --- |
| 管理员 | admin | 123456 |
| 老板 | boss | 123456 |
| 员工 | zhangsan / lisi | 123456 |
