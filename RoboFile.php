<?php

// @codingStandardsIgnoreStart

/**
 * Base tasks for setting up a module to test within a full Drupal environment.
 *
 * This file expects to be called from the root of a Drupal site.
 *
 * @class RoboFile
 * @codeCoverageIgnore
 */
class RoboFile extends \Robo\Tasks
{

    /**
     * RoboFile constructor.
     */
    public function __construct()
    {
        // Treat this command like bash -e and exit as soon as there's a failure.
        $this->stopOnFail();
    }

    /**
     * Build folder.
     */
    const BUILD_FOLDER = 'web';

    /**
     * Adds coding standard dependencies.
     */
    public function addCodingStandardsDeps()
    {
        $config = json_decode(file_get_contents('composer.json'));
        $config->require->{"drupal/coder"} = "^8.2";
        file_put_contents('composer.json', json_encode($config));
    }

    /**
     * Adds Behat dependencies.
     */
    public function addBehatDeps()
    {
        $config = json_decode(file_get_contents('composer.json'));
        $config->require->{"behat/mink-selenium2-driver"} = "^1.3";
        $config->require->{"drupal/drupal-extension"} = "master-dev";
        // Pin version until https://github.com/Behat/MinkExtension/pull/311 gets fixed.
        $config->require->{"behat/mink-extension"} = "v2.2";
        $config->require->{"drush/drush"} = "~8.1";
        $config->require->{"guzzlehttp/guzzle"} = "^6.0@dev";
        file_put_contents('composer.json', json_encode($config));
    }

  /**
   * Installs composer dependencies.
   */
  public function installDependencies()
  {
    $this->taskComposerInstall()
      ->optimizeAutoloader()
      ->run();
  }

    /**
     * Updates composer dependencies.
     */
    public function updateDependencies()
    {
        // The git checkout includes a composer.lock, and running composer update
        // on it fails for the first time.
        $this->taskFilesystemStack()->remove('composer.lock')->run();
        $this->taskComposerUpdate()
          ->optimizeAutoloader()
          ->run();
    }

    /**
     * Install Drupal.
     *
     * @param string $admin_user
     *   (optional) The administrator's username.
     * @param string $admin_password
     *   (optional) The administrator's password.
     * @param string $site_name
     *   (optional) The Drupal site name.
     * @param string $site_uuid
     *   (optional) The Drupal site uuid.
     */
    public function setupDrupal(
      $admin_user = null,
      $admin_password = null,
      $site_name = null,
      $site_uuid = null
    ) {
        $task = $this->drush()
          ->args('site-install')
          ->option('yes');

        if ($admin_user) {
            $task->option('account-name', $admin_user, '=');
        }

        if ($admin_password) {
            $task->option('account-pass', $admin_password, '=');
        }

        if ($site_name) {
            $task->option('site-name', $site_name, '=');
        }

        // Sending email will fail, so we need to allow this to always pass.
        $this->stopOnFail(false);
        $task->run();
        $this->stopOnFail();

        if ($site_uuid) {
            $task = $this->drush()
              ->args(['config-set', 'system.site', 'uuid', $site_uuid])
              ->option('yes');
            $task->run();
        }
        $this->importConfig();

        $task = $this->drush()
          ->args('cr')
          ->run();
    }

    /**
     * Import config.
     */
    protected function importConfig()
    {
        $task = $this->drush()
          ->args('config-import')
          ->option('yes')
          ->run();
    }

    /**
     * Return drush with default arguments.
     *
     * @return \Robo\Task\Base\Exec
     *   A drush exec command.
     */
    protected function drush()
    {
        // Drush needs an absolute path to the docroot.
        $docroot = $this->getDocroot() . '/' . static::BUILD_FOLDER;
        return $this->taskExec('vendor/bin/drush')
          ->option('root', $docroot, '=');
    }

    /**
     * Get the absolute path to the docroot.
     *
     * @return string
     */
    protected function getDocroot()
    {
        $docroot = (getcwd());
        return $docroot;
    }

    /**
     * Run PHPUnit and simpletests for the module.
     *
     * @param string $module
     *   The module name.
     */
    public function test($module)
    {
        $this->phpUnit($module)
          ->run();
    }

    /**
     * Return a configured phpunit task.
     *
     * This will check for PHPUnit configuration first in the module directory.
     * If no configuration is found, it will fall back to Drupal's core
     * directory.
     *
     * @param string $module
     *   The module name.
     *
     * @return \Robo\Task\Testing\PHPUnit
     */
    private function phpUnit($module)
    {
        return $this->taskPhpUnit('vendor/bin/phpunit')
          ->option('verbose')
          ->option('debug')
          ->configFile('web/core')
          ->group($module);
    }

}
