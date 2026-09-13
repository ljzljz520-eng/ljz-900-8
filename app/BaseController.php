<?php
declare(strict_types=1);

namespace app;

use think\App;
use think\exception\ValidateException;
use think\Validate;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
        $this->initialize();
    }

    protected function initialize(): void
    {
    }

    /**
     * 验证数据
     * @param array        $data
     * @param string|array $validate 验证器类名或规则数组
     */
    protected function validateData(array $data, $validate, array $message = [], bool $batch = false): array
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            $v = new $validate();
        }
        if ($message) {
            $v->message($message);
        }
        if ($batch) {
            $v->batch(true);
        }
        if (!$v->failException(true)->check($data)) {
            throw new ValidateException($v->getError());
        }
        return $data;
    }
}
