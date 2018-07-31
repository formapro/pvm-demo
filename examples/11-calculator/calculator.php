<?php
use Formapro\Pvm\DefaultBehaviorRegistry;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\ProcessBuilder;
use Formapro\Pvm\ProcessEngine;
use Formapro\Pvm\Token;
use Formapro\Pvm\FileDAL;
use Formapro\Pvm\TokenTransition;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require_once __DIR__.'/vendor/autoload.php';

$httpRequest = Request::createFromGlobals();

$fileDal = new FileDAL(__DIR__.'/store');

$process = null;
if ($httpRequest->get('token')) {
    try {
        $token = $fileDal->getToken($httpRequest->get('token'));
        $process = $token->getProcess();
    } catch (\InvalidArgumentException $e) {
    }
}

if (false == $process) {
    $process = (new ProcessBuilder())
        ->createNode('provide_a', 'a')->end()
        ->createNode('provide_b', 'b')->end()
        ->createNode('provide_operator', 'operator')->end()
        ->createNode('result', 'result')->end()
        ->createTransition('provide_a', 'provide_b')->end()
        ->createTransition('provide_b', 'provide_operator')->end()
        ->createTransition('provide_operator', 'result')->end()
        ->createStartTransition('provide_a')->end()

        ->getProcess()
    ;

    $token = $fileDal->createProcessToken($process);
    $token->addTransition(TokenTransition::createFor($process->getStartTransition(), 1));
}

$token->httpRequest = $httpRequest;

$registry = new DefaultBehaviorRegistry([
    'a' => function(Token $token) {
        /** @var Request $httpRequest */
        $httpRequest = $token->httpRequest;

        if ($token->getCurrentTransition()->isWaiting() && Request::METHOD_POST === $httpRequest->getMethod()) {
            $token->setValue('a', $httpRequest->request->getInt('a'));

            return;
        }

        $token->httpResponse = new Response('
            <form method="post" action="'.$_SERVER['REQUEST_URI'].'?token='.$token->getId().'">
                <label for="a">Select A:</label>
                <input name="a" value="1" type="number" />
                <input type="submit" title="Submit" />
            </form>
        ');

        throw new WaitExecutionException();
    },
    'b' => function(Token $token) {
        /** @var Request $httpRequest */
        $httpRequest = $token->httpRequest;

        if ($token->getCurrentTransition()->isWaiting() && Request::METHOD_POST === $httpRequest->getMethod()) {
            $token->setValue('b', $httpRequest->request->getInt('b'));

            return;
        }

        $token->httpResponse = new Response('
            <form method="post" action="'.$_SERVER['REQUEST_URI'].'">
                <label for="b">Select B:</label>
                <input name="b" value="2" type="number" />
                <input type="submit" title="Submit" />
            </form>
        ');

        throw new WaitExecutionException();
    },
    'operator' => function(Token $token) {
        /** @var Request $httpRequest */
        $httpRequest = $token->httpRequest;

        if ($token->getCurrentTransition()->isWaiting() && Request::METHOD_POST === $httpRequest->getMethod()) {
            $token->setValue('operator', $httpRequest->request->get('operator'));

            return;
        }

        $token->httpResponse = new Response('
            <form method="post" action="'.$_SERVER['REQUEST_URI'].'">
                <fieldset>
                    <legend>Select an operator:</legend>
            
                    <div>
                        <input type="radio" name="operator" value="+" checked />
                        <label for="huey">Addition "+"</label>
                    </div>
            
                    <div>
                        <input type="radio" name="operator" value="-" />
                        <label for="dewey">Subtraction "-"</label>
                    </div>
            
                    <div>
                        <input type="radio" name="operator" value="*" />
                        <label for="louie">Multiplication "*"</label>
                    </div>
                </fieldset>
                <input type="submit" title="Submit" />
            </form>
        ');

        throw new WaitExecutionException();
    },
    'result' => function(Token $token) {
        $result = $token->getValue('result');
        $operator = $token->getValue('operator');
        $a = $token->getValue('a');
        $b = $token->getValue('b');

        if (false == $result) {
            switch ($operator) {
                case '+':
                    $result = $a + $b;
                    break;
                case '-':
                    $result = $a - $b;
                    break;
                case '*':
                    $result = $a * $b;
                    break;
                default:
                    throw new \LogicException('Invalid operator');
            }

            $token->setValue('result', $result);
        }

        $token->httpResponse = new Response("
            <p>The result of $a $operator $b equals <b>$result</b></p> 
        ");

        throw new WaitExecutionException();
    },
]);

$engine = new ProcessEngine($registry, $fileDal);

$waitTokens = $engine->proceed($token);

/** @var Response $httpResponse */
$httpResponse = $waitTokens[0]->httpResponse;

echo $httpResponse->getContent();