<?php
declare(strict_types=1);

namespace App\Handler;

use App\Entity\Message;
use App\Message\SendMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
/**
 * TODO: Cover with a test
 */
class SendMessageHandler
{
    const MESSAGE_SENT ='sent';
    public function __construct(private EntityManagerInterface $manager)
    {
    }
    
    public function __invoke(SendMessage $sendMessage): void
    {
        $message = new Message();
        $message->setUuid(Uuid::v6()->toRfc4122());
        $message->setText($sendMessage->text);
        $message->setStatus(self::MESSAGE_SENT);
        $message->setCreatedAt(new \DateTime());

        $this->manager->persist($message);
        $this->manager->flush();
    }
}