<?php

namespace Acquia\Console\Helpers\Command;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait PlatformGroupTrait.
 *
 * Perform sites filtering via group option.
 *
 * @package Acquia\Console\Helpers\Traits
 */
trait PlatformGroupTrait {

  /**
   * Fetch the location of the platform group file.
   *
   * @param string $alias
   *   Platform alias.
   *
   * @return string
   *   Platform group file path.
   */
  protected function getGroupFilePath(string $alias): string {
    $dir_parts = [
      '.commonconsole',
      'groups',
    ];
    array_unshift($dir_parts, getenv('HOME'));

    return implode(DIRECTORY_SEPARATOR, $dir_parts) . "/{$alias}.yml";
  }

  /**
   * Filter list of sites via group sites.
   *
   * @param string $group_name
   *   Platform grouping sites placeholder.
   * @param array $sites
   *   Platform sites.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output stream.
   * @param string $alias
   *   Platform alias.
   * @param string $platform_id
   *   Platform ID.
   *
   * @return array
   *   Array containing filtered list of sites.
   */
  protected function filterSitesByGroup(string $group_name, array $sites, OutputInterface $output, string $alias, string $platform_id): array {
    $group_file = $this->getGroupFilePath($alias);
    try {
      $group_config = Yaml::parseFile($group_file);
    }
    catch (ParseException $exception) {
      $output->writeln('<error>Unable to parse the YAML ' . $exception->getMessage() . '</error>');
      return [];
    }

    if (!isset($group_config[$group_name])) {
      $output->writeln('<error>Group name doesn\'t exist.</error>');
      return [];
    }

    if (empty($group_config[$group_name])) {
      $output->writeln('<warning>No sites available in the group. Exiting...</warning>');
      return [];
    }

    if ($platform_id === ACSFPlatform::PLATFORM_NAME) {
      foreach ($sites as $key => $site) {
        $id = $site['id'] ?? $site;
        if (!in_array($id, $group_config[$group_name], TRUE)) {
          unset($sites[$key]);
        }
      }
    }

    if ($platform_id === AcquiaCloudPlatform::PLATFORM_NAME
      || $platform_id === AcquiaCloudMultiSitePlatform::PLATFORM_NAME) {
      $sites = array_intersect_key($sites, array_flip($group_config[$group_name]));
    }

    if (empty($sites)) {
      $output->writeln('<warning>No valid sites available in the group. Exiting...</warning>');
      return [];
    }

    return $sites;
  }

  /**
   * Gets the uri of one of the sites.
   *
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   Platform object.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   Input stream.
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   *   Output stream.
   *
   * @return string
   *   URI to return.
   */
  protected function getUri(PlatformInterface $platform, InputInterface $input, OutputInterface $output): string {
    $sites = [];
    $platform_id = $platform->getPlatformId();
    $sites = $platform->getPlatformSites();
    $group_name = $input->hasOption('group') ? $input->getOption('group') : '';
    if (!empty($group_name)) {
      $alias = $platform->getAlias();
      $sites = $this->filterSitesByGroup($group_name, $sites, $output, $alias, $platform_id);
    }

    $site_info = reset($sites);
    return $site_info['uri'] ?? $site_info;
  }

}
