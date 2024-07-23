<?php

namespace App\Tests\Handler;

use App\Entity\Message;
use App\Handler\SendMessageHandler;
use App\Message\SendMessage;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SendMessageHandlerTest extends TestCase
{
    public function testInvoke(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $validator = $this->createMock(ValidatorInterface::class);

        $handler = new SendMessageHandler($entityManager, $validator);

        $sendMessage = new SendMessage('Test message');

        $entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (Message $message) use ($sendMessage) {
                $this->assertNotEmpty($message->getUuid());
                $this->assertSame($sendMessage->text, $message->getText());
                $this->assertSame(SendMessageHandler::MESSAGE_SENT, $message->getStatus());
                $this->assertInstanceOf(\DateTime::class, $message->getCreatedAt());
                return true;
            }));

        $entityManager->expects($this->once())
            ->method('flush');

        $handler($sendMessage);
    }
}
