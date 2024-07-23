<?php
declare(strict_types=1);

namespace App\Handler;

use App\Entity\Message;
use App\Message\SendMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsMessageHandler]
/**
 *
 */
class SendMessageHandler
{
    const MESSAGE_SENT = 'sent';
    const MESSAGE_READ= 'read';
    const MESSAGE_VALID = 'Message is valid .';

    const MESSAGE_INVALID = 'Message is invalid .';

    public function __construct(
        private EntityManagerInterface $manager,
        private ValidatorInterface     $validator
    )
    {
    }

    /**
     * @param SendMessage $sendMessage
     * @return array{message: string, error: ?string}
     */
    public function __invoke(SendMessage $sendMessage): array
    {
        $message = new Message();

        $message->setUuid(Uuid::v6()->toRfc4122());
        $message->setText($sendMessage->text);
        $message->setStatus(self::MESSAGE_SENT);
        $message->setCreatedAt(new \DateTime());

        $errors = $this->validator->validate($message);

        if (count($errors) > 0) {

            return [
                'message' => self::MESSAGE_INVALID,
                'error' => (string)$errors
            ];

        }

        $this->manager->persist($message);
        $this->manager->flush();

        return [
            'message' => self::MESSAGE_VALID,
            'error' => null,
        ];
    }
}