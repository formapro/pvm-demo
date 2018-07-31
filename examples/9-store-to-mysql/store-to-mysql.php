<?php
use Acme\StoreToDoctrine\OrmProcess;
use Acme\StoreToDoctrine\OrmToken;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriverChain;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\SimplifiedXmlDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\Doctrine\DoctrineDAL;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\ProcessBuilder;

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/OrmProcess.php';
require_once __DIR__.'/OrmToken.php';

$config = new Configuration();
$config->setAutoGenerateProxyClasses(true);
$config->setProxyDir(\sys_get_temp_dir());
$config->setProxyNamespace('Proxies');
$config->setQueryCacheImpl(new ArrayCache());
$config->setMetadataCacheImpl(new ArrayCache());

$driver = new MappingDriverChain();

$annotationDriver = $config->newDefaultAnnotationDriver([__DIR__], false);
$driver->addDriver($annotationDriver, 'Acme\StoreToDoctrine');

$xmlDriver = new SimplifiedXmlDriver([
    realpath(__DIR__.'/vendor/formapro/pvm/src/Doctrine/mapping') => 'Formapro\Pvm\Doctrine',
]);
$driver->addDriver($xmlDriver, 'Formapro\Pvm\Doctrine');

$config->setMetadataDriverImpl($driver);
$connection = ['url' => getenv('MYSQL_DSN')];

$em = EntityManager::create($connection, $config);
echo 'Connected to '.getenv('MYSQL_DSN').PHP_EOL;

$schemaTool = new SchemaTool($em);
$schemaTool->updateSchema($em->getMetadataFactory()->getAllMetadata());

$doctrineDal = new DoctrineDAL($em, OrmProcess::class, OrmToken::class);

$process = (new ProcessBuilder())
    ->createNode('a_task', 'print_label')->end()
    ->createStartTransition('a_task')->end()

    ->getProcess()
;

$registry = new DefaultBehaviorRegistry([
    'print_label' => function(Token $token) {
        echo $token->getTo()->getId().PHP_EOL;
    },
]);

$engine = new ProcessEngine($registry, $doctrineDal);

$token = $engine->createTokenFor($process->getStartTransition());
$engine->proceed($token);

if ($em->find(OrmProcess::class, $process->getId())) {
    echo 'Found the process in DB'.PHP_EOL;
} else {
    echo 'the process was not found in DB'.PHP_EOL;
}
