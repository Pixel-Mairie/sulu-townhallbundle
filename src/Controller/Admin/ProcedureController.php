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
use Pixel\TownHallBundle\Domain\Event\ProcedureCreatedEvent;
use Pixel\TownHallBundle\Domain\Event\ProcedureModifiedEvent;
use Pixel\TownHallBundle\Domain\Event\ProcedureRemovedEvent;
use Pixel\TownHallBundle\Entity\Procedure;
use Pixel\TownHallBundle\Reference\ProcedureReferenceProvider;
use Pixel\TownHallBundle\Repository\ProcedureRepository;
use Sulu\Bundle\ActivityBundle\Application\Collector\DomainEventCollectorInterface;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManagerInterface;
use Sulu\Bundle\RouteBundle\Entity\RouteRepositoryInterface;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;
use Sulu\Bundle\TrashBundle\Application\TrashManager\TrashManagerInterface;
use Sulu\Component\Rest\AbstractRestController;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\RequestParametersTrait;
use Sulu\Component\Security\SecuredControllerInterface;
use Sulu\Component\Webspace\Manager\WebspaceManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * @RouteResource("procedure")
 */
class ProcedureController extends AbstractRestController implements ClassResourceInterface, SecuredControllerInterface
{
    use RequestParametersTrait;

    private DoctrineListRepresentationFactory $doctrineListRepresentationFactory;

    private EntityManagerInterface $entityManager;

    private MediaManagerInterface $mediaManager;

    private CategoryManagerInterface $categoryManager;

    private ProcedureRepository $repository;

    private WebspaceManagerInterface $webspaceManager;

    private RouteManagerInterface $routeManager;

    private RouteRepositoryInterface $routeRepository;

    private TrashManagerInterface $trashManager;

    private DomainEventCollectorInterface $domainEventCollector;

    private ProcedureReferenceProvider $procedureReferenceProvider;

    public function __construct(
        DoctrineListRepresentationFactory $doctrineListRepresentationFactory,
        EntityManagerInterface $entityManager,
        MediaManagerInterface $mediaManager,
        ViewHandlerInterface $viewHandler,
        CategoryManagerInterface $categoryManager,
        ProcedureRepository $repository,
        WebspaceManagerInterface $webspaceManager,
        RouteManagerInterface $routeManager,
        RouteRepositoryInterface $routeRepository,
        TrashManagerInterface $trashManager,
        DomainEventCollectorInterface $domainEventCollector,
        ProcedureReferenceProvider $procedureReferenceProvider,
        ?TokenStorageInterface $tokenStorage = null
    ) {
        $this->doctrineListRepresentationFactory = $doctrineListRepresentationFactory;
        $this->entityManager = $entityManager;
        $this->mediaManager = $mediaManager;
        $this->categoryManager = $categoryManager;
        $this->repository = $repository;
        $this->webspaceManager = $webspaceManager;
        $this->routeManager = $routeManager;
        $this->routeRepository = $routeRepository;
        $this->trashManager = $trashManager;
        $this->domainEventCollector = $domainEventCollector;
        $this->procedureReferenceProvider = $procedureReferenceProvider;

        parent::__construct($viewHandler, $tokenStorage);
    }

    public function cgetAction(Request $request): Response
    {
        $locale = $request->query->get('locale');
        $listRepresentation = $this->doctrineListRepresentationFactory->createDoctrineListRepresentation(
            Procedure::RESOURCE_KEY,
            [],
            [
                'locale' => $locale,
            ]
        );

        return $this->handleView($this->view($listRepresentation));
    }

    public function getAction(int $id, Request $request): Response
    {
        $procedure = $this->load($id, $request);
        if (! $procedure) {
            throw new NotFoundHttpException();
        }

        if ($procedure->getTitle() === null && $procedure->getDefaultLocale()) {
            $request->setMethod($procedure->getDefaultLocale());
            $procedure = $this->load($id, $request, $procedure->getDefaultLocale());
        }

        return $this->handleView($this->view($procedure));
    }

    protected function load(int $id, Request $request, string $defaultLocale = null): ?Procedure
    {
        return $this->repository->findById($id, ($defaultLocale) ? $defaultLocale : (string) $this->getLocale($request));
    }

    public function putAction(Request $request, int $id): Response
    {
        $procedure = $this->load($id, $request);
        if (! $procedure) {
            throw new NotFoundHttpException();
        }

        $data = $request->request->all();
        $this->mapDataToEntity($data, $procedure);
        $this->updateRoutesForEntity($procedure);
        $this->domainEventCollector->collect(
            new ProcedureModifiedEvent($procedure, $data)
        );
        $this->entityManager->flush();
        $this->save($procedure);
        $this->procedureReferenceProvider->updateReferences($procedure, (string) $this->getLocale($request), 'admin');
        return $this->handleView($this->view($procedure));
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function mapDataToEntity(array $data, Procedure $entity): void
    {
        $state = $data['state'] ?? null;
        $coverId = $data['cover']['id'] ?? null;
        $documentId = $data['document']['id'] ?? null;
        $externalLink = $data['externalLink'] ?? null;
        $categoryId = (isset($data['category']['id'])) ? $data['category']['id'] : $data['category'];
        $seo = (isset($data['ext']['seo'])) ? $data['ext']['seo'] : null;

        $entity->setTitle($data['title']);
        $entity->setState($state);
        $entity->setRoutePath($data['routePath']);
        $entity->setCover($coverId ? $this->mediaManager->getEntityById($coverId) : null);
        $entity->setDocument($documentId ? $this->mediaManager->getEntityById($documentId) : null);
        $entity->setExternalLink($externalLink);
        $entity->setCategory($this->categoryManager->findById($categoryId));
        $entity->setDescription($data['description']);
        $entity->setSeo($seo);
    }

    protected function updateRoutesForEntity(Procedure $entity): void
    {
        // create route for all locales of the application because event entity is not localized
        foreach ($this->webspaceManager->getAllLocales() as $locale) {
            $this->routeManager->createOrUpdateByAttributes(
                Procedure::class,
                (string) $entity->getId(),
                $locale,
                $entity->getRoutePath(),
            );
        }
    }

    protected function save(Procedure $procedure): void
    {
        $this->repository->save($procedure);
    }

    public function postAction(Request $request): Response
    {
        $procedure = $this->create($request);
        $data = $request->request->all();
        $this->mapDataToEntity($data, $procedure);
        $this->save($procedure);
        $this->updateRoutesForEntity($procedure);
        $this->domainEventCollector->collect(
            new ProcedureCreatedEvent($procedure, $data)
        );
        $this->entityManager->flush();
        $this->procedureReferenceProvider->updateReferences($procedure, (string) $this->getLocale($request), 'admin');

        return $this->handleView($this->view($procedure, 201));
    }

    protected function create(Request $request): Procedure
    {
        return $this->repository->create((string) $this->getLocale($request));
    }

    public function deleteAction(int $id): Response
    {
        /** @var Procedure $procedure */
        $procedure = $this->entityManager->getRepository(Procedure::class)->find($id);
        $procedureTitle = $procedure->getTitle();
        if ($procedure) {
            $this->trashManager->store(Procedure::RESOURCE_KEY, $procedure);
            $this->entityManager->remove($procedure);
            $this->removeRoutesForEntity($procedure);
            $this->domainEventCollector->collect(
                new ProcedureRemovedEvent($id, $procedureTitle)
            );
        }
        $this->entityManager->flush();

        return $this->handleView($this->view(null, 204));
    }

    protected function removeRoutesForEntity(Procedure $entity): void
    {
        // remove route for all locales of the application because event entity is not localized
        foreach ($this->webspaceManager->getAllLocales() as $locale) {
            $routes = $this->routeRepository->findAllByEntity(
                Procedure::class,
                (string) $entity->getId(),
                $locale
            );

            foreach ($routes as $route) {
                $this->routeRepository->remove($route);
            }
        }
    }

    /**
     * @Rest\Post("/procedures/{id}")
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws EntityNotFoundException
     */
    public function postTriggerAction(int $id, Request $request): Response
    {
        $action = $this->getRequestParameter($request, 'action', true);
        $locale = $this->getRequestParameter($request, 'locale', true);

        try {
            switch ($action) {
                case 'enable':
                    $item = $this->entityManager->getRepository(Procedure::class)->find($id);
                    $item->setLocale($locale);
                    $item->setState(true);
                    $this->entityManager->persist($item);
                    $this->entityManager->flush();
                    break;
                case 'disable':
                    $item = $this->entityManager->getRepository(Procedure::class)->find($id);
                    $item->setLocale($locale);
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
        return Procedure::SECURITY_CONTEXT;
    }
}
