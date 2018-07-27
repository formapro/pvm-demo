<?php
// handle-async-transition.php

use Enqueue\Client\Config;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Enqueue\SimpleClient\SimpleClient;
use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\Enqueue\AsyncTransition;
use Formapro\Pvm\Enqueue\HandleAsyncTransitionProcessor;
use Formapro\Pvm\Process;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\Yadm\InProcessDAL;
use Formapro\Pvm\Yadm\TokenLocker;
use Makasim\Yadm\CollectionFactory;
use Makasim\Yadm\Hydrator;
use Makasim\Yadm\PessimisticLock;
use Makasim\Yadm\Storage;

require_once __DIR__.'/vendor/autoload.php';

$mongoDsn = getenv('MONGO_DSN');
$mongoClient = new \MongoDB\Client($mongoDsn);
$processCollection = (new CollectionFactory($mongoClient, $mongoDsn))->create('pvm_process');
$processStorage = new Storage($processCollection, new Hydrator(Process::class));
$processLockCollection = (new CollectionFactory($mongoClient, $mongoDsn))->create('pvm_token_lock');
echo 'Connected to '.$mongoDsn.PHP_EOL;

$enqueueDsn = getenv('ENQUEUE_DSN');
$enqueueClient = new SimpleClient($enqueueDsn);
echo 'Connected to '.$enqueueDsn.PHP_EOL;

$registry = new DefaultBehaviorRegistry([
    'print_label' => function(Token $token) {
        echo $token->getTo()->getId().PHP_EOL;
    },
]);

$dal = new InProcessDAL($processStorage);
$tokenLocker = new TokenLocker(new PessimisticLock($processLockCollection));

$engine = new ProcessEngine($registry, $dal, new AsyncTransition($enqueueClient->getProducer()));

$enqueueClient->bind(
    Config::COMMAND_TOPIC,
    HandleAsyncTransitionProcessor::COMMAND,
    new HandleAsyncTransitionProcessor($engine, $tokenLocker)
);

$enqueueClient->setupBroker();
$enqueueClient->consume(new ChainExtension([
    new LimitConsumptionTimeExtension(new \DateTime('now + 3 seconds'))
]));
