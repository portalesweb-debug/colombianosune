<?php

namespace Drupal\menu_breadcrumb_custom;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\TitleResolverInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;

/**
 * Builds a breadcrumb based on menu hierarchy or URL alias fallback.
 */
class MenuBreadcrumbBuilder {

  protected ConfigFactoryInterface $configFactory;
  protected MenuLinkTreeInterface $menuLinkTree;
  protected AliasManagerInterface $aliasManager;
  protected CurrentPathStack $currentPath;
  protected CurrentRouteMatch $currentRouteMatch;
  protected RequestStack $requestStack;
  protected TitleResolverInterface $titleResolver;

  public function __construct(
    ConfigFactoryInterface $configFactory,
    MenuLinkTreeInterface $menuLinkTree,
    AliasManagerInterface $aliasManager,
    CurrentPathStack $currentPath,
    CurrentRouteMatch $currentRouteMatch,
    RequestStack $requestStack,
    TitleResolverInterface $titleResolver
  ) {
    $this->configFactory = $configFactory;
    $this->menuLinkTree = $menuLinkTree;
    $this->aliasManager = $aliasManager;
    $this->currentPath = $currentPath;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->requestStack = $requestStack;
    $this->titleResolver = $titleResolver;
  }

  /**
   * Builds the breadcrumb render array.
   */
  public function buildRenderArray(): array {
    $config = $this->configFactory->get('menu_breadcrumb_custom.settings');

    $menuName = $config->get('menu_name');
    $showHome = $config->get('show_home');
    $homeLabel = $config->get('home_label') ?: t('Home');
    $alwaysShowCurrent = $config->get('always_show_current_page');

    $items = [];

    // Home link.
    if ($showHome) {
      $items[] = [
        'title' => $homeLabel,
        'url' => Url::fromRoute('<front>'),
      ];
    }

    // Current path and alias.
    $internalPath = $this->currentPath->getPath();
    $alias = $this->aliasManager->getAliasByPath($internalPath);

    // Current node ID if available.
    $nodeId = NULL;
    if ($this->currentRouteMatch->getRouteName() === 'entity.node.canonical') {
      $node = $this->currentRouteMatch->getParameter('node');
      if ($node && $node->id()) {
        $nodeId = (int) $node->id();
      }
    }

    // Try to find trail in menu.
    $trail = $this->findTrailInMenu($menuName, $internalPath, $alias, $nodeId);

    if (!empty($trail)) {
      foreach ($trail as $delta => $link) {
        $isLast = ($delta === array_key_last($trail));

        $items[] = [
          'title' => $link['title'],
          'url' => $isLast ? NULL : $link['url'],
        ];
      }
    }
    elseif ($alwaysShowCurrent) {
      // Resolve title from route.
      $request = $this->requestStack->getCurrentRequest();
      $route = $this->currentRouteMatch->getRouteObject();
      $routeTitle = $route ? $this->titleResolver->getTitle($request, $route) : '';

      $title = is_string($routeTitle) ? trim($routeTitle) : '';

      // Fallback to alias if title is empty or generic.
      if ($title === '' || mb_strtolower($title) === 'página') {
        $aliasTitle = $this->aliasToTitle($alias);
        if ($aliasTitle !== '') {
          $title = $aliasTitle;
        }
      }

      if ($title !== '') {
        $items[] = [
          'title' => $title,
          'url' => NULL,
        ];
      }
    }

    // Avoid rendering breadcrumb with only Home.
    if (count($items) <= 1) {
      return [];
    }

    return [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['menu-breadcrumb-custom'],
      ],
      'breadcrumb' => [
        '#theme' => 'item_list',
        '#items' => array_map(function ($item) {
          if ($item['url']) {
            return [
              '#type' => 'link',
              '#title' => $item['title'],
              '#url' => $item['url'],
            ];
          }
          return [
            '#markup' => '<span aria-current="page">' . $item['title'] . '</span>',
          ];
        }, $items),
        '#attributes' => [
          'class' => ['breadcrumb'],
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path', 'user.permissions'],
        'tags' => ['config:system.menu.' . $menuName],
      ],
    ];
  }

  /**
   * Converts a URL alias into a human readable title.
   */
  protected function aliasToTitle(string $alias): string {
    $alias = trim($alias, '/');

    if ($alias === '') {
      return '';
    }

    $parts = explode('/', $alias);
    $last = end($parts);

    return ucfirst(str_replace('-', ' ', $last));
  }

  /**
   * Finds breadcrumb trail inside a menu.
   */
  protected function findTrailInMenu(string $menuName, string $internalPath, string $alias, ?int $nodeId): array {
    $parameters = $this->menuLinkTree->getCurrentRouteMenuTreeParameters($menuName);
    $parameters->onlyEnabledLinks();

    $tree = $this->menuLinkTree->load($menuName, $parameters);

    $trail = [];
    $this->walkTree($tree, $internalPath, $alias, $nodeId, [], $trail);

    return $trail;
  }

  /**
   * Walks menu tree recursively.
   */
  protected function walkTree(array $tree, string $internalPath, string $alias, ?int $nodeId, array $parents, array &$trail): void {
    foreach ($tree as $element) {
      $link = $element->link;
      $url = $link->getUrlObject();

      $current = array_merge($parents, [[
        'title' => $link->getTitle(),
        'url' => $url,
      ]]);

      if ($this->urlMatchesCurrent($url, $internalPath, $alias, $nodeId)) {
        $trail = $current;
        return;
      }

      if ($element->subtree) {
        $this->walkTree($element->subtree, $internalPath, $alias, $nodeId, $current, $trail);
      }
    }
  }

  /**
   * Checks if a menu link URL matches the current page.
   */
  protected function urlMatchesCurrent(Url $url, string $internalPath, string $alias, ?int $nodeId): bool {
    if ($url->isRouted() && $nodeId !== NULL) {
      if ($url->getRouteName() === 'entity.node.canonical') {
        $params = $url->getRouteParameters();
        if (isset($params['node']) && (int) $params['node'] === $nodeId) {
          return TRUE;
        }
      }
    }

    $urlString = '/' . ltrim($url->toString(), '/');

    return $urlString === $internalPath || $urlString === $alias;
  }

}
