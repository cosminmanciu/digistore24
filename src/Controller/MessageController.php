<?php
declare(strict_types=1);

namespace App\Controller;

use App\Message\SendMessage;
use App\Repository\MessageRepository;
use Controller\MessageControllerTest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @see MessageControllerTest
 */
class MessageController extends AbstractController
{
    const NO_MESSAGES = 'No messages found .';

    /**
     * @var MessageRepository
     */
    private MessageRepository $messageRepository;

    /**
     * @var MessageBusInterface
     */
    private MessageBusInterface $messageBus;

    /**
     * Added Dependency Injection Via Controller in order to facilitate easier testing
     * @param MessageRepository $messageRepository
     * @param MessageBusInterface $messageBus
     */
    public function __construct(MessageRepository $messageRepository, MessageBusInterface $messageBus)
    {
        $this->messageRepository = $messageRepository;
        $this->messageBus = $messageBus;
    }


    #[Route('/messages', name: 'list_messages', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // Renamed repo method by to findAllByStatus for better understanding
        $messages = $this->messageRepository->findAllByStatus($request->query->getString('status'));

        //Added extra validation
        if (empty($messages)) {
            return new JsonResponse(['error' => self::NO_MESSAGES], Response::HTTP_NOT_FOUND);
        }

        foreach ($messages as $key=>$message) {
            $messages[$key] = [
                'uuid' => $message->getUuid(),
                'text' => $message->getText(),
                'status' => $message->getStatus(),
            ];
        }

        return new JsonResponse($messages, Response::HTTP_OK);
    }

    #[Route('/messages/send', name:'send_message', methods: ['GET'])]
    public function send(Request $request): Response
    {
        $text = $request->query->getString('text');

        //Added extra validation
        if (empty($text)) {
            return new Response('Text is required', Response::HTTP_BAD_REQUEST);
        }

        $this->messageBus->dispatch(new SendMessage($text));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }

}