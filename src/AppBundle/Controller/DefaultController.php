<?php

namespace AppBundle\Controller;

use Formapro\Pvm\CallbackBehavior;
use Formapro\Pvm\Exception\WaitExecutionException;
use Formapro\Pvm\Process;
use Formapro\Pvm\Token;
use Formapro\Pvm\UUID;
use Formapro\Pvm\Visual\GraphVizVisual;
use Psr\Log\NullLogger;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Tests\Logger;
use Symfony\Component\Validator\Constraints\NotBlank;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.root_dir').'/..').DIRECTORY_SEPARATOR,
        ]);
    }

    /**
     * @Route("/calculator", name="calculator")
     */
    public function calculatorAction(Request $request)
    {
        $this->setupBehaviors();

        if ($processId = $request->getSession()->get('processId')) {
            $process = $this->getProcessStorage()->findExecution($processId);
        } else {
            $process = $this->createProcess();
        }

        if ($tokenId = $request->getSession()->get('tokenId')) {
            $token = $process->getToken($tokenId);
        } else {
            foreach ($process->getTransitions() as $transition) {
                if ($transition->getFrom() === null) {
                    break;
                }
            }

            $token = $process->createToken($transition);
        }

        $token->request = $request;

        $waitTokens = $this->getProcessEngine()->proceed($token, $logger = new Logger());

        if ($waitTokens) {
            $request->getSession()->set('processId', $process->getId());
            $request->getSession()->set('tokenId', $waitTokens[0]->getId());
        } else {
            $request->getSession()->remove('processId');
            $request->getSession()->remove('tokenId');
        }

        $graph = (new GraphVizVisual())->createImageSrc($process);

        return $this->render('default/calculator.html.twig', [
            'process' => $process,
            'graph' => $graph,
            'form' => $waitTokens ? $waitTokens[0]->form->createView() : null,
            'logger' => $logger,
        ]);
    }

    private function setupBehaviors()
    {
        $repository = $this->getBehaviorRepository();
        $repository->register('a', new CallbackBehavior(function(Token $token) {
            /** @var Form $form */
            $form = $this->getFormFactory()->createNamedBuilder('form')
                ->add('a', NumberType::class)
                ->getForm()
            ;

            $token->form = $form;

            if ($token->getTransition()->isWaiting()) {
                $form->handleRequest($token->request);
                if ($form->isValid()) {
                    $token->getProcess()->setValue('a', $form['a']->getData());
                    return;
                }
            }

            throw new WaitExecutionException();
        }));
        $repository->register('b', new CallbackBehavior(function(Token $token) {
            /** @var Form $form */
            $form = $this->getFormFactory()->createNamedBuilder('form')
                ->add('b', NumberType::class)
                ->getForm()
            ;

            $token->form = $form;

            if ($token->getTransition()->isWaiting()) {
                $form->handleRequest($token->request);
                if ($form->isValid()) {
                    $token->getProcess()->setValue('b', $form['b']->getData());

                    return;
                }
            }

            throw new WaitExecutionException();
        }));
        $repository->register('operator', new CallbackBehavior(function(Token $token) {
            /** @var Form $form */
            $form = $this->getFormFactory()->createNamedBuilder('form')
                ->add('operator', null, [
                    'constraints' => [new NotBlank()],
                ])
                ->getForm()
            ;

            $token->form = $form;

            if ($token->getTransition()->isWaiting()) {
                $form->handleRequest($token->request);
                if ($form->isValid()) {
                    $token->getProcess()->setValue('operator', $form['operator']->getData());

                    return;
                }
            }

            throw new WaitExecutionException();
        }));
        $repository->register('result', new CallbackBehavior(function(Token $token) {
            $process = $token->getProcess();
            switch ($process->getValue('operator')) {
                case '+':
                    $result = $process->getValue('a') + $process->getValue('b');
                    break;
                default:
                    throw new \LogicException('Invalid operator');
            }

            $process->setValue('result', $result);
        }));
    }

    private function createProcess()
    {
        $process = new Process();
        $process->setId(UUID::generate());

        $task1 = $process->createNode();
        $task1->setLabel('task a');
        $task1->setBehavior('a');

        $task2 = $process->createNode();
        $task2->setLabel('task b');
        $task2->setBehavior('b');

        $task3 = $process->createNode();
        $task3->setLabel('task operator');
        $task3->setBehavior('operator');

        $task4 = $process->createNode();
        $task4->setLabel('task result');
        $task4->setBehavior('result');

        $start = $process->createTransition(null, $task1);
        $process->createTransition($task1, $task2);
        $process->createTransition($task2, $task3);
        $process->createTransition($task3, $task4);

        return $process;
    }

    /**
     * @return \Formapro\Pvm\MongoProcessStorage
     */
    private function getProcessStorage()
    {
        return $this->get('app.pvm.storage');
    }

    /**
     * @return \Formapro\Pvm\DefaultBehaviorRegistry
     */
    private function getBehaviorRepository()
    {
        return $this->get('app.pvm.behavior_registry');
    }

    /**
     * @return \Formapro\Pvm\ProcessEngine
     */
    private function getProcessEngine()
    {
        return $this->get('app.pvm.process_engine');
    }

    /**
     * @return \Symfony\Component\Form\FormFactory
     */
    private function getFormFactory()
    {
        return $this->get('form.factory');
    }
}
