<?php

namespace App\Controller;

use App\Entity\Station;
use App\Entity\User;
use App\Repository\StationRepository;
use App\Repository\BuildingRepository;
use App\Repository\ReadingRepository;
use App\Service\AuthManager;
use App\Service\StateManager;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class StationController extends AbstractController
{
    #[Route('/api/stations/{id}', name: 'station_list', methods: ['GET'], priority: 2)]
    public function index(
        #[CurrentUser()] User $user,
        Request $request,
        BuildingRepository $repo,
        ReadingRepository $readingRepo,
        AuthManager $auth,
        EntityManagerInterface $manager,
        int $id,
        StateManager $stateManager
    ): Response {
        if (($authResponse = $auth->checkAuth($user, $request)) !== null)
            return $authResponse;
        $manager->getConnection()->beginTransaction();
        try {
            $building = $repo->findOneBy(['id' => $id]);
            $stations = $building->getStations();    
            $stateManager->refreshStateStations($manager, $stations, $readingRepo);                
        } catch (Exception $e) {
            $manager->getConnection()->rollBack();
            return $this->json([
                'message' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
        return $this->json([
            'message' => 'ok',
            'list_station' => $stations,
        ], Response::HTTP_OK);
    }

    #[Route('/api/stations', name: 'station_create', methods: ["POST"], priority: 2)]
    public function create(
        #[CurrentUser()] User $user,
        Request $request,
        EntityManagerInterface $manager,
        StationRepository $repo,
        BuildingRepository $buildingRepo,
        AuthManager $auth
    ): Response {

        if (($authResponse = $auth->checkAuth($user, $request)) !== null)
            return $authResponse;

        $manager->getConnection()->beginTransaction();

        try {
            $jsonbody = $request->getContent();
            $body = json_decode($jsonbody, true);

            $building = $buildingRepo->findOneBy(['id' => $body['id_building']]);
            $station = $repo->findOneBy(['mac' => $body['mac_address']]);

            if ($station && $station->getState()) {
                return $this->json([
                    'message' => 'Station already used',
                ], Response::HTTP_UNAUTHORIZED);
            }

            if (!$station) {
                $station = new Station();
                $station->setMac($body['mac_address']);
            }

            $station
                ->setBuilding($building)
                ->setActivationDate(new \DateTime('now', new DateTimeZone('Europe/Paris')))
                ->setState(0)
                ->setName($body['name_station']);
            $manager->persist($station);
            $manager->flush();
            $manager->getConnection()->commit();
            return $this->json([
                'message' => 'station created',
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            $manager->getConnection()->rollBack();
            print_r($e->getMessage());
            return $this->json([
                'message' => 'Bad Request',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/stations', name: 'station_delete', methods: ["DELETE"], priority: 2)]
    public function delete(
        #[CurrentUser()] User $user,
        Request $request,
        EntityManagerInterface $manager,
        StationRepository $repo,
        AuthManager $auth
    ): Response {
        if (($authResponse = $auth->checkAuth($user, $request)) !== null)
            return $authResponse;

        $manager->getConnection()->beginTransaction();

        try {
            $jsonbody = $request->getContent();
            $body = json_decode($jsonbody, true);
            $station = $repo->findOneBy(['mac' => $body['mac_address']]);
            $station
                ->setBuilding(null)
                ->setActivationDate(null)
                ->setState(0)
                ->setName(null);
            $manager->persist($station);
            $manager->flush();
            $manager->getConnection()->commit();
            return $this->json([
                'message' => 'Station deleted',
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            $manager->getConnection()->rollBack();
            return $this->json([
                'message' => 'Bad Request',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('api/stations/{id}', name: 'station_update', methods: ["PATCH"], priority: 2)]
    public function update(
        #[CurrentUser()] User $user,
        Request $request,
        EntityManagerInterface $manager,
        AuthManager $auth,
        StationRepository $repo,
        BuildingRepository $building_repo,
        int $id
    ) {

        if (!$auth->checkAuth($user, $request)) {
            return $this->json([
                'message' => 'missing credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $manager->getConnection()->beginTransaction();

        try {
            $jsonbody = $request->getContent();
            $body = json_decode($jsonbody, true);
            $station = $repo->findOneBy(['id' => $id]);
            if ($body['new_name'] != "")
                $station->setName($body['new_name']);
            if ($body['newBuilding_id'] !== 0) {
                $building = $building_repo->findOneBy(["id" => $body["newBuilding_id"]]);
                if ($building)
                    $station->setBuilding($building);
            }
            $manager->persist($station);
            $manager->flush();
            $manager->getConnection()->commit();
            return $this->json([
                'message' => 'station updated'
            ], Response::HTTP_OK);
        } catch (Exception $e) {
            $manager->getConnection()->rollBack();
            return $this->json([
                'message' => $e,
            ], Response::HTTP_CONFLICT);
        }
    }
}
