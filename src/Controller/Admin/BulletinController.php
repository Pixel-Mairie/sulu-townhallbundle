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
use Pixel\TownHallBundle\Domain\Event\BulletinCreatedEvent;
use Pixel\TownHallBundle\Domain\Event\BulletinModifiedEvent;
use Pixel\TownHallBundle\Domain\Event\BulletinRemovedEvent;
use Pixel\TownHallBundle\Entity\Bulletin;
use Pixel\TownHallBundle\Reference\BulletinReferenceProvider;
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
 * @RouteResource("bulletin")
 */
class BulletinController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    use RequestParametersTrait;

    private DoctrineListRepresentationFactory $doctrineListRepresentationFactory;

    private EntityManagerInterface $entityManager;

    private MediaManagerInterface $mediaManager;

    private TrashManagerInterface $trashManager;

    private DomainEventCollectorInterface $domainEventCollector;

    private BulletinReferenceProvider $bulletinReferenceProvider;

    public function __construct(
        DoctrineListRepresentationFactory $doctrineListRepresentationFactory,
        EntityManagerInterface $entityManager,
        MediaManagerInterface $mediaManager,
        ViewHandlerInterface $viewHandler,
        TrashManagerInterface $trashManager,
        DomainEventCollectorInterface $domainEventCollector,
        BulletinReferenceProvider $bulletinReferenceProvider,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        $this->doctrineListRepresentationFactory = $doctrineListRepresentationFactory;
        $this->entityManager = $entityManager;
        $this->mediaManager = $mediaManager;
        $this->trashManager = $trashManager;
        $this->domainEventCollector = $domainEventCollector;
        $this->bulletinReferenceProvider = $bulletinReferenceProvider;

        parent::__construct($viewHandler, $tokenStorage);
    }

    public function cgetAction(): Response
    {
        $listRepresentation = $this->doctrineListRepresentationFactory->createDoctrineListRepresentation(
            Bulletin::RESOURCE_KEY
        );

        return $this->handleView($this->view($listRepresentation));
    }

    public function getAction(int $id): Response
    {
        $bulletin = $this->entityManager->getRepository(Bulletin::class)->find($id);
        if (! $bulletin) {
            throw new NotFoundHttpException();
        }

        return $this->handleView($this->view($bulletin));
    }

    public function putAction(Request $request, int $id): Response
    {
        $bulletin = $this->entityManager->getRepository(Bulletin::class)->find($id);
        if (! $bulletin) {
            throw new NotFoundHttpException();
        }

        $data = $request->request->all();
        $this->mapDataToEntity($data, $bulletin);
        $this->domainEventCollector->collect(
            new BulletinModifiedEvent($bulletin, $data)
        );
        $this->entityManager->flush();
        $this->bulletinReferenceProvider->updateReferences($bulletin, $request->query->get('locale', 'fr'), 'admin');
        return $this->handleView($this->view($bulletin));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function mapDataToEntity(array $data, Bulletin $entity): void
    {
        $coverId = $data['cover']['id'] ?? null;
        $documentId = $data['document']['id'] ?? null;
        $description = $data['description'] ?? null;
        $state = $data['state'] ?? null;
        $entity->setTitle($data['title']);
        $entity->setDateBulletin(new \DateTimeImmutable($data['dateBulletin']));
        $entity->setDocument($documentId ? $this->mediaManager->getEntityById($documentId) : null);
        $entity->setCover($coverId ? $this->mediaManager->getEntityById($coverId) : null);
        $entity->setDescription($description);
        $entity->setState($state);
    }

    public function postAction(Request $request): Response
    {
        $bulletin = new Bulletin();
        $data = $request->request->all();
        $this->mapDataToEntity($data, $bulletin);
        $this->entityManager->persist($bulletin);
        $this->domainEventCollector->collect(
            new BulletinCreatedEvent($bulletin, $data)
        );
        $this->entityManager->flush();
        $this->bulletinReferenceProvider->updateReferences($bulletin, $request->query->get('locale', 'fr'), 'admin');

        return $this->handleView($this->view($bulletin, 201));
    }

    public function deleteAction(int $id): Response
    {
        /** @var Bulletin $bulletin */
        $bulletin = $this->entityManager->getRepository(Bulletin::class)->find($id);
        $bulletinTitle = $bulletin->getTitle();
        if ($bulletin) {
            $this->trashManager->store(Bulletin::RESOURCE_KEY, $bulletin);
            $this->entityManager->remove($bulletin);
            $this->domainEventCollector->collect(
                new BulletinRemovedEvent($id, $bulletinTitle)
            );
        }
        $this->entityManager->flush();

        return $this->handleView($this->view(null, 204));
    }

    /**
     * @Rest\Post("/bulletins/{id}")
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
                    $item = $this->entityManager->getReference(Bulletin::class, $id);
                    $item->setState(true);
                    $this->entityManager->persist($item);
                    $this->entityManager->flush();
                    break;
                case 'disable':
                    $item = $this->entityManager->getReference(Bulletin::class, $id);
                    $item->setState(false);
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

    public function getSecurityContext(): string
    {
        return Bulletin::SECURITY_CONTEXT;
    }
}
