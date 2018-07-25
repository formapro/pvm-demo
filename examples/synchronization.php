<?php
// synchronization.php

use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\Exception\InterruptExecutionException;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\ProcessBuilder;

require_once __DIR__.'/vendor/autoload.php';

$process = (new ProcessBuilder())
    ->createNode('fork', 'print_label')->end()
    ->createNode('update_warehouse', 'print_label')->end()
    ->createNode('update_accounting', 'print_label')->end()
    ->createNode('update_delivery', 'print_label')->end()
    ->createNode('notify_customer', 'print_label')->end()
    ->createNode('join', 'join')->end()

    ->createTransition('fork', 'update_warehouse')->end()
    ->createTransition('fork', 'update_accounting')->end()
    ->createTransition('fork', 'update_delivery')->end()
    ->createTransition('update_warehouse', 'join')->end()
    ->createTransition('update_accounting', 'join')->end()
    ->createTransition('update_delivery', 'join')->end()
    ->createTransition('join', 'notify_customer')->end()
    ->createStartTransition('fork')->end()

    ->getProcess()
;

$engine = new ProcessEngine(new DefaultBehaviorRegistry([
    'print_label' => function(Token $token) {
        echo $token->getTo()->getId().PHP_EOL;
    },
    'join' => function (Token $token) {
        static $weight = 0;
        $weight += $token->getCurrentTransition()->getWeight();

        if ($weight === 3) {
            return;
        }

        echo 'wait for other tasks to be finished'.PHP_EOL;

        throw new InterruptExecutionException();
    }
]));

$token = $engine->createTokenFor($process->getStartTransition());
$engine->proceed($token);
