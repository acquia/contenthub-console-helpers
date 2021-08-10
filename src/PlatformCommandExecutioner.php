<?php

namespace Acquia\Console\Helpers;

use Acquia\Console\Helpers\Command\CommandOptionsDefinitionTrait;
use EclipseGc\CommonConsole\PlatformInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class PlatformCommandExecutioner.
 *
 * @package Acquia\Console\Helpers\Client
 */
class PlatformCommandExecutioner {

  use CommandOptionsDefinitionTrait;

  /**
   * Symfony console application.
   *
   * @var \Symfony\Component\Console\Application
   */
  protected $application;

  /**
   * The input object.
   *
   * @var \Symfony\Component\Console\Input\InputInterface
   */
  protected $input;

  /**
   * PlatformCommandExecutioner constructor.
   *
   * @param \Symfony\Component\Console\Application $application
   *   Current application.
   * @param \Symfony\Component\Console\Input\InputInterface $input
   *   The input object.
   */
  public function __construct(Application $application, InputInterface $input) {
    $this->application = $application;
    $this->input = $input;
  }

  /**
   * Helper function to return current application.
   *
   * @return \Symfony\Component\Console\Application
   *   Current application.
   */
  protected function getApplication() : Application {
    return $this->application;
  }

  /**
   * Executes a command on the given platform and returns the output.
   *
   * @param string $cmd_name
   *   The name of the command to execute.
   * @param \EclipseGc\CommonConsole\PlatformInterface|null $platform
   *   The platform where command needs to be executed.
   * @param array $input
   *   The input for the command.
   *
   * @return object
   *   The output of the command execution.
   *
   * @throws \Symfony\Component\Console\Exception\ExceptionInterface
   */
  public function runWithMemoryOutput(string $cmd_name, PlatformInterface $platform = NULL, array $input = []): object {
    /** @var \Symfony\Component\Console\Command\Command $command */
    $command = $this->getApplication()->find($cmd_name);
    $remote_output = new StreamOutput(fopen('php://memory', 'r+', FALSE));
    // @todo LCH-4538 added this solution for fix the highlighting
    // It fixes highlighting but PlatformCmdOutputFormatterTrait functions will work incorrectly
    // $remote_output->setDecorated(TRUE);
    $input['--bare'] = NULL;
    if ($group = $this->input->getOption('group')) {
      $input['--group'] = $group;
    }
    $bind_input = new ArrayInput($input);
    $bind_input->bind($this->getDefinitions($command));
    if ($platform) {
      $return_code = $platform->execute($command, $bind_input, $remote_output);
    }
    // Current execution already on platform.
    else {
      $return_code = $command->run($bind_input, $remote_output);
    }
    rewind($remote_output->getStream());

    return $this->formatReturnObject($return_code, $remote_output);
  }

  /**
   * Executes a command with given platform locally and returns the output.
   *
   * @param string $cmd_name
   *   The name of the command to execute.
   * @param \EclipseGc\CommonConsole\PlatformInterface $platform
   *   The name of the key of where the desired platform resides.
   * @param array $input
   *   The input for the command.
   *
   * @return object
   *   The output of the command execution.
   *
   * @throws \Exception
   * @throws \Symfony\Component\Console\Exception\ExceptionInterface
   */
  public function runLocallyWithMemoryOutput(string $cmd_name, PlatformInterface $platform, array $input = []): object {
    /** @var \Symfony\Component\Console\Command\Command $command */
    $command = $this->getApplication()->find($cmd_name);
    $remote_output = new StreamOutput(fopen('php://memory', 'r+', FALSE));
    // @todo LCH-4538 added this solution for fix the highlighting
    // It fixes highlighting but PlatformCmdOutputFormatterTrait functions will work incorrectly
    // $remote_output->setDecorated(TRUE);
    if ($group = $this->input->getOption('group')) {
      $input['--group'] = $group;
    }
    $bind_input = new ArrayInput($input);
    $bind_input->bind($this->getDefinitions($command));
    $command->addPlatform($platform->getAlias(), $platform);
    $return_code = $command->run($bind_input, $remote_output);
    rewind($remote_output->getStream());

    return $this->formatReturnObject($return_code, $remote_output);
  }

  /**
   * Format command execution output.
   *
   * @param int $return_code
   *   Exit code.
   * @param \Symfony\Component\Console\Output\StreamOutput $remote_output
   *   StreamOutput after command run.
   *
   * @return object
   *   Object containing data.
   */
  protected function formatReturnObject(int $return_code, StreamOutput $remote_output): object {
    return new class($return_code, stream_get_contents($remote_output->getStream()) ?? '') {

      /**
       * Constructor.
       */
      public function __construct($returnCode, string $result) {
        $this->returnCode = $returnCode ?? -1;
        $this->result = $result;
      }

      /**
       * Returns the response return code.
       */
      public function getReturnCode() {
        return $this->returnCode;
      }

      /**
       * Returns the response body.
       */
      public function __toString() {
        return $this->result;
      }

    };
  }

}
