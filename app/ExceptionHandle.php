<?php
declare(strict_types=1);

namespace app;

use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理
 */
class ExceptionHandle extends Handle
{
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ValidateException::class,
    ];

    public function report(Throwable $exception): void
    {
        parent::report($exception);
    }

    public function render($request, Throwable $e): Response
    {
        // 调试模式交给框架渲染详细页
        if (env('app.debug') || ($this->app->isDebug() ?? false)) {
            return parent::render($request, $e);
        }

        if ($request->isAjax()) {
            return json([
                'code' => $e->getCode() ?: 500,
                'msg'  => $e->getMessage() ?: '服务器内部错误',
            ], $e instanceof HttpException ? $e->getStatusCode() : 500);
        }

        if ($e instanceof HttpException && $e->getStatusCode() === 404) {
            return Response::create('404 Not Found', 'html', 404);
        }

        return Response::create(
            '<h2>系统繁忙</h2><p>' . htmlspecialchars($e->getMessage()) . '</p>',
            'html',
            500
        );
    }
}
