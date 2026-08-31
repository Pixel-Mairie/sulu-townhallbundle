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
use Pixel\TownHallBundle\Domain\Event\DecreeCreatedEvent;
use Pixel\TownHallBundle\Domain\Event\DecreeModifiedEvent;
use Pixel\TownHallBundle\Domain\Event\DecreeRemovedEvent;
use Pixel\TownHallBundle\Entity\Decree;
use Pixel\TownHallBundle\Reference\DecreeReferenceProvider;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @RouteResource("decree")
 */
class DecreeController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    private DoctrineListRepresentationFactory $doctrineListRepresentationFactory;

    private EntityManagerInterface $entityManager;

    private MediaManagerInterface $mediaManager;

    private CategoryManagerInterface $categoryManager;

    private TrashManagerInterface $trashManager;

    private DomainEventCollectorInterface $domainEventCollector;

    private DecreeReferenceProvider $decreeReferenceProvider;

    public function __construct(
        DoctrineListRepresentationFactory $doctrineListRepresentationFactory,
        EntityManagerInterface $entityManager,
        MediaManagerInterface $mediaManager,
        CategoryManagerInterface $categoryManager,
        TrashManagerInterface $trashManager,
        DomainEventCollectorInterface $domainEventCollector,
        ViewHandlerInterface $viewHandler,
        DecreeReferenceProvider $decreeReferenceProvider,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        $this->doctrineListRepresentationFactory = $doctrineListRepresentationFactory;
        $this->entityManager = $entityManager;
        $this->mediaManager = $mediaManager;
        $this->categoryManager = $categoryManager;
        $this->trashManager = $trashManager;
        $this->domainEventCollector = $domainEventCollector;
        $this->decreeReferenceProvider = $decreeReferenceProvider;
        parent::__construct($viewHandler, $tokenStorage);
    }

    public function cgetAction(): Response
    {
        $listRepresentation = $this->doctrineListRepresentationFactory->createDoctrineListRepresentation(
            Decree::RESOURCE_KEY
        );
        return $this->handleView($this->view($listRepresentation));
    }

    public function getAction(int $id): Response
    {
        $decree = $this->entityManager->getRepository(Decree::class)->find($id);
        if (! $decree) {
            throw new NotFoundHttpException();
        }
        return $this->handleView($this->view($decree));
    }

    public function putAction(Request $request, int $id): Response
    {
        $decree = $this->entityManager->getRepository(Decree::class)->find($id);
        if (! $decree) {
            throw new NotFoundHttpException();
        }
        $data = $request->request->all();
        $this->mapDataToEntity($data, $decree);
        $this->domainEventCollector->collect(
            new DecreeModifiedEvent($decree, $data)
        );
        $this->entityManager->flush();
        $this->decreeReferenceProvider->updateReferences($decree, $request->query->get('locale', 'fr'), 'admin');
        return $this->handleView($this->view($decree));
    }

    /**
     * @param array<mixed> $data
     * @throws \Sulu\Bundle\CategoryBundle\Exception\CategoryIdNotFoundException
     */
    protected function mapDataToEntity(array $data, Decree $entity): void
    {
        $endDate = $data['endDate'] ?? null;
        $description = $data['description'] ?? null;
        $isActive = $data['isActive'] ?? null;
        $categoryId = (isset($data['category']['id'])) ? $data['category']['id'] : $data['category'];

        $entity->setTitle($data['title']);
        $entity->setStartDate(new \DateTimeImmutable($data['startDate']));
        $entity->setEndDate($endDate ? new \DateTimeImmutable($endDate) : null);
        $entity->setPdf($this->mediaManager->getEntityById($data['pdf']['id']));
        $entity->setDescription($description);
        $entity->setIsActive($isActive);
        $entity->setCategory($this->categoryManager->findById($categoryId));
    }

    public function postAction(Request $request): Response
    {
        $decree = new Decree();
        $data = $request->request->all();
        $this->mapDataToEntity($data, $decree);
        $this->entityManager->persist($decree);
        $this->domainEventCollector->collect(
            new DecreeCreatedEvent($decree, $data)
        );
        $this->entityManager->flush();
        $this->decreeReferenceProvider->updateReferences($decree, $request->query->get('locale', 'fr'), 'admin');
        return $this->handleView($this->view($decree, 201));
    }

    public function deleteAction(int $id): Response
    {
        /** @var Decree $decree */
        $decree = $this->entityManager->getRepository(Decree::class)->find($id);
        $decreeTitle = $decree->getTitle();
        if ($decree) {
            $this->trashManager->store(Decree::RESOURCE_KEY, $decree);
            $this->entityManager->remove($decree);
            $this->domainEventCollector->collect(
                new DecreeRemovedEvent($id, $decreeTitle)
            );
        }
        $this->entityManager->flush();
        return $this->handleView($this->view(null, 204));
    }

    public function getSecurityContext(): string
    {
        return Decree::SECURITY_CONTEXT;
    }

    /**
     * @Rest\Post("/decrees/{id}")
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws EntityNotFoundException
     */
    public function postTriggerAction(int $id, Request $request): Response
    {
        $action = $request->query->get('action');
        //$locale = $request-query->get('locale');

        try {
            switch ($action) {
                case 'enable':
                    $item = $this->entityManager->getReference(Decree::class, $id);
                    $item->setIsActive(true);
                    $this->entityManager->persist($item);
                    $this->entityManager->flush();
                    break;
                case 'disable':
                    $item = $this->entityManager->getReference(Decree::class, $id);
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
