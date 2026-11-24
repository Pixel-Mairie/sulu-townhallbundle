<?php

declare(strict_types=1);

namespace Pixel\TownHallBundle\Controller\Website;

use Pixel\TownHallBundle\Entity\Decree;
use Pixel\TownHallBundle\Repository\DecreeRepository;
use Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface;
use Sulu\Bundle\CategoryBundle\Entity\CategoryInterface;
use Sulu\Bundle\CategoryBundle\Exception\CategoryKeyNotFoundException;
use Sulu\Bundle\WebsiteBundle\Controller\DefaultController;
use Sulu\Component\Content\Compat\StructureInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Contrôleur de page listant les arrêtés, groupés par année puis par type, filtrables par type et par année, et paginés.
 * À référencer dans la balise <controller> du template de page. Le nombre d'arrêtés par page peut être surchargé par
 * une propriété « limit » du template de page.
 */
class DecreeController extends DefaultController
{
    public const CATEGORY_ROOT_KEY = "decrees";

    public const ALL_YEARS = "all";

    public const DEFAULT_LIMIT = 20;

    private DecreeRepository $decreeRepository;

    private CategoryManagerInterface $categoryManager;

    private RequestStack $requestStack;

    public function __construct(
        #[Autowire(service: 'townhall.decree_repository')]
        DecreeRepository $decreeRepository,
        CategoryManagerInterface $categoryManager,
        RequestStack $requestStack
    ) {
        $this->decreeRepository = $decreeRepository;
        $this->categoryManager = $categoryManager;
        $this->requestStack = $requestStack;
    }

    protected function getAttributes($attributes, ?StructureInterface $structure = null, $preview = false)
    {
        $attributes = parent::getAttributes($attributes, $structure, $preview);

        $request = $this->requestStack->getCurrentRequest();
        $locale = $request ? $request->getLocale() : "fr";
        $currentYear = (int) date("Y");

        $types = $this->getTypes($locale);
        $years = $this->getYears($currentYear);

        // Type : tous par défaut, ignoré s'il ne fait pas partie des types d'arrêtés
        $type = $request ? (int) $request->query->get("type") : 0;
        if (! isset($types[$type])) {
            $type = null;
        }

        // Année : l'année courante par défaut, « all » pour toutes les années
        $year = $request ? (string) $request->query->get("year", (string) $currentYear) : (string) $currentYear;
        if (self::ALL_YEARS !== $year) {
            $year = in_array((int) $year, $years, true) ? (int) $year : $currentYear;
        }

        if (self::ALL_YEARS === $year) {
            $decrees = $this->decreeRepository->findActiveByPeriod(null, null, $type);
        } else {
            $decrees = $this->decreeRepository->findActiveByPeriod(
                new \DateTimeImmutable($year . "-01-01 00:00:00"),
                new \DateTimeImmutable($year . "-12-31 23:59:59"),
                $type
            );
        }
        $entries = $this->sortByYearAndType($decrees, $year, $types);

        // Pagination sur la liste triée : un groupe année/type peut se poursuivre sur la page suivante
        $limit = (int) ($attributes['content']['limit'] ?? 0) ?: self::DEFAULT_LIMIT;
        $pages = max(1, (int) ceil(count($entries) / $limit));
        $page = $request ? max(1, (int) $request->query->get("page", "1")) : 1;
        $page = min($page, $pages);

        $attributes['decreeTypes'] = $types;
        $attributes['decreeYears'] = $years;
        $attributes['decreeFilters'] = [
            'type' => $type,
            'year' => $year,
        ];
        $attributes['decreesByYear'] = $this->groupByYearAndType(array_slice($entries, ($page - 1) * $limit, $limit), $types);
        $attributes['decreePagination'] = [
            'page' => $page,
            'pages' => $pages,
            'total' => count($entries),
        ];

        return $attributes;
    }

    /**
     * Types d'arrêtés (enfants de la catégorie racine « decrees »), indexés par id et triés par nom.
     *
     * @return array<int, string>
     */
    private function getTypes(string $locale): array
    {
        try {
            $categories = $this->categoryManager->findChildrenByParentKey(self::CATEGORY_ROOT_KEY);
        } catch (CategoryKeyNotFoundException $e) {
            return [];
        }

        $types = [];
        foreach ($categories as $category) {
            $types[$category->getId()] = $this->getCategoryName($category, $locale);
        }
        asort($types, SORT_NATURAL | SORT_FLAG_CASE);

        return $types;
    }

    private function getCategoryName(CategoryInterface $category, string $locale): string
    {
        $translation = $category->findTranslationByLocale($locale)
            ?: $category->findTranslationByLocale($category->getDefaultLocale());

        return $translation ? (string) $translation->getTranslation() : (string) $category->getKey();
    }

    /**
     * Années proposées dans le filtre, de la plus récente à la plus ancienne. L'année courante est toujours
     * proposée ; les dates de fin lointaines n'étendent pas la liste.
     *
     * @return int[]
     */
    private function getYears(int $currentYear): array
    {
        $bounds = $this->decreeRepository->findStartYearBounds();
        $min = $bounds ? min($bounds['min'], $currentYear) : $currentYear;
        $max = $bounds ? max($bounds['max'], $currentYear) : $currentYear;

        return range($max, $min);
    }

    /**
     * Rattache chaque arrêté à une année puis trie par année décroissante, par type (ordre de $types) et par date
     * de début décroissante. Sur une année filtrée, l'arrêté est rattaché à cette année (il y est en vigueur) ;
     * sur toutes les années, il n'est rattaché qu'à son année de début pour ne pas être répété.
     *
     * @param Decree[] $decrees triés par date de début décroissante
     * @param array<int, string> $types
     *
     * @return array<array{year: int, typeId: int, decree: Decree}>
     */
    private function sortByYearAndType(array $decrees, int|string $year, array $types): array
    {
        $typePositions = array_flip(array_keys($types));

        $entries = [];
        foreach ($decrees as $decree) {
            $typeId = $decree->getCategory()->getId();
            if (! isset($typePositions[$typeId])) {
                continue;
            }
            $entries[] = [
                'year' => self::ALL_YEARS === $year ? (int) $decree->getStartDate()->format("Y") : (int) $year,
                'typeId' => $typeId,
                'decree' => $decree,
            ];
        }

        // usort est stable : l'ordre par date de début issu de la requête est conservé dans chaque groupe
        usort($entries, fn (array $a, array $b): int => [$b['year'], $typePositions[$a['typeId']]] <=> [$a['year'], $typePositions[$b['typeId']]]);

        return $entries;
    }

    /**
     * @param array<array{year: int, typeId: int, decree: Decree}> $entries
     * @param array<int, string> $types
     *
     * @return array<int, array<int, array{name: string, decrees: Decree[]}>>
     */
    private function groupByYearAndType(array $entries, array $types): array
    {
        $grouped = [];
        foreach ($entries as $entry) {
            $grouped[$entry['year']][$entry['typeId']]['name'] = $types[$entry['typeId']];
            $grouped[$entry['year']][$entry['typeId']]['decrees'][] = $entry['decree'];
        }

        return $grouped;
    }
}
