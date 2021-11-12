<?php

namespace Acquia\Console\Helpers\Command;

use Acquia\Console\Acsf\Platform\ACSFPlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudMultiSitePlatform;
use Acquia\Console\Cloud\Platform\AcquiaCloudPlatform;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Trait PlatformGroupTrait.
 *
 * @package Acquia\Console\Helpers\Traits
 */
trait PlatformGroupTrait {

  /**
   * Fetch the location of the platform group file.
   *
   * @return string
   *   Platform group file path.
   */
  protected function groupingSitesFilePath($alias): string {
    $dir_parts = static::GROUP_CONFIG_LOCATION;
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
   *
   * @return array
   *   Array containing filtered list of sites.
   */
  protected function filterSitesByGroup(string $group_name, array $sites, OutputInterface $output, string $alias, string $platform_id): array {
    $group_file = $this->groupingSitesFilePath($alias);
    try {
      $group_config = Yaml::parseFile($group_file);
    }
    catch (ParseException $exception) {
      $output->writeln('<error>Unable to parse the YAML ' . $exception->getMessage() . '</error>');
      return [];
    }

    if (!isset($group_config[$group_name])) {
      $output->writeln('<error>Group name doesn\'t exists.</error>');
      return [];
    }

    if (empty($group_config[$group_name])) {
      $output->writeln('<warning>No sites available in the groups. Exiting...</warning>');
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
      $output->writeln('<warning>No valid sites available in the groups. Exiting...</warning>');
      return [];
    }

    return $sites;
  }

}
