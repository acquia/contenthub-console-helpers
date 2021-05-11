<?php

namespace Acquia\Console\Helpers\Command;

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
  protected function groupingSitesFilePath(): string {
    $alias = $this->getAlias();
    $dir_parts = static::PLATFORM_LOCATION;
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
   * @param OutputInterface $output
   *   Output stream.
   *
   * @return array
   *   Array containing filtered list of sites.
   */
  protected function filterSitesByGroup(string $group_name, array $sites, OutputInterface $output): array {
    $group_file = $this->groupingSitesFilePath();
    try {
      $group_config = Yaml::parseFile($group_file);
    }
    catch (ParseException $exception) {
      $output->writeln('<error>Unable to parse the YAML ' . $exception->getMessage() . '</error>', );
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

    $platform_id = self::getPlatformId();
    if ($platform_id === 'Acquia Cloud Site Factory') {
      foreach ($sites as $key => $site) {
        if (!in_array($site['id'], $group_config[$group_name])) {
          unset($sites[$key]);
        }
      }
      return $sites;
    }

    return array_intersect_key($sites, array_flip($group_config[$group_name]));

  }

}
