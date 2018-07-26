<?php
// store-to-mongodb.php

use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\Process;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\ProcessBuilder;
use Makasim\Yadm\CollectionFactory;
use Makasim\Yadm\Hydrator;
use Makasim\Yadm\Storage;

require_once __DIR__.'/vendor/autoload.php';

$mongoDsn = getenv('MONGO_DSN');
$mongoClient = new \MongoDB\Client($mongoDsn);
$mongoCollection = (new CollectionFactory($mongoClient, $mongoDsn))->create('pvm_process');
$processStorage = new Storage($mongoCollection, new Hydrator(Process::class));
echo 'Connected to '.$mongoDsn.PHP_EOL;

$process = (new ProcessBuilder())
    ->createNode('a_task', 'print_label')->end()
    ->createStartTransition('a_task')->end()

    ->getProcess()
;

$processId = $process->getId();

$processStorage->insert($process);
echo 'Inserted to MongoDB'.PHP_EOL;

unset($process);

$process = $processStorage->findOne(['id' => $processId]);
echo 'Found by ID: '.$processId.PHP_EOL;

$engine = new ProcessEngine(new DefaultBehaviorRegistry([
    'print_label' => function(Token $token) {
        echo $token->getTo()->getId().PHP_EOL;
    },
]));

$token = $engine->createTokenFor($process->getStartTransition());
$engine->proceed($token);

$processStorage->update($process);
echo 'Updated in MongoDB'.PHP_EOL;