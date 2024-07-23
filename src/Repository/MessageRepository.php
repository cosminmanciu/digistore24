<?php
declare(strict_types = 1);

namespace App\Repository;

use App\Entity\Message;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array $criteria, array $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MessageRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    /**
     * added Query Builder for a faster response from db
     * @param string|null $status
     * @return Message[]
     */
    public function findAllByStatus(?string $status): array
    {
        $entityManager = $this->getEntityManager();
        $queryBuilder = $entityManager->createQueryBuilder()
            ->select('m')
            ->from(Message::class, 'm');
        if (!empty($status)) {
            $queryBuilder->where('m.status = :status');
            $queryBuilder->setParameter('status', $status);
        }
        $query = $queryBuilder->orderBy('m.createdAt', 'DESC')->getQuery();
        /** @var Message[] $result */
        $result = $query->getResult();

        return $result;
    }
}
