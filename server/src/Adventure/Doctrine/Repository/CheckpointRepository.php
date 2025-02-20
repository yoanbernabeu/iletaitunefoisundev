<?php

declare(strict_types=1);

namespace App\Adventure\Doctrine\Repository;

use App\Adventure\Entity\Checkpoint;
use App\Adventure\Entity\Player;
use App\Adventure\Entity\Quest;
use App\Adventure\Gateway\CheckpointGateway;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @template T
 *
 * @template-extends ServiceEntityRepository<Checkpoint>
 * @template-implements CheckpointGateway<Checkpoint>
 */
final class CheckpointRepository extends ServiceEntityRepository implements CheckpointGateway
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Checkpoint::class);
    }

    public function save(Checkpoint $checkpoint): void
    {
        if (UnitOfWork::STATE_NEW === $this->_em->getUnitOfWork()->getEntityState($checkpoint)) {
            $this->_em->persist($checkpoint);
        }
        $this->_em->flush();
    }

    public function hasStartedQuest(Player $player, Quest $quest): bool
    {
        return 1 === $this->createQueryBuilder('c')
                ->select('COUNT(c.id)')
                ->where('c.journey = :journey')
                ->andWhere('c.quest = :quest')
                ->andWhere('c.finishedAt IS NULL')
                ->setParameters(['journey' => $player->getJourney(), 'quest' => $quest])
                ->getQuery()
                ->getSingleScalarResult();
    }

    public function hasFinishedQuest(Player $player, Quest $quest): bool
    {
        return 1 === $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.journey = :journey')
            ->andWhere('c.quest = :quest')
            ->andWhere('c.finishedAt IS NOT NULL')
            ->setParameters(['journey' => $player->getJourney(), 'quest' => $quest])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getCheckpointByPlayerAndQuest(Player $player, Quest $quest): ?Checkpoint
    {
        return $this->findOneBy(['journey' => $player->getJourney(), 'quest' => $quest]);
    }
}
