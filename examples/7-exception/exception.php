<?php
// exception.php

use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\ProcessBuilder;

require_once __DIR__.'/vendor/autoload.php';

$process = (new ProcessBuilder())
    ->createNode('foo_task', 'print_label')->end()
    ->createNode('bar_task', 'throw_exception')->end()
    ->createNode('baz_task', 'print_label')->end()
    ->createTransition('foo_task', 'bar_task')->end()
    ->createTransition('bar_task', 'baz_task')->end()
    ->createStartTransition('foo_task')->end()

    ->getProcess()
;

$engine = new ProcessEngine(new DefaultBehaviorRegistry([
    'print_label' => function(Token $token) {
        echo $token->getTo()->getId().PHP_EOL;
    },
    'throw_exception' => function(Token $token) {
        echo $token->getTo()->getId().PHP_EOL;
        throw new \LogicException('Something went wrong');
    },
]));

$engine->setLogException(true);

$token = $engine->createTokenFor($process->getStartTransition());

try {
    $engine->proceed($token);
} catch (\Throwable $e) {
    echo get_class($e).' is caught. Message '.$e->getMessage().PHP_EOL;
}

