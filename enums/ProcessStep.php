<?php
if (PHP_VERSION_ID < 80100) {
    require_once(plugin_dir_path(__FILE__) . "ProcessStep-emulated.php");
} else {
    require_once(plugin_dir_path(__FILE__) . "ProcessStep-native.php");
}