<?php

namespace Tests\Integration;

/**
 * 端到端回归覆盖三个缺陷（A 守护模式崩溃 / B init 静默跳过 / C 守护模式错误
 * 静默丢弃），运行在真实 webman 宿主内，跑当前仓库的 wegar/basic 源码
 * （refreshPluginFromRepo 已拉取最新代码）。
 *
 * - DefectA: InitProcess 必须在 STDOUT 失效的守护模式下完成构造，不再让
 *   worker 反复重启。
 * - DefectB: app/init/ 下存在非 PHP 文件（如 .gitkeep）时，命名空间推导与
 *   init 类执行不能被静默跳过。
 * - DefectC: 守护模式下 ConsoleOutput 退化为 NullOutput 时，init 抛出的异常
 *   必须改走可持久化落点，不能被静默丢弃。
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

  /**
   * DefectC: 守护模式（STDOUT 关闭）下 ConsoleOutput 退化为 NullOutput，
   * init 抛出的异常必须改走可持久化落点而非静默丢弃。这里用 $errorSink 捕获，
   * 断言异常消息确实被记下（端到端，不依赖真实 Monolog 落盘位置）。
   */
  public function testDefectCInitErrorIsRoutedToSinkWhenStdoutClosed(): void
  {
    $initDir = $this->hostDir() . '/defect-c-init';
    mkdir($initDir, 0777, true);
    file_put_contents($initDir . '/BoomInit.php', <<<'PHP'
<?php
namespace App\Init;

class BoomInit
{
  public int $weight = 1;

  public function run(): void
  {
    throw new \RuntimeException('init-boom');
  }
}
PHP);

    $sinkFile = $this->hostDir() . '/defect-c-sink.log';
    $script = $this->hostDir() . '/wegar-defectC.php';
    file_put_contents($script, <<<'PHP'
<?php
chdir(__DIR__);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/support/bootstrap.php';
require_once __DIR__ . '/defect-c-init/BoomInit.php';
\Wegar\Basic\Helper\CommandHelper::$errorSink = function (string $m) {
  file_put_contents(__DIR__ . '/defect-c-sink.log', $m . "\n", FILE_APPEND);
};
fclose(STDOUT); // 模拟守护进程：ConsoleOutput 构造失败 → NullOutput
\Wegar\Basic\Helper\InitHelper::load(__DIR__ . '/defect-c-init');
PHP);

    @unlink($sinkFile);
    try {
      [$exit, $out] = $this->runHost(['php', 'wegar-defectC.php']);
      $this->assertSame(0, $exit, "InitHelper::load failed; output:\n$out");
      $sink = is_file($sinkFile) ? (string) file_get_contents($sinkFile) : '(no sink log)';
      $this->assertStringContainsString('init-boom', $sink,
        'init error must reach the persistent sink when console is unavailable');
    } finally {
      @unlink($initDir . '/BoomInit.php');
      @rmdir($initDir);
      @unlink($script);
      @unlink($sinkFile);
    }
  }

  /**
   * DefectC（真实落点）：不注入 $errorSink，验证守护模式下错误真的写进了 webman
   * 的 support\Log（Monolog 文件），且 failed() 映射为 CRITICAL。覆盖生产默认
   * 路径，防止落点被改坏而测试仍全绿。
   */
  public function testDefectCDefaultSinkPersistsToWebmanLog(): void
  {
    $initDir = $this->hostDir() . '/defect-c2-init';
    mkdir($initDir, 0777, true);
    file_put_contents($initDir . '/BoomInit.php', <<<'PHP'
<?php
namespace App\Init;

class BoomInit
{
  public int $weight = 1;

  public function run(): void
  {
    throw new \RuntimeException('init-boom-default');
  }
}
PHP);

    $script = $this->hostDir() . '/wegar-defectC2.php';
    file_put_contents($script, <<<'PHP'
<?php
chdir(__DIR__);
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/support/bootstrap.php';
require_once __DIR__ . '/defect-c2-init/BoomInit.php';
// 不设置 $errorSink：走生产默认落点（support\Log → Monolog 文件）
fclose(STDOUT);
fclose(STDERR);
\Wegar\Basic\Helper\InitHelper::load(__DIR__ . '/defect-c2-init'); // error() → support\Log
(new \Wegar\Basic\Helper\CommandHelper())->failed('sink-crit-marker'); // failed() → support\Log::critical
PHP);

    try {
      [$exit, $out] = $this->runHost(['php', 'wegar-defectC2.php']);
      $this->assertSame(0, $exit, "subprocess failed; output:\n$out");

      $logs = glob($this->hostDir() . '/runtime/logs/*.log') ?: [];
      $this->assertNotEmpty($logs, 'webman log file must be written');
      $content = '';
      foreach ($logs as $file) {
        $content .= (string) file_get_contents($file);
      }
      $this->assertStringContainsString('init-boom-default', $content,
        'error() must reach webman support\Log in daemon mode');
      $this->assertStringContainsString('sink-crit-marker', $content,
        'failed() must reach webman support\Log in daemon mode');
      $this->assertStringContainsString('CRITICAL', $content,
        'failed() must map to critical level');
    } finally {
      @unlink($initDir . '/BoomInit.php');
      @rmdir($initDir);
      @unlink($script);
    }
  }
}
