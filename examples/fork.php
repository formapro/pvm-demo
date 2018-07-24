<?php
// fork.php

use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\ProcessBuilder;

require_once __DIR__.'/vendor/autoload.php';

$process = (new ProcessBuilder())
    ->createNode('purchase_order', 'purchase_order')->end()
    ->createNode('send_email', 'send_email')->end()
    ->createNode('send_sms', 'send_sms')->end()

    ->createTransition('purchase_order', 'send_email')->end()
    ->createTransition('purchase_order', 'send_sms')->end()
    ->createStartTransition('purchase_order')->end()

    ->getProcess()
;

$engine = new ProcessEngine(new DefaultBehaviorRegistry([
    'purchase_order' => function() {
        echo 'order is purchased'.PHP_EOL;
    },
    'send_email' => function() {
        echo 'send email'.PHP_EOL;
    },
    'send_sms' => function() {
        echo 'send sms'.PHP_EOL;
    },
]));

$token = $engine->createTokenFor($process->getStartTransition());
$engine->proceed($token);
