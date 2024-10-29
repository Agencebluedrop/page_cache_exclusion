<?php

namespace Drupal\page_cache_exclusion;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\page_cache_exclusion\StackMiddleware\PageCacheAlter;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class to add a new http_middleware to alter the page cache logic.
 */
class PageCacheExclusionServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('http_middleware.page_cache');
    $definition->setClass(PageCacheAlter::class)->addArgument(new Reference('config.factory'))
      ->addArgument(new Reference('path_alias.manager'))
      ->addArgument(new Reference('path.matcher'))
      ->addArgument(new Reference('path.current'));
  }

}
