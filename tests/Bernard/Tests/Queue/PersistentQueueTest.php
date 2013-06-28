<?php

namespace Bernard\Tests\Queue;

use Bernard\Message\Envelope;
use Bernard\Queue\PersistentQueue;

class PersistentQueueTest extends AbstractQueueTest
{
    public function setUp()
    {
        $this->connection = $this->getMock('Bernard\Driver');
        $this->serializer = $this->getMock('Bernard\Serializer');
    }

    public function testDequeue()
    {
        $messageWrapper = new Envelope($this->getMock('Bernard\Message'));

        $this->connection->expects($this->at(1))->method('popMessage')->with($this->equalTo('send-newsletter'))
            ->will($this->returnValue(array('serialized', null)));

        $this->connection->expects($this->at(2))->method('popMessage')->with($this->equalTo('send-newsletter'))
            ->will($this->returnValue(null));

        $this->serializer->expects($this->once())->method('deserialize')->with($this->equalTo('serialized'))
            ->will($this->returnValue($messageWrapper));

        $queue = $this->createQueue('send-newsletter');

        $this->assertSame($messageWrapper, $queue->dequeue());
        $this->assertInternalType('null', $queue->dequeue());
    }

    /**
     * @dataProvider peekDataProvider
     */
    public function testPeekDeserializesMessages($index, $limit)
    {
        $this->serializer->expects($this->at(0))->method('deserialize')->with($this->equalTo('message1'));
        $this->serializer->expects($this->at(1))->method('deserialize')->with($this->equalTo('message2'));
        $this->serializer->expects($this->at(2))->method('deserialize')->with($this->equalTo('message3'));

        $this->connection->expects($this->once())->method('peekQueue')->with($this->equalTo('send-newsletter'), $this->equalTo($index), $this->equalTo($limit))
            ->will($this->returnValue(array('message1', 'message2', 'message3')));

        $queue = $this->createQueue('send-newsletter');
        $queue->peek($index, $limit);
    }

    public function dataClosedMethods()
    {
        $methods = parent::dataClosedMethods();
        $methods[] = array('register', array());

        return $methods;
    }

    public function peekDataProvider()
    {
        return array(
            array(0, 20),
            array(1, 10),
            array(20, 100),
        );
    }

    protected function createQueue($name)
    {
        return new PersistentQueue($name, $this->connection, $this->serializer);
    }
}
