<?php
// condition.php

use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\ProcessBuilder;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;

require_once __DIR__.'/vendor/autoload.php';

$process = (new ProcessBuilder())
    ->createNode('shipping_decision', 'shipping_decision')->end()
    ->createNode('free_shipping', 'print_label')->end()
    ->createNode('ups_shipping', 'print_label')->end()

    ->createTransition('shipping_decision', 'free_shipping', 'free')->end()
    ->createTransition('shipping_decision', 'ups_shipping', 'ups')->end()
    ->createStartTransition('shipping_decision')->end()

    ->getProcess()
;

$engine = new ProcessEngine(new DefaultBehaviorRegistry([
    'print_label' => function(Token $token) {
        echo $token->getTo()->getId().PHP_EOL;
    },
    'shipping_decision' => function(Token $token) {
        echo 'Total price (free if >= 50): '. $token->getValue('total_price').PHP_EOL;

        return $token->getValue('total_price') > 50 ? 'free' : 'ups';
    }
]));

$token = $engine->createTokenFor($process->getStartTransition());
$token->setValue('total_price', rand(10, 100));

$engine->proceed($token);