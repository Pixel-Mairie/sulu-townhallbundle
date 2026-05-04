<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\View\ViewHandlerInterface;
use HandcraftedInTheAlps\RestRoutingBundle\Controller\Annotations\RouteResource;
use HandcraftedInTheAlps\RestRoutingBundle\Routing\ClassResourceInterface;
use Pixel\TownHallBundle\Common\DoctrineListRepresentationFactory;
use Pixel\TownHallBundle\Domain\Event\ReportCreatedEvent;
use Pixel\TownHallBundle\Domain\Event\ReportModifiedEvent;
use Pixel\TownHallBundle\Domain\Event\ReportRemovedEvent;
use Pixel\TownHallBundle\Entity\Report;
use Pixel\TownHallBundle\Reference\ReportReferenceProvider;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @RouteResource("report")
 */
class ReportController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    use RequestParametersTrait;

    private DoctrineListRepresentationFactory $doctrineListRepresentationFactory;

    private EntityManagerInterface $entityManager;

    private MediaManagerInterface $mediaManager;

    private TrashManagerInterface $trashManager;

    private DomainEventCollectorInterface $domainEventCollector;

    private ReportReferenceProvider $reportReferenceProvider;

    public function __construct(
        DoctrineListRepresentationFactory $doctrineListRepresentationFactory,
        EntityManagerInterface $entityManager,
        MediaManagerInterface $mediaManager,
        ViewHandlerInterface $viewHandler,
        TrashManagerInterface $trashManager,
        DomainEventCollectorInterface $domainEventCollector,
        ReportReferenceProvider $reportReferenceProvider,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        $this->doctrineListRepresentationFactory = $doctrineListRepresentationFactory;
        $this->entityManager = $entityManager;
        $this->mediaManager = $mediaManager;
        $this->trashManager = $trashManager;
        $this->domainEventCollector = $domainEventCollector;
        $this->reportReferenceProvider = $reportReferenceProvider;

        parent::__construct($viewHandler, $tokenStorage);
    }

    public function cgetAction(): Response
    {
        $listRepresentation = $this->doctrineListRepresentationFactory->createDoctrineListRepresentation(
            Report::RESOURCE_KEY
        );

        return $this->handleView($this->view($listRepresentation));
    }

    public function getAction(int $id): Response
    {
        $report = $this->entityManager->getRepository(Report::class)->find($id);
        if (! $report) {
            throw new NotFoundHttpException();
        }

        return $this->handleView($this->view($report));
    }

    public function putAction(Request $request, int $id): Response
    {
        $report = $this->entityManager->getRepository(Report::class)->find($id);
        if (! $report) {
            throw new NotFoundHttpException();
        }

        $data = $request->request->all();
        $this->mapDataToEntity($data, $report);
        $this->domainEventCollector->collect(
            new ReportModifiedEvent($report, $data)
        );
        $this->entityManager->flush();
        $this->reportReferenceProvider->updateReferences($report, $request->query->get('locale', 'fr'), 'admin');
        return $this->handleView($this->view($report));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function mapDataToEntity(array $data, Report $entity): void
    {
        $documentId = $data['document']['id'] ?? null;
        $title = $data['title'] ?? null;
        $description = $data['description'] ?? null;
        $isActive = $data['isActive'] ?? null;
        $entity->setTitle($title);
        $entity->setIsActive($isActive);
        $entity->setDescription($description);
        $entity->setDateReport(new \DateTimeImmutable($data['dateReport']));
        $entity->setDocument($documentId ? $this->mediaManager->getEntityById($documentId) : null);
    }

    public function postAction(Request $request): Response
    {
        $report = new Report();
        $data = $request->request->all();
        $this->mapDataToEntity($data, $report);
        $this->entityManager->persist($report);
        $this->domainEventCollector->collect(
            new ReportCreatedEvent($report, $data)
        );
        $this->entityManager->flush();
        $this->reportReferenceProvider->updateReferences($report, $request->query->get('locale', 'fr'), 'admin');

        return $this->handleView($this->view($report, 201));
    }

    public function deleteAction(int $id): Response
    {
        /** @var Report $report */
        $report = $this->entityManager->getRepository(Report::class)->find($id);
        $reportDate = $report->getDateReport();
        if ($report) {
            $this->trashManager->store(Report::RESOURCE_KEY, $report);
            $this->entityManager->remove($report);
            $this->domainEventCollector->collect(
                new ReportRemovedEvent($id, $reportDate)
            );
        }
        $this->entityManager->flush();

        return $this->handleView($this->view(null, 204));
    }

    public function getSecurityContext(): string
    {
        return Report::SECURITY_CONTEXT;
    }

    /**
     * @Rest\Post("/reports/{id}")
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws EntityNotFoundException
     */
    public function postTriggerAction(int $id, Request $request): Response
    {
        $action = $this->getRequestParameter($request, 'action', true);
        //$locale = $this->getRequestParameter($request, 'locale', true);

        try {
            switch ($action) {
                case 'enable':
                    $item = $this->entityManager->getReference(Report::class, $id);
                    $item->setIsActive(true);
                    $this->entityManager->persist($item);
                    $this->entityManager->flush();
                    break;
                case 'disable':
                    $item = $this->entityManager->getReference(Report::class, $id);
                    $item->setIsActive(false);
                    $this->entityManager->persist($item);
                    $this->entityManager->flush();
                    break;
                default:
                    throw new BadRequestHttpException(sprintf('Unknown action "%s".', $action));
            }
        } catch (RestException $exc) {
            $view = $this->view($exc->toArray(), 400);
            return $this->handleView($view);
        }

        return $this->handleView($this->view($item));
    }
}
