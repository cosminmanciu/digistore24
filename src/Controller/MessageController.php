<?php
declare(strict_types=1);

namespace App\Controller;

use App\Handler\SendMessageHandler;
use App\Message\SendMessage;
use App\Repository\MessageRepository;
use App\Service\SanitizeInterface;
use Controller\MessageControllerTest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
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


    /** @var SanitizeInterface $sanitize */
    private SanitizeInterface $sanitize;


    /**
     * Added Dependency Injection Via Controller in order to facilitate easier testing
     * @param MessageRepository $messageRepository
     * @param MessageBusInterface $messageBus
     * @param SanitizeInterface $sanitize
     */
    public function __construct(
        MessageRepository   $messageRepository,
        MessageBusInterface $messageBus,
        SanitizeInterface   $sanitize
    )
    {
        $this->messageRepository = $messageRepository;
        $this->messageBus = $messageBus;
        $this->sanitize = $sanitize;
    }


    #[Route('/messages', name: 'list_messages', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $status = $request->query->getString('status');

        if (!empty($status) && !$this->sanitize->isValid($status)) {

            return new JsonResponse('Message is invalid', Response::HTTP_BAD_REQUEST);
        }

        // Renamed repo method by to findAllByStatus for better understanding
        $messages = $this->messageRepository->findAllByStatus($status);

        //Added extra validation
        if (empty($messages)) {
            return new JsonResponse(['error' => self::NO_MESSAGES], Response::HTTP_NOT_FOUND);
        }

        foreach ($messages as $key => $message) {
            $messages[$key] = [
                'uuid' => $message->getUuid(),
                'text' => $message->getText(),
                'status' => $message->getStatus(),
            ];
        }

        return new JsonResponse($messages, Response::HTTP_OK);
    }

    #[Route('/messages/send', name: 'send_message', methods: ['GET'])]
    public function send(Request $request): Response
    {
        $text = $request->query->getString('text');
        /** @var Envelope $envelope */
        $envelope = $this->messageBus->dispatch(new SendMessage($text));

        $handledStamps = $envelope->all(HandledStamp::class);

        if (!empty($handledStamps) && $handledStamps[0] instanceof HandledStamp) {
            $handledStamp = $handledStamps[0];

            $result = $handledStamp->getResult();

            if (is_array($result)) {
                if (isset($result['message']) && $result['message'] === SendMessageHandler::MESSAGE_VALID) {
                    return new Response(null, Response::HTTP_NO_CONTENT);
                }
                if (isset($result['error']) && $result['message'] === SendMessageHandler::MESSAGE_INVALID) {
                    return new Response('Message is invalid', Response::HTTP_BAD_REQUEST);
                }
            }
        } else {
            return new Response('Message not processed yet', Response::HTTP_ACCEPTED);
        }

        return new Response('Internal Server Error', Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}