<?php
use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\ProcessBuilder;

require_once __DIR__.'/vendor/autoload.php';

$process = (new ProcessBuilder())
    ->createNode('purchase_order', 'print_label')->end()
    ->createNode('send_email', 'print_label')->end()
    ->createNode('send_sms', 'print_label')->end()

    ->createTransition('purchase_order', 'send_email')->end()
    ->createTransition('purchase_order', 'send_sms')->end()
    ->createStartTransition('purchase_order')->end()

    ->getProcess()
;

$engine = new ProcessEngine(new DefaultBehaviorRegistry([
    'print_label' => function(Token $token) {
        echo $token->getTo()->getId().PHP_EOL;
    },
]));

$token = $engine->createTokenFor($process->getStartTransition());
$engine->proceed($token);
