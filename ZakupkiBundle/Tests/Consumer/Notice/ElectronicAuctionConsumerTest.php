<?php
/**
 * Created by PhpStorm.
 * User: iGusev
 * Date: 29/09/15
 * Time: 17:37
 */

namespace ZakupkiBundle\Tests\Consumer\Notice;

use Liip\FunctionalTestBundle\Test\WebTestCase;
use PhpAmqpLib\Message\AMQPMessage;

class ElectronicAuctionConsumerTest extends WebTestCase
{
    public function setUp()
    {
        $kernel = static::createKernel();
        $kernel->boot();

        $this->client = static::createClient();

        //$this->logIn();
    }

    public function testExecute()
    {
        /** @var \PhpAmqpLib\Message\AMQPMessage $messsage */
        $messsage = $this
            ->getMockBuilder('\PhpAmqpLib\Message\AMQPMessage')
            ->setMethods(array('get'))
            ->getMock();
        $messsage->expects($this->once())->method('get')->will($this->returnValue('integrations.zakupki.electronic_auction'));

//        $messsage->set('routing_key', 'integrations.zakupki.electronic_auction');
        $messsage->setBody((object) ['lotId' => '43ce4fb2-2bee-4dd4-b21d-7091cf7962ea']);

        $cZakupki = $this->getContainer()->get('zakupki.consumer.notice.electronic_auction');

        echo $cZakupki->execute($messsage);
    }
}
