<?php
namespace Allofmex\TranslatingAutoLoader;

require 'TranslatingAutoLoader.php';

$instance = new TranslatingAutoLoader();
$instance->init();
return $instance;
