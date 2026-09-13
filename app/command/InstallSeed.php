<?php
declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * 安装初始化：
 *   1) 读取 sql/schema.sql
 *   2) 将占位符替换为真实 bcrypt 哈希
 *   3) 输出为 sql/install.lock.sql（供 mysql 导入）
 *
 * 用法： php think install:seed
 *       php think install:seed --admin-pass=admin123 --boss-pass=boss123 --pin=123
 */
class InstallSeed extends Command
{
    protected function configure(): void
    {
        $this->setName('install:seed')
            ->addOption('admin-pass', null, Option::VALUE_OPTIONAL, '管理员密码', 'admin123')
            ->addOption('boss-pass', null, Option::VALUE_OPTIONAL, '老板密码', 'boss123')
            ->addOption('pin', null, Option::VALUE_OPTIONAL, '员工整改口令', '1234')
            ->setDescription('生成含安全口令哈希的安装 SQL');
    }

    protected function execute(Input $input, Output $output): int
    {
        $source = root_path() . 'sql' . DIRECTORY_SEPARATOR . 'schema.sql';
        $target = root_path() . 'sql' . DIRECTORY_SEPARATOR . 'install.lock.sql';

        if (!is_file($source)) {
            $output->error('找不到 sql/schema.sql');
            return 1;
        }

        $sql = file_get_contents($source);

        $sql = str_replace('__BCRYPT_ADMIN__', password_hash($input->getOption('admin-pass'), PASSWORD_DEFAULT), $sql);
        $sql = str_replace('__BCRYPT_BOSS__',  password_hash($input->getOption('boss-pass'), PASSWORD_DEFAULT), $sql);
        $sql = str_replace('__BCRYPT_PIN__',   password_hash($input->getOption('pin'), PASSWORD_DEFAULT), $sql);

        file_put_contents($target, $sql);

        $output->writeln('<info>已生成 ' . $target . '</info>');
        $output->writeln('导入命令： mysql -u root -p < sql/install.lock.sql');
        $output->writeln('默认账号： admin/' . $input->getOption('admin-pass') . '  boss/' . $input->getOption('boss-pass'));
        $output->writeln('员工整改口令： ' . $input->getOption('pin'));

        return 0;
    }
}
