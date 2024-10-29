<?php

namespace Drupal\page_cache_exclusion\StackMiddleware;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\PageCache\RequestPolicyInterface;
use Drupal\Core\PageCache\ResponsePolicyInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\page_cache\StackMiddleware\PageCache;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Executes the page caching before the main kernel takes over the request.
 */
class PageCacheAlter extends PageCache {

  /**
   * A config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * The alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  public function __construct(HttpKernelInterface $http_kernel, CacheBackendInterface $cache, RequestPolicyInterface $request_policy, ResponsePolicyInterface $response_policy, ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathMatcherInterface $path_matcher, CurrentPathStack $current_path) {
    parent::__construct($http_kernel, $cache, $request_policy, $response_policy);
    $this->config = $config_factory->get('page_cache_exclusion.settings');
    $this->aliasManager = $alias_manager;
    $this->pathMatcher = $path_matcher;
    $this->currentPath = $current_path;
  }

  /**
   * {@inheritdoc}
   */
  protected function set(Request $request, Response $response, $expire, array $tags) {
    $disableErrorResponse = $this->config->get('client_error_caching');
    if ($disableErrorResponse && $response->isClientError()) {
      return;
    }

    // Compare the lowercase path alias (if any) and internal path.
    $path = $this->currentPath->getPath($request);
    // Do not trim a trailing slash if that is the complete path.
    $path = $path === '/' ? $path : rtrim($path, '/');
    $path_alias = mb_strtolower($this->aliasManager->getAliasByPath($path));

    $pageExclusionList = $this->config->get('page_list');
    $matchedPageExclusion = $this->pathMatcher->matchPath($path_alias, $pageExclusionList) || (($path != $path_alias) && $this->pathMatcher->matchPath($path, $pageExclusionList));
    if ($pageExclusionList && $matchedPageExclusion) {
      return;
    }

    $pageQueryParametersList = $this->config->get('page_query_parameters_list');
    $queryParameters = $request->query->all();
    $matched = $this->pathMatcher->matchPath($path_alias, $pageQueryParametersList) || (($path != $path_alias) && $this->pathMatcher->matchPath($path, $pageQueryParametersList));
    if ($matched && $queryParameters) {
      return;
    }

    $cid = $this->getCacheId($request);
    $this->cache->set($cid, $response, $expire, $tags);
  }

}
