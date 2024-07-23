<?php
declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Message;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Uid\Uuid;
use function Psl\Iter\random;

class TestFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        for ($i = 0; $i < 2; $i++) {
            $message = new Message();
            $message->setUuid('test_uuid_' . $i);
            $message->setText('This is the text message for test' . $i);
            $message->setStatus(random(['sent']));
            $message->setCreatedAt(new \DateTime());

            $manager->persist($message);
        }

        $manager->flush();
    }
}
