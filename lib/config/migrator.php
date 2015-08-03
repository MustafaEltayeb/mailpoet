<?php
namespace MailPoet\Config;
use \MailPoet\Config\Env;

if(!defined('ABSPATH')) exit;

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

class Migrator {
  function __construct() {
    $this->prefix = Env::$db_prefix . Env::$plugin_prefix;
    $this->charset = Env::$db_charset;
    $this->models = array(
      'subscriber',
      'setting'
    );
  }

  function up() {
    global $wpdb;

    $migrate = function($model) {
      dbDelta($this->$model());
    };

    array_map($migrate, $this->models);
  }

  function down() {
    global $wpdb;

    $drop_table = function($model) {
      $table = $this->prefix . $model;
      $wpdb->query("DROP TABLE {$table}");
    };

    array_map($drop_table, $this->models);
  }

  function subscriber() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'first_name tinytext NOT NULL,',
      'last_name tinytext NOT NULL,',
      'PRIMARY KEY  (id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  function setting() {
    $attributes = array(
      'id mediumint(9) NOT NULL AUTO_INCREMENT,',
      'PRIMARY KEY  (id)'
    );
    return $this->sqlify(__FUNCTION__, $attributes);
  }

  private function sqlify($model, $attributes) {
    $table = $this->prefix . $model;

    $sql = array();
    $sql[] = "CREATE TABLE " . $table . " (";
    $sql = array_merge($sql, $attributes);
    $sql[] = ")" . $this->charset .  ";";

    return implode("\n", $sql);
  }
}
