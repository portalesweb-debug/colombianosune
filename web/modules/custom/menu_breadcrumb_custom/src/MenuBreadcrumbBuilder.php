<?php

namespace Drupal\menu_breadcrumb_custom;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Core\Link;
use Drupal\Core\Menu\MenuLinkTreeElement;
use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;

/**
 * Builds breadcrumbs based on menu hierarchy or URL alias fallback.
 */
final class MenuBreadcrumbBuilder {

  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
    private readonly MenuLinkTreeInterface $menuLinkTree,
    private readonly CurrentPathStack $currentPath,
    private readonly AliasManagerInterface $aliasManager,
    private readonly CurrentRouteMatch $routeMatch,
    private readonly RequestStack $requestStack,
    private readonly TitleResolverInterface $titleResolver,
  ) {}

  /**
   * Main builder.
   */
  public function buildRenderArray(): array {
    $config = $this->configFactory->get('menu_breadcrumb_custom.settings');

    $menu_name = (string) ($config->get('menu_name') ?: 'main');
    $show_home = (bool) $config->get('show_home');
    $home_label = (string) ($config->get('home_label') ?: 'Inicio');
    $always_show_current = (bool) $config->get('always_show_current_page');

    $internal_path = $this->normalizePath($this->currentPath->getPath());
    $alias_path = $this->normalizePath(
      $this->aliasManager->getAliasByPath($internal_path)
    );

    $current_node_id = $this->getCurrentNodeId();

    // 1. Try menu-based breadcrumb first.
    $trail = $this->findTrailInMenu(
      $menu_name,
      $internal_path,
      $alias_path,
      $current_node_id
    );

    $items = [];

    if ($show_home) {
      $items[] = [
        'title' => $home_label,
        'url' => Url::fromRoute('<front>'),
        'link' => TRUE,
      ];
    }

    if (!empty($trail)) {
      foreach ($trail as $item) {
        $items[] = $item;
      }

      // Force last item to be current page (no link).
      $items[count($items) - 1]['link'] = FALSE;
      $items[count($items) - 1]['url'] = NULL;
    }
    elseif ($always_show_current) {
      // 2. Alias-based fallback (your requirement).
      $segments = explode('/', trim($alias_path, '/'));

      // Example: /noticia/slug → ["noticia", "slug"]
      if (!empty($segments[0])) {
        $first_segment_path = '/' . $segments[0];

        // If /noticia does NOT exist as a real route, show it as text only.
        if (!$this->routeExists($first_segment_path)) {
          $items[] = [
            'title' => $this->segmentToTitle($segments[0]),
            'url' => NULL,
            'link' => FALSE,
          ];
        }
      }

      // Current page title (never linked).
      $title = $this->getCurrentPageTitle();

      if ($title === 'Página') {
        $alias_title = $this->aliasToTitle($alias_path);
        if ($alias_title !== '') {
          $title = $alias_title;
        }
      }

      $items[] = [
        'title' => $title,
        'url' => NULL,
        'link' => FALSE,
      ];
    }

    // Avoid rendering breadcrumb with only "Inicio".
    if (count($items) < 2) {
      return [];
    }

    return $this->render($items, $menu_name);
  }

  /**
   * Menu traversal.
   */
  private function findTrailInMenu(
    string $menu_name,
    string $internal_path,
    string $alias_path,
    ?int $node_id
  ): array {
    $parameters = (new MenuTreeParameters())->onlyEnabledLinks();
    $tree = $this->menuLinkTree->load($menu_name, $parameters);

    $trail = [];
    $this->walkTree($tree, $internal_path, $alias_path, $node_id, [], $trail);

    return $trail;
  }

  private function walkTree(
    array $tree,
    string $internal_path,
    string $alias_path,
    ?int $node_id,
    array $parents,
    array &$trail
  ): void {
    foreach ($tree as $element) {
      if (!$element instanceof MenuLinkTreeElement) {
        continue;
      }

      $url = $element->link->getUrlObject();

      $current = array_merge($parents, [[
        'title' => $element->link->getTitle(),
        'url' => $url,
        'link' => TRUE,
      ]]);

      if ($this->urlMatchesCurrent($url, $internal_path, $alias_path, $node_id)) {
        $trail = $current;
        return;
      }

      if (!empty($element->subtree)) {
        $this->walkTree(
          $element->subtree,
          $internal_path,
          $alias_path,
          $node_id,
          $current,
          $trail
        );

        if (!empty($trail)) {
          return;
        }
      }
    }
  }

  private function urlMatchesCurrent(
    Url $url,
    string $internal_path,
    string $alias_path,
    ?int $node_id
  ): bool {
    if ($node_id !== NULL && $url->isRouted() && $url->getRouteName() === 'entity.node.canonical') {
      $params = $url->getRouteParameters();
      if (isset($params['node']) && (int) $params['node'] === $node_id) {
        return TRUE;
      }
    }

    $url_string = $this->normalizePath($url->toString());

    return $url_string === $internal_path || $url_string === $alias_path;
  }

  /**
   * Helpers
   */
  private function routeExists(string $path): bool {
    try {
      \Drupal::service('router')->match($path);
      return TRUE;
    }
    catch (ResourceNotFoundException | MethodNotAllowedException) {
      return FALSE;
    }
  }

  private function normalizePath(string $path): string {
    $path = trim($path);
    if ($path === '') {
      return '/';
    }
    if ($path[0] !== '/') {
      $path = '/' . $path;
    }
    return rtrim($path, '/') ?: '/';
  }

  private function aliasToTitle(string $alias): string {
    $alias = trim($alias, '/');
    if ($alias === '') {
      return '';
    }
    $parts = explode('/', $alias);
    $last = end($parts);
    return $this->segmentToTitle($last);
  }

  private function segmentToTitle(string $segment): string {
    $segment = str_replace(['-', '_'], ' ', $segment);
    return mb_strtoupper(mb_substr($segment, 0, 1)) . mb_substr($segment, 1);
  }

  private function getCurrentNodeId(): ?int {
    $node = $this->routeMatch->getParameter('node');
    if (is_object($node) && method_exists($node, 'id')) {
      return (int) $node->id();
    }
    if (is_numeric($node)) {
      return (int) $node;
    }
    return NULL;
  }

  private function getCurrentPageTitle(): string {
    $request = $this->requestStack->getCurrentRequest();
    if (!$request) {
      return 'Página';
    }
    $title = $this->titleResolver->getTitle(
      $request,
      $this->routeMatch->getRouteObject()
    );
    return (is_string($title) && $title !== '') ? $title : 'Página';
  }

  private function render(array $items, string $menu_name): array {
    $list = [];

    foreach ($items as $index => $item) {
      $is_last = ($index === count($items) - 1);

      if (!$is_last && !empty($item['link']) && $item['url'] instanceof Url) {
        $list[] = Link::fromTextAndUrl($item['title'], $item['url'])->toRenderable();
      }
      else {
        $list[] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => $item['title'],
          '#attributes' => $is_last ? ['aria-current' => 'page'] : [],
        ];
      }
    }

    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['menu-breadcrumb-custom']],
      'nav' => [
        '#type' => 'html_tag',
        '#tag' => 'nav',
        '#attributes' => [
          'class' => ['breadcrumb'],
          'aria-label' => 'Breadcrumb',
        ],
        'list' => [
          '#theme' => 'item_list',
          '#items' => $list,
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path', 'user.permissions'],
        'tags' => ['config:system.menu.' . $menu_name],
      ],
    ];
  }

}
