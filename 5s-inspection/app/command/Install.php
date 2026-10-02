<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;

/**
 * 一键初始化: 创建表结构 + 写入演示数据
 * 用法: php think install
 */
class Install extends Command
{
    protected function configure(): void
    {
        $this->setName('install')
            ->addOption('force')
            ->setDescription('创建5S检查表结构并写入演示数据(默认密码123456)');
    }

    protected function execute(Input $input, Output $output): void
    {
        $force = (bool)$input->getOption('force');
        $sqlFile = root_path() . 'database' . DIRECTORY_SEPARATOR . 'install.sql';
        if (!is_file($sqlFile)) {
            $output->error('未找到 database/install.sql');
            return;
        }

        $exists = Db::query("SHOW TABLES LIKE '" . config('database.connections.mysql.prefix', 'ws5s_') . "user'");
        if ($exists && !$force) {
            $output->warning('数据表已存在。如需覆盖请执行: php think install --force');
            return;
        }

        $sql = file_get_contents($sqlFile) ?: '';
        // 兼容 PDO::exec 一次执行一条
        foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) {
            if ($stmt === '' || str_starts_with($stmt, '--')) {
                continue;
            }
            Db::execute($stmt);
        }

        $output->info('数据表与演示数据初始化完成 ✔');
        $output->info('管理员 admin / 老板 boss / 员工 zhangsan、lisi, 密码均为 123456');
    }
}
