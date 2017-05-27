<?php
use Dotenv\Dotenv;
$dotenv = new Dotenv(__DIR__, ".env");
$dotenv->load();

Yii::setAlias('@common', dirname(__DIR__));
Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');

