<?php

echo "\n";

define('NO_SUDO', true);

require __DIR__."/_bootstrap.php";

$config = Pimcore_Config::getSystemConfig();

$remote_env = $config->deploy->remote->env;

$env_config = new Zend_Config_Ini('website/config/env.ini', $remote_env);

$remote_host = $config->deploy->remote->host;
$remote_php_owner = $env_config->general->php_owner;
$remote_dir = $env_config->general->root;
$remote_php = $env_config->general->php_cli;
$remote_mysql_cmd = $env_config->general->mysql_cli;
$remote_mysql_user = $env_config->database->params->username;
$remote_mysql_passwd = $env_config->database->params->password;
$remote_mysql_db = $env_config->database->params->dbname;

try {
    $opts = new Zend_Console_Getopt(array(
        'config-processed'   => '(internal use)',
        'replace-db' => 'Replaces the remote DB (!caution!)',
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
$replace_db = $opts->getOption('replace-db');

if($replace_db){
  echo "Replaces existing DB (5 seconds to cancel with CTRL+C)...\n";
  sleep(5);
  echo "Création du backup...\n";
  bdpPhpExec("pimcore/cli/backup.php -v -f current -o");
  echo "Backup créé !\n";

  echo "Extraction dump.sql\n";
  passthru("cd website/var/tmp && mkdir -p deploy && cd deploy && tar xf ../../backup/current.tar && cp dump.sql ../../backup && cd .. && rm -rf deploy");

  echo "Remplacement des DEFINER...";
  passthru("sed -i 's/DEFINER=`cmp`@`localhost`/DEFINER=`$remote_mysql_user`@`localhost`/g' website/var/backup/dump.sql");
  echo " OK\n";
}

passthru("rsync -avc --delete --progress --exclude=.git --exclude-from=.gitignore . $remote_host:$remote_dir");

if($replace_db){
  echo "Mise à jour DB distante : copie...";
  passthru("scp -B website/var/backup/dump.sql $remote_host:$remote_dir/website/var/tmp/");
  echo " OK, import : ";
  passthru("ssh $remote_host 'cd $remote_dir && $remote_mysql_cmd -u $remote_mysql_user --password=$remote_mysql_passwd $remote_mysql_db < website/var/tmp/dump.sql'");
  echo " OK\n";
}

echo "Procédure distante de post-deploiement : \n";
passthru("ssh $remote_host 'cd $remote_dir && APPLICATION_ENV=$remote_env sudo -E -u $remote_php_owner $remote_php plugins/Bdp/cli/post-deploy.php'");
echo "Terminé !\n";