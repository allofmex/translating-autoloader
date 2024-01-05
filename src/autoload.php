<?php
namespace Allofmex\TranslatingAutoLoader;

require_once 'TranslatingAutoLoader.php';

$instance = new TranslatingAutoLoader();
$instance->init();
return $instance;
