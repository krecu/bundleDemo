<?php

namespace ZakupkiBundle\Tests\Consumer;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use PhpAmqpLib\Message\AMQPMessage;

class ZakupkiConsumerTest extends WebTestCase
{
    public function setUp()
    {
        $kernel = static::createKernel();
        $kernel->boot();

        $this->client = static::createClient();

        //$this->logIn();
    }

    public function testExecuteAction()
    {
        /** @var \PhpAmqpLib\Message\AMQPMessage $messsage */
        $messsage = $this
            ->getMockBuilder('\PhpAmqpLib\Message\AMQPMessage')
            ->setMethods(array('get'))
            ->getMock();
        $messsage->expects($this->once())->method('get')->will($this->returnValue('integrations.zakupki.plan'));

        //$messsage->set('routing_key', 'integrations.zakupki.plan');
        $messsage->setBody((object) ['planId' => '11af21c5-7ee4-4bc4-af1d-6511b17a4ec0']);

        $cZakupki = $this->getContainer()->get('zakupki.consumer.plan.procurement');

        echo $cZakupki->execute($messsage);
    }
}