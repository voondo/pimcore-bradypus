<?php
set_include_path(get_include_path().':'.__DIR__.'/../../../pimcore/lib');
require 'Zend/Console/Getopt.php';
require 'Zend/Config/Xml.php';
require 'Zend/Config/Ini.php';
require 'Zend/Config/Writer/Xml.php';


try {
    $opts = new Zend_Console_Getopt(array(
        'config-processed'   => '(internal use)',
        'help|h' => 'display this help'
    ));
    $opts->parse();
} catch (Exception $e) {
    echo "!! ".$e->getMessage()."\n";
    die;
}

// display help message
if($opts->getOption("help")) {
    echo $opts->getUsageMessage();
    exit;
}

if(!$opts->getOption('config-processed')){

  if(!isset($_SERVER['APPLICATION_ENV'])){
    echo "!! APPLICATION_ENV must be set \n";
    die;
  }
  $env = $_SERVER['APPLICATION_ENV'];
  define('APPLICATION_ENV', $env);


  echo "Updating config...";
  $config = new Zend_Config_Xml('website/var/config/system.xml', null, array(
    'allowModifications' => true
    ));

  $env_config = new Zend_Config_Ini('website/config/env.ini', $env);
  $config->merge($env_config);

  $writer = new Zend_Config_Writer_Xml(array(
    'config'   => $config,
    'filename' => 'website/var/config/system.xml'
    ));
  $writer->write();
  echo " OK !\n";

  echo "Config updated !\n";
  require __DIR__."/_tools.php";

  bdpPhpExec(implode(' ', array_merge(
    $_SERVER['argv'],
    array('--config-processed')
    )), $config->general->php_cli);
  die;
}

echo "Bootstraping...\n";
require __DIR__."/_bootstrap.php";

echo "Cache cleaning...\n";
passthru("rm -rf website/var/cache/*");

echo "Maintenance...\n";
bdpPhpExec("pimcore/cli/maintenance.php");

echo "Db optimize...\n";
bdpPhpExec("pimcore/cli/mysql-tools.php --mode=optimize");

echo "Db warming...\n";
bdpPhpExec("pimcore/cli/mysql-tools.php --mode=warmup");

echo "Done !\n";