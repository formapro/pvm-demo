<?php
use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\Process;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\ProcessBuilder;
use Formapro\Pvm\Yadm\InProcessDAL;
use Makasim\Yadm\CollectionFactory;
use Makasim\Yadm\Hydrator;
use Makasim\Yadm\Storage;

require_once __DIR__.'/vendor/autoload.php';

$mongoDsn = getenv('MONGO_DSN');
$mongoClient = new \MongoDB\Client($mongoDsn);
$mongoCollection = (new CollectionFactory($mongoClient, $mongoDsn))->create('pvm_process');
$processStorage = new Storage($mongoCollection, new Hydrator(Process::class));
echo 'Connected to '.$mongoDsn.PHP_EOL;

$yadmDal = new InProcessDAL($processStorage);

$process = (new ProcessBuilder())
    ->createNode('a_task', 'print_label')->end()
    ->createStartTransition('a_task')->end()

    ->getProcess()
;

$processId = $process->getId();

$registry = new DefaultBehaviorRegistry([
    'print_label' => function(Token $token) {
        echo $token->getTo()->getId().PHP_EOL;
    },
]);

$engine = new ProcessEngine($registry, $yadmDal);

$token = $engine->createTokenFor($process->getStartTransition());
$engine->proceed($token);

if ($process = $processStorage->findOne(['id' => $process->getId()])) {
    echo 'Found the process in DB'.PHP_EOL;
} else {
    echo 'the process was not found in DB'.PHP_EOL;
}
