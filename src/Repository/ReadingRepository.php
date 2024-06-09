<?php

namespace App\Repository;

use App\Entity\Reading;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reading>
 *
 * @method Reading|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reading|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reading[]    findAll()
 * @method Reading[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReadingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reading::class);
    }

    public function findRecentReadingsByStation(int $stationId, int $days): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.station = :stationId')
            ->andWhere('r.date >= :date')
            ->setParameter('stationId', $stationId)
            ->setParameter('date', new \DateTime("-$days days"))
            ->orderBy('r.date', 'DESC');

        return $qb->getQuery()->getResult();
    }
    public function findRecentReadingsByStationHour(int $stationId, int $days): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select(
                'DATE_FORMAT(r.date, \'%Y-%m-%d %H:00:00\') AS hour',
                'AVG(r.temperature) AS avgTemperature',
                'AVG(r.pressure) AS avgPressure',
                'AVG(r.altitude) AS avgAltitude',
                'AVG(r.humidity) AS avgHumidity'
            )
            ->andWhere('r.station = :stationId')
            ->andWhere('r.date >= :date')
            ->setParameter('stationId', $stationId)
            ->setParameter('date', new \DateTime("-$days days"))
            ->groupBy('hour')
            ->orderBy('hour', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function findRecentReadingsByStationDay(int $stationId, int $days): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select(
                'DATE(r.date) AS day',
                'AVG(r.temperature) AS avgTemperature',
                'AVG(r.pressure) AS avgPressure',
                'AVG(r.altitude) AS avgAltitude',
                'AVG(r.humidity) AS avgHumidity'
            )
            ->andWhere('r.station = :stationId')
            ->andWhere('r.date >= :date')
            ->setParameter('stationId', $stationId)
            ->setParameter('date', new \DateTime("-$days days"))
            ->groupBy('day')
            ->orderBy('day', 'ASC');
        return $qb->getQuery()->getResult();
    }
    public function findRecentReadingsByStationMonth(int $stationId, int $days): array
    {
        $qb = $this->createQueryBuilder('r')
            ->select('DATE_FORMAT(r.date, \'%Y-%m\') AS month', 'AVG(r.temperature) AS avgTemperature', 'AVG(r.pressure) AS avgPressure', 'AVG(r.altitude) AS avgAltitude', 'AVG(r.humidity) AS avgHumidity')
            ->andWhere('r.station = :stationId')
            ->andWhere('r.date >= :date')
            ->setParameter('stationId', $stationId)
            ->setParameter('date', new \DateTime("-$days days"))
            ->groupBy('month')
            ->orderBy('month', 'ASC');
        return $qb->getQuery()->getResult();
    }
    //    /**
    //     * @return Reading[] Returns an array of Reading objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Reading
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
