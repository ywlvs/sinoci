<?php

namespace App\Services;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Events\EventServiceProvider;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Facade;
use Illuminate\View\ViewServiceProvider;

class Laravel
{

    // Laravel 容器
    private $container;

    public function __construct()
    {
        // 初始化容器
        $this->container = new Container;

        // 循环开启功能
        array_filter(config('use_laravel'), function ($v) {
            call_user_func([$this, 'use' . $v]);
        });

        // 绑定应用容器
        Facade::setFacadeApplication($this->container);
    }

    public function useEloquent()
    {
        // 初始化相关服务
        $this->bootService(EventServiceProvider::class);

        // 加载数据库配置
        load_class('Config', 'core')->load('database');
        load_class('Config', 'core')->load('pagination', true);

        // 修复分页页码
        Paginator::currentPageResolver(function () {
            return app()->input->get('page') ?: 1;
        });

        // 定制分页样式
        Paginator::presenter(function ($paginator) {

            // 修复分页路径
            $paginator->setPath(null);

            // 添加已有参数
            $paginator->appends(array_except(app()->input->get(), 'page'));

            // 返回分页模板
            return new Pagination($paginator);
        });

        // 初始化 Eloquent
        $manager = new Manager($this->container);
        $manager->addConnection(config('db'));
        $manager->setAsGlobal();
        $manager->bootEloquent();

        // 调试状态下开启查询日志
        APP_DEBUG && $manager->getConnection()->enableQueryLog();
    }

    public function useBlade()
    {
        // 模板相关配置
        $this->container['config']['view.paths'] = [VIEWPATH];
        $this->container['config']['view.compiled'] = config('cache_path');

        // 初始化相关服务
        $this->bootService(FilesystemServiceProvider::class);
        $this->bootService(ViewServiceProvider::class);
    }

    public function bootService($provider)
    {
        // 获取服务提供者
        $service = new $provider($this->container);

        // 注册相关服务
        $service->register();
    }

}
