<?php
// grouping.php

use Formapro\Pvm\ProcessBuilder;

require_once __DIR__.'/vendor/autoload.php';

$process = (new ProcessBuilder())
    ->createNode('foo_1_task', 'a_behavior')->setOption('group', 'foo')->end()
    ->createNode('foo_2_task', 'a_behavior')->setOption('group', 'foo')->end()
    ->createNode('foo_3_task', 'a_behavior')->setOption('group', 'foo')->end()
    ->createNode('bar_1_task', 'a_behavior')->setOption('group', 'bar')->end()
    ->createNode('bar_2_task', 'a_behavior')->setOption('group', 'bar')->end()
    ->createTransition('foo_1_task', 'foo_2_task')->end()
    ->createTransition('foo_2_task', 'foo_3_task')->end()
    ->createTransition('foo_2_task', 'bar_2_task')->end()
    ->createTransition('bar_1_task', 'bar_2_task')->end()
    ->createStartTransition('foo_1_task')->end()
    ->createStartTransition('bar_1_task')->end()

    ->getProcess()
;

