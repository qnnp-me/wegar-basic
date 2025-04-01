<?php

namespace Wegar\Basic\init;

use Wegar\Basic\abstract\InitAbstract;

class CheckFilesDirectories extends InitAbstract
{
  public int $weight = 0;

  function run(): void
  {
    $this->checkDirs();
    $this->checkAndCreatePhinxFiles();
  }

  function checkDirs(): void
  {
    if (!is_dir(app_path('init'))) {
      mkdir(app_path('init'), recursive: true);
    }
    if (!is_dir(app_path('cron'))) {
      mkdir(app_path('cron'), recursive: true);
    }
  }

  function checkAndCreatePhinxFiles(): void
  {
    $phinx_file = base_path('phinx.php');
    if (!is_dir(base_path("database/migrations"))) {
      mkdir(base_path("database/migrations"), recursive: true);
    }
    if (!is_dir(base_path("database/migrations"))) {
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