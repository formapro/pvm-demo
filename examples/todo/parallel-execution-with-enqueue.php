<?php
// parallel-execution-with-enqueue.php

use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\ProcessBuilder;

use Enqueue\SimpleClient\SimpleClient;
use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\CallbackBehavior;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Process;
use Formapro\Pvm\Token;
use Formapro\Pvm\Enqueue\AsyncTransition;
use Formapro\Pvm\Uuid;
use Formapro\Pvm\Yadm\InProcessDAL;
use Makasim\Yadm\Hydrator;
use Makasim\Yadm\Storage;
use function Makasim\Values\register_object_hooks;

register_object_hooks();

$client = new SimpleClient('file:/'.__DIR__.'/queue');
$asyncTransition = new AsyncTransition($client->getProducer());

/** @var \Makasim\Yadm\Storage $processStorage */

$registry = new DefaultBehaviorRegistry();
$registry->register('print_label', new CallbackBehavior(function(Token $token) {
    echo $token->getCurrentTransition()->getTransition()->getTo()->getLabel().' ';
}));

$process = Process::create();
$process->setId(Uuid::generate());

$foo = $process->createNode();
$foo->setLabel('foo');
$foo->setBehavior('print_label');

$bar = $process->createNode();
$bar->setLabel('bar');
$bar->setBehavior('print_label');

$baz = $process->createNode();
$baz->setLabel('baz');
$baz->setBehavior('print_label');

$process->createTransition($foo, $bar);

$transition = $process->createTransition($foo, $baz);
$transition->setAsync(true);

$client = new \MongoDB\Client();
$collection = $client->selectCollection('pvm', 'process');
$processStorage = new Storage($collection, new Hydrator(Process::class));

$dal = new InProcessDAL($processStorage);

$engine = new ProcessEngine($registry, $dal, $asyncTransition);