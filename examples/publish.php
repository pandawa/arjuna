<?php

use Illuminate\Contracts\Events\Dispatcher;

$app = require_once  __DIR__ . '/bootstrap.php';

$app->get(Dispatcher::class)->dispatch(new UserRegistered());

