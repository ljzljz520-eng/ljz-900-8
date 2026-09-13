<?php
declare(strict_types=1);

namespace app\middleware;

use Closure;
use think\Request;
use think\Response;

/**
 * 解析 application/json 请求体到 Request 的 POST 数据
 */
class JsonBody
{
    public function handle(Request $request, Closure $next): Response
    {
        $contentType = strtolower((string) $request->contentType());

        if (str_contains($contentType, 'application/json')) {
            $content = $request->getInput();
            if ($content !== '') {
                $data = json_decode($content, true);
                if (is_array($data)) {
                    // 合并到 POST，供 $request->post() / 依赖注入的 Request 读取
                    $request->withPost(array_merge($request->post(), $data));
                }
            }
        }

        return $next($request);
    }
}
