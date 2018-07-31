<?php
use Formapro\Pvm\ProcessBuilder;
use function Makasim\Values\clone_object;

require_once __DIR__.'/vendor/autoload.php';

$processToBeCloned = (new ProcessBuilder())
    ->createNode('a_task', 'a_behavior')->end()
    ->createStartTransition('a_task')->end()

    ->getProcess()
;

$process = clone_object($processToBeCloned);

(new ProcessBuilder($process))
    ->createNode('b_task', 'a_behavior')->end()
    ->createTransition('a_task', 'b_task')
;
