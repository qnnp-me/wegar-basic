# Wegar Basic

- [初始化任务自动加载](#load-init-task)
- [数据库迁移自动加载](#load-migration)
- [Cron任务自动加载](#load-cron)
- [自动释放打包文件](#build_release)

## 初始化任务自动加载 <a name="load-init-task"></a>

在`app/init`文件夹下以`类名.php`形式创建初始化任务类，并实现`run()`方法，该方法会在系统启动时自动执行。

<details>

<summary><code>Init</code>示例</summary>

```php
<?php

# app/init/Foo.php

namespace app\init;

use Wegar\Basic\Abstract\InitAbstract;

class Foo extends InitAbstract {

    public int $weight = 10; // 默认为10，越小越先执行

    function run(){
        // do something
    }
}
```

</details>

## 数据库迁移自动加载 <a name="load-migration"></a>

在项目根目录创建`phinx.php`文件，使用命令 `phinx create` 或者 `webman phinx create` 创建迁移文件。当启动时，会自动执行迁移文件。

如果使用 bin 或者 phar 打包时需要设置自动释放配置。

<details>

<summary><code>phinx.php</code>文件内容</summary>

```php
<?php

use Dotenv\Dotenv;

if (class_exists('Dotenv\Dotenv') && file_exists(base_path(false) . '/.env')) {
  if (method_exists('Dotenv\Dotenv', 'createUnsafeMutable')) {
    Dotenv::createUnsafeMutable(base_path(false))->load();
  } else {
    Dotenv::createMutable(base_path(false))->load();
  }
}
return [
  "paths"        => [
    "migrations" => is_phar() ? runtime_path('phinx/database/migrations') : base_path("database/migrations"),
    "seeds"      => is_phar() ? runtime_path('phinx/database/seeds') : base_path("database/seeds")
  ],
  "environments" => [
    "default_migration_table" => "phinxlog",
    "default_environment"     => "default",
    "default"                 => [
      "adapter"   => "mysql",
      "host"      => env("MYSQL_HOST"),
      "name"      => env("MYSQL_DBNAME"),
      "user"      => env("MYSQL_USER"),
      "pass"      => env("MYSQL_PASSWORD"),
      "port"      => env("MYSQL_PORT", "3306"),
      "charset"   => "utf8mb4",
      'collation' => 'utf8mb4_general_ci',
    ],
  ]
];
```

</details>

## Cron任务自动加载 <a name="load-cron"></a>

在`app/cron`文件夹下以`类名.php`形式创建Cron任务类，并实现`run()`方法，该方法会在系统启动时自动执行。
使用`Wegar\Basic\attribute\CronRule`对`run()`方法进行注解，以定义Cron规则。

<details>

<summary><code>Cron</code>示例</summary>

```php
<?php

# app/cron/Foo.php

namespace app\cron;

use Wegar\Basic\Attribute\CronRule;

class Foo {
    #[CronRule('*/5 * * * * *')] // 每5秒执行一次
    function run(){
        // do something
    }
}
```

</details>

## 打包文件自动释放文件 <a name="build_release"></a>

在 `config/app.php` 中设置 `build_release` 即可自动释放打包文件。

<details>

<summary><code>config/app.php</code>示例</summary>

```php
# config/app.php
  ...
  'build_release' => [
    '.env.example' => run_path(), # 将 .env.example 文件释放到运行目录下
    'public/' => run_path(),
    'plugin/admin/public/' => run_path(),
    'database/' => runtime_path('phinx'), # 将 database 目录释放到 phinx 运行目录下
  ],
  ...
```
</details>

[//]: # (## 远程 组件/APP 加载规则)

[//]: # ()
[//]: # (使用 `\Wegar\Basic\helper\RouteHelper::registerComponent` 注册远程组件)

[//]: # ()
[//]: # (- `RouteHelper::registerComponent&#40;name: 'test-page', component_file_path: '...', need_base_url: true&#41;`)

[//]: # (    - 前端访问 `/test-page{remaining_path: .*}` 可直接渲染远程组件/APP)

## 注意

- 将会需要并申明以下函数
    - `json_success` json success 响应
    - `json_error` json err 响应
    - `ss` 用于 session 快捷管理，通过修改 `config/plugin/wegar/basic/helper/SessionHelper.php` 增加自定义 session 名称和方法