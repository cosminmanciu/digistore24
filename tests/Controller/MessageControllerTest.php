<?php
declare(strict_types=1);

namespace Controller;

use App\DataFixtures\TestFixtures;
use App\Entity\Message;
use App\Message\SendMessage;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Normalizer\NormalizableInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Zenstruck\Messenger\Test\InteractsWithMessenger;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use App\Repository\MessageRepository;
use Symfony\Component\HttpFoundation\Response;

class MessageControllerTest extends WebTestCase
{
    use InteractsWithMessenger;

    protected AbstractDatabaseTool $databaseTool;
    protected KernelBrowser $client;


    public function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        /** @var DatabaseToolCollection $databaseToolCollection */
        $databaseToolCollection = self::getContainer()->get(DatabaseToolCollection::class);
        $this->databaseTool = $databaseToolCollection->get();
        $this->databaseTool->loadFixtures([
            TestFixtures::class
        ]);
    }


    public function testMessageIsSent(): void
    {

        $this->client->request('GET', '/messages/send', [
            'text' => 'Test Message',
        ]);

        $this->assertResponseIsSuccessful();
        // This is using https://packagist.org/packages/zenstruck/messenger-test
        $this->transport('sync')
            ->queue()
            ->assertContains(SendMessage::class, 1);
    }

    public function testListWithMessages(): void
    {

        $this->client->request('GET', '/messages', ['status' => 'sent']);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $response = $this->client->getResponse();

        $content = $response->getContent();
        if (empty($content))
        {
            $this->fail('Failed to decode JSON response.');
        }
        $messages = json_decode($content, true);

        if ($messages === null) {
            $this->fail('Failed to decode JSON response.');
        }

        /** @var array<array{uuid: string, text: string, status: string}> $messages */
        foreach ($messages as $message) {
            $this->assertArrayHasKey('uuid', $message);
            $this->assertArrayHasKey('text', $message);
            $this->assertArrayHasKey('status', $message);
        }

    }

    public function testListWithNoMessages(): void
    {
        $messageRepository = $this->createMock(MessageRepository::class);
        $messageRepository->expects($this->any())->method('findAllByStatus')
            ->willReturn([
            ]);
        self::getContainer()->set('App\Repository\MessageRepository', $messageRepository);


        $this->client->request('GET', '/messages', ['status' => 'sent']);
        $response = $this->client->getResponse();
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertStringContainsString('No messages found', $content);

    }

    /*
     * Added extra test to see if message is sent for inmemory Sync
     */

//    public function testMessageIsSentInMemory(): void
//    {
//
//        $this->client->request('GET', '/messages/send',[
//            'text' => 'Hello Test',
//        ]);
//        $this->assertResponseIsSuccessful();
//
//
//        $transport = $this->getContainer()->get('messenger.transport.sync');
//
//        $this->assertCount(1, $transport->getSent());
//    }
    protected function tearDown(): void
    {
        $this->databaseTool->loadFixtures([]);
        unset($this->databaseTool);
        parent::tearDown();
    }
}