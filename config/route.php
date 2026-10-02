<?php
// +----------------------------------------------------------------------
// | 路由设置
// +----------------------------------------------------------------------
return [
    // pathinfo分隔符
    'pathinfo_depr'           => '/',
    // 是否开启路由延迟解析
    'url_lazy_route'          => false,
    // 是否强制使用路由
    'url_route_must'          => false,
    // 合并路由规则
    'route_rule_merge'        => false,
    // 路由是否完全匹配
    'route_complete_match'    => false,
    // 访问控制器层名称
    'controller_layer'        => 'controller',
    // 空控制器名
    'empty_controller'        => '',
    // 是否使用控制器后缀
    'controller_suffix'       => false,
    // 默认控制器名
    'default_controller'      => 'Index',
    // 默认操作名
    'default_action'          => 'index',
    // 操作方法后缀
    'action_suffix'           => '',
    // 默认JSONP格式返回的处理方法
    'jsonp_handler'           => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'       => 'callback',
    // URL伪静态后缀
    'url_html_suffix'         => 'html',
    // 自动搜索控制器
    'controller_auto_search'  => true,
];
