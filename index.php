<?php

# Arquivo de configução de ambiente
require("config/fw.inc.php");

# Arquivo de autoloar
require("vendor/AutoLoader.php");

# Setando pastas das classes
AutoLoader::inc('app');
AutoLoader::inc('core');

# Liberando o carregamento automático
AutoLoader::dispatch();

# Instanciando a aplicação
$app = new \app\Application;

# Rodando a aplicação
$app->run();