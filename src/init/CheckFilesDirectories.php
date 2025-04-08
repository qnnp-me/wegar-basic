<?php

namespace Wegar\Basic\init;

use Webman\RedisQueue\Process\Consumer;
use Wegar\Basic\abstract\InitAbstract;
use Workerman\Crontab\Crontab;

class CheckFilesDirectories extends InitAbstract
{
  public int $weight = 0;

  function run(): void
  {
    if (is_phar()) return;
    $this->checkEnvExample();
    $this->checkDirs();
    $this->checkAndCreatePhinxFiles();
  }

  function checkDirs(): void
  {
    if (!is_dir(app_path('init'))) {
      mkdir(app_path('init'), recursive: true);
    }
    if (class_exists(Crontab::class) && !is_dir(app_path('cron'))) {
      mkdir(app_path('cron'), recursive: true);
    }
    foreach (config('plugin.webman.redis-queue.process', []) as $name => $config) {
      if (
        config("plugin.webman.redis-queue.process.$name.handler") === Consumer::class
        && !is_dir(config("plugin.webman.redis-queue.process.$name.constructor.consumer_dir", ''))
      ) {
        mkdir(config("plugin.webman.redis-queue.process.$name.constructor.consumer_dir", ''), recursive: true);
      }
    }
  }

  function checkEnvExample(): void
  {
    if (class_exists(\Dotenv\Dotenv::class) && !file_exists(base_path(".env.example"))) {
      file_put_contents(run_path(".env.example"), /** @lang dotenv */ "
DEBUG=true

# PROJECT_NAME 应该和同系统中的其他项目保持不一致
PROJECT_NAME=app_name

# HTTP 服务相关设置
HTTP_IP=[::]
HTTP_PORT=8787
# HTTP_CPU_COUNT=
# HTTP_USER=
# HTTP_GROUP=
# HTTP_REUSE_PORT=

# 最大数据包大小
SERVER_MAX_PACKAGE_SIZE=10485760

MYSQL_HOST=127.0.0.1
MYSQL_DBNAME=
MYSQL_USER=
MYSQL_PASSWORD=
MYSQL_PORT=3306

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Phar 和 二进制打包配置
# BUILD_DIR=build
# BUILD_PHAR_FILENAME=webman.phar
# BUILD_BIN_FILENAME=webman.bin
# BUILD_EXCLUDE_PATTERN=#^(?!.*(composer.json|/.github/|/.idea/|/.git/|/.setting/|/runtime/|/vendor-bin/|/build/|/vendor/webman/admin/))(.*)$#
# BUILD_EXCLUDE_FILES=.env,LICENSE,composer.json,composer.lock,start.php,webman.phar,webman.bin
# BUILD_CUSTOM_INI=\"
# memory_limit = 256M
# \"

# 权限类型文件保存地址，仅用于开发
# PERMISSION_TYPES_SAVE_PATH=
");
    }
  }

  function checkAndCreatePhinxFiles(): void
  {
    $phinx_file = base_path('phinx.php');
    if (!is_dir(base_path("database/migrations"))) {
      mkdir(base_path("database/migrations"), recursive: true);
    }
    if (!is_dir(base_path("database/seeds"))) {
      mkdir(base_path("database/seeds"), recursive: true);
    }
    if (!file_exists($phinx_file)) {
      file_put_contents($phinx_file,
        '<?php

use Dotenv\Dotenv;

if (class_exists("Dotenv\\Dotenv") && file_exists(base_path(false) . "/.env")) {
  if (method_exists("Dotenv\\Dotenv", "createUnsafeMutable")) {
    Dotenv::createUnsafeMutable(base_path(false))->load();
  } else {
    Dotenv::createMutable(base_path(false))->load();
  }
}
return [
  "paths"        => [
    "migrations" => is_phar() ? runtime_path("phinx/database/migrations") : base_path("database/migrations"),
    "seeds"      => is_phar() ? runtime_path("phinx/database/seeds") : base_path("database/seeds")
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
      "collation" => "utf8mb4_general_ci",
    ],
  ]
];'
      );
    }
  }
}