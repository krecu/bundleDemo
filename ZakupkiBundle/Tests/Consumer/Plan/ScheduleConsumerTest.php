<?php

namespace ZakupkiBundle\Tests\Consumer\Plan;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use PhpAmqpLib\Message\AMQPMessage;

class ScheduleConsumerTest extends WebTestCase
{
    public function setUp()
    {
        $kernel = static::createKernel();
        $kernel->boot();

        $this->client = static::createClient();
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
        $messsage->setBody((object) [
            'foivId' => '4bb574eb-be09-48fb-a087-b5b258718f0b'
        ]);

        $cZakupki = $this->getContainer()->get('zakupki.consumer.plan.schedule');

        echo $cZakupki->execute($messsage);
    }
}