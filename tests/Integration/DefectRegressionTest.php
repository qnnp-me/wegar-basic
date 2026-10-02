<?php

namespace Tests\Integration;

/**
 * 端到端回归覆盖两个缺陷（A 守护模式崩溃 / B init 静默跳过），运行在真实
 * webman 宿主内，跑当前仓库的 wegar/basic 源码（refreshPluginFromRepo 已
 * 拉取最新代码）。
 *
 * - DefectA: InitProcess 必须在 STDOUT 失效的守护模式下完成构造，不再让
 *   worker 反复重启。
 * - DefectB: app/init/ 下存在非 PHP 文件（如 .gitkeep）时，命名空间推导与
 *   init 类执行不能被静默跳过。
 */
class DefectRegressionTest extends HostTestCase
{
  public function testDefectAInitProcessSurvivesClosedStdout(): void
  {
    $script = $this->hostDir() . '/wegar-defectA.php';
    file_put_contents($script, <<<'PHP'
<?php
chdir(__DIR__);
register_shutdown_function(function () {
  $err = error_get_last();
  if ($err) {
    file_put_contents(__DIR__ . '/shutdown-trace.log', "FATAL: " . json_encode($err) . "\n", FILE_APPEND);
  }
});
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/support/bootstrap.php';
// 模拟 Workerman 守护进程关闭 STDOUT 后的真实环境：fclose 后 php-console-color
// 的 posix_isatty() 抛 TypeError，Symfony 的 ConsoleOutput 也无法用 STDOUT。
fclose(STDOUT);
file_put_contents(__DIR__ . '/shutdown-trace.log', "STDOUT_CLOSED\n", FILE_APPEND);
try {
  new \Wegar\Basic\Process\InitProcess();
  // STDOUT 已关闭，不能用 echo（会 fatal）。写到 trace 文件。
  file_put_contents(__DIR__ . '/shutdown-trace.log', "INIT_DONE\nDEFECT_A_OK\n", FILE_APPEND);
  exit(0);
} catch (\Throwable $t) {
  file_put_contents(__DIR__ . '/shutdown-trace.log',
    'CAUGHT: ' . get_class($t) . ' :: ' . $t->getMessage() . "\n" . $t->getTraceAsString() . "\n",
    FILE_APPEND);
  exit(1);
}
PHP);
    $traceFile = $this->hostDir() . '/shutdown-trace.log';
    @unlink($traceFile);
    [$exit, $out] = $this->runHost(['php', 'wegar-defectA.php']);
    $trace = is_file($traceFile) ? file_get_contents($traceFile) : '(no trace)';
    $this->assertSame(0, $exit,
      "InitProcess threw on closed STDOUT; output:\n$out\ntrace:\n$trace");
    $this->assertStringContainsString('DEFECT_A_OK', $trace);
    @unlink($traceFile);
  }

  public function testDefectBNonPhpEntryDoesNotSkipInitClasses(): void
  {
    $initDir = $this->hostDir() . '/app/init';
    if (!is_dir($initDir)) {
      mkdir($initDir, 0777, true);
    }
    // .gitkeep 排在前（scandir 字典序：'.' 开头文件最先），模拟下游仓库真实顺序
    file_put_contents($initDir . '/.gitkeep', '');
    // 自定义 init 类：通过子进程显式 require_once（不走 PSR-4）。
    file_put_contents($initDir . '/RegressionRunner.php', <<<'PHP'
<?php
namespace App\Init;

class RegressionRunner
{
  public int $weight = 99;

  public function run(): void
  {
    fwrite(STDOUT, "DEFECT_B_RAN\n");
  }
}
PHP);

    try {
      $script = $this->hostDir() . '/wegar-defectB.php';
      file_put_contents($script, <<<'PHP'
<?php
chdir(__DIR__);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/support/bootstrap.php';
// 绕过 PSR-4，显式声明 RegressionRunner 的位置让 InitHelper::class_exists 命中
require_once __DIR__ . '/app/init/RegressionRunner.php';
new \Wegar\Basic\Process\InitProcess();
echo "DEFECT_B_OK\n";
PHP);
      [$exit, $out] = $this->runHost(['php', 'wegar-defectB.php']);

      $this->assertSame(0, $exit, "InitProcess failed; output:\n$out");
      $this->assertStringContainsString('DEFECT_B_OK', $out);
      $this->assertStringContainsString('DEFECT_B_RAN', $out,
        'RegressionRunner must run even when .gitkeep sorts first in app/init/');
    } finally {
      @unlink($initDir . '/.gitkeep');
      @unlink($initDir . '/RegressionRunner.php');
    }
  }
}
