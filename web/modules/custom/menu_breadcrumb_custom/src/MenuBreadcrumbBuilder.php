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
use Drupal\Core\Path\CurrentPathStack;
use Drupal\path_alias\AliasManagerInterface;

/**
 * Builds breadcrumbs from a configured menu (Drupal 11 compatible).
 */
final class MenuBreadcrumbBuilder {

  public function __construct(
  ConfigFactoryInterface $configFactory,
  MenuLinkTreeInterface $menuLinkTree,
  CurrentPathStack $currentPath,
  AliasManagerInterface $aliasManager,
  CurrentRouteMatch $routeMatch,
  RequestStack $requestStack,
  TitleResolverInterface $titleResolver,
) {
  $this->configFactory = $configFactory;
  $this->menuLinkTree = $menuLinkTree;
  $this->currentPath = $currentPath;
  $this->aliasManager = $aliasManager;
  $this->routeMatch = $routeMatch;
  $this->requestStack = $requestStack;
  $this->titleResolver = $titleResolver;
}
 

  public function buildRenderArray(): array {
    $config = $this->configFactory->get('menu_breadcrumb_custom.settings');
    $menu_name = (string) ($config->get('menu_name') ?: 'main');
    $show_home = (bool) $config->get('show_home');
    $home_label = (string) ($config->get('home_label') ?: 'Inicio');
    $always_show_current = (bool) $config->get('always_show_current_page');

    $current_internal = $this->normalizePath($this->currentPath->getPath()); // e.g. /node/123
    $current_alias = $this->normalizePath($this->aliasManager->getAliasByPath($current_internal)); // e.g. /tramites/...

    $current_node_id = $this->getCurrentNodeId();

    $trail = $this->findTrailInMenu($menu_name, $current_internal, $current_alias, $current_node_id);

    $items = [];
    if ($show_home) {
      $items[] = [
        'title' => $home_label,
        'url' => Url::fromRoute('<front>'),
        'link' => TRUE,
      ];
    }

    if (!empty($trail)) {
      foreach ($trail as $t) {
        $items[] = $t;
      }
      // Ensure last is current (no-link).
      $items[count($items) - 1]['link'] = FALSE;
      $items[count($items) - 1]['url'] = NULL;
    }
    elseif ($always_show_current) {
      $title = $this->getCurrentPageTitle();

      // ✅ Ajuste solicitado:
      // Si no existe en el menú y el TitleResolver devuelve un título genérico ("Página"),
      // usamos el alias actual como label del breadcrumb.
      if ($title === 'Página') {
        $alias_title = $this->aliasToTitle($current_alias);
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

    // If we only have "Inicio", don't render anything.
    if (count($items) < 2) {
      return [];
    }

    return $this->render($items, $menu_name);
  }

  private function findTrailInMenu(string $menu_name, string $current_internal, string $current_alias, ?int $current_node_id): array {
    $parameters = (new MenuTreeParameters())
      ->onlyEnabledLinks();

    $tree = $this->menuLinkTree->load($menu_name, $parameters);

    $trail = [];
    $this->walk($tree, $current_internal, $current_alias, $current_node_id, [], $trail);

    return $trail;
  }

  /**
   * Depth-first walk with parent stack.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   * @param array $parents
   * @param array $trail
   */
  private function walk(array $tree, string $current_internal, string $current_alias, ?int $current_node_id, array $parents, array &$trail): void {
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

      if ($this->urlMatchesCurrent($url, $current_internal, $current_alias, $current_node_id)) {
        $trail = $current;
        return;
      }

      if (!empty($element->subtree)) {
        $this->walk($element->subtree, $current_internal, $current_alias, $current_node_id, $current, $trail);
        if (!empty($trail)) {
          return;
        }
      }
    }
  }

  private function urlMatchesCurrent(Url $url, string $current_internal, string $current_alias, ?int $current_node_id): bool {
    // Best match: node canonical with same ID.
    if ($current_node_id !== NULL && $url->isRouted() && $url->getRouteName() === 'entity.node.canonical') {
      $params = $url->getRouteParameters();
      if (isset($params['node']) && (int) $params['node'] === $current_node_id) {
        return TRUE;
      }
    }

    // Compare normalized strings (internal path or alias).
    $url_string = $this->normalizePath($url->toString());

    return ($url_string === $current_internal) || ($url_string === $current_alias);
  }

  private function normalizePath(string $path): string {
    $path = trim($path);
    if ($path === '') {
      return '/';
    }
    // Ensure leading slash.
    if ($path[0] !== '/') {
      $path = '/' . $path;
    }
    // Remove trailing slash except root.
    $path = rtrim($path, '/');
    return $path === '' ? '/' : $path;
  }

  /**
   * Convierte el alias en un label legible.
   * Ej: /noticias-para-colombianos-cnu -> Noticias para colombianos cnu
   */
  private function aliasToTitle(string $alias): string {
    $alias = $this->normalizePath($alias);

    if ($alias === '/' || $alias === '') {
      return '';
    }

    $alias = trim($alias, '/');
    $parts = explode('/', $alias);
    $last = (string) end($parts);

    if ($last === '') {
      return '';
    }

    $last = str_replace(['-', '_'], ' ', $last);
    return mb_strtoupper(mb_substr($last, 0, 1)) . mb_substr($last, 1);
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
    $title = $this->titleResolver->getTitle($request, $this->routeMatch->getRouteObject());
    return (is_string($title) && $title !== '') ? $title : 'Página';
  }

  private function render(array $items, string $menu_name): array {
    $list_items = [];
    foreach ($items as $idx => $item) {
      $is_last = ($idx === count($items) - 1);

      if (!$is_last && !empty($item['link']) && $item['url'] instanceof Url) {
        $list_items[] = Link::fromTextAndUrl((string) $item['title'], $item['url'])->toRenderable();
      }
      else {
        $list_items[] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#value' => (string) $item['title'],
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
          '#items' => $list_items,
          '#attributes' => ['class' => ['breadcrumb__list']],
        ],
      ],
      '#cache' => [
        'contexts' => ['url.path', 'user.permissions'],
        'tags' => ['config:system.menu.' . $menu_name],
      ],
    ];
  }

}
