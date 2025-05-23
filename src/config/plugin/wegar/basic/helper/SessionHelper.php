<?php

namespace config\plugin\wegar\basic\helper;

use Exception;

/**
 * session 管理模块
 * 在此类增加的属性名即为可被管理的 session 名
 * 以属性名为前缀的方法名即为可被调用的方法, 可用方法为 delete, exists, forget, get, has, put, pull, set
 */
class SessionHelper
{
  protected static SessionHelper $instance;

  public mixed $some_session_name;

  public static function getInstance(): SessionHelper
  {
    if (!isset(static::$instance)) {
      static::$instance = new static();
    }
    return static::$instance;
  }

  /**
   * @throws Exception
   */
  public function __call(string $name, ?array $arguments)
  {
    $_name = preg_replace("#([A-Z][a-z]+)$#", "*$1", $name);
    $_name = explode("*", $_name);
    $session_name = $_name[0];
    if (!property_exists($this, $session_name)) {
      throw new Exception("session $session_name 未被管理 -> 请至：config/plugin/wegar/basic/helper/SessionHelper.php 添加 \$$session_name 属性");
    }
    $methods = [
      'delete',
      'exists',
      'forget',
      'get',
      'has',
      'put',
      'pull',
      'set',
    ];
    $method = strtolower($_name[1]);
    if (!in_array($method, $methods)) {
      throw new Exception("方法 $method 不被支持");
    }
    return session()->{$method}($session_name, ...$arguments);
  }
}
