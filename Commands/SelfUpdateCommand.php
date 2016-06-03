<?php

namespace Terminus\Commands;

use Terminus\Commands\TerminusCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Utils;

/**
 * Terminus self update plugin
 *
 * @command update
 */
class SelfUpdateCommand extends TerminusCommand {

  /**
   * Terminus self update plugin
   *
   * @param array $options Options to construct the command object
   * @return SelfUpdateCommand
   */
  public function __construct(array $options = []) {
    parent::__construct($options);
  }

  /**
   * Terminus self update plugin
   */
  public function __invoke() {
    $installed = '';
    $home = getenv('HOME');
    $windows = Utils\isWindows();
    if ($windows) {
      $system = '';
      if (getenv('MSYSTEM') !== null) {
        $system = strtoupper(substr(getenv('MSYSTEM'), 0, 4));
      }
      if ($system != 'MING') {
        $home = getenv('HOMEPATH');
      }
      $home = str_replace('\\', '\\\\', $home);
      $slash = '\\\\';
    } else {
      $slash = '/';
    }
    exec('which terminus', $output);
    if (!empty($output)) {
      foreach ($output as $cmd) {
        // Check if installed via composer.
        $pattern = "|({$home}{$slash}(.composer{$slash})?)vendor{$slash}bin{$slash}terminus|U";
        if (preg_match($pattern, $cmd, $matches)) {
          if (isset($matches[1]) && ($composer_dir = $matches[1])) {
            $json = "{$composer_dir}composer.json";
            if (file_exists($json)) {
              exec("which composer", $result);
              if (!empty($result)) {
                foreach ($result as $composer) {
                  exec("cd \"{$composer_dir}\";$composer update pantheon-systems/terminus", $output);
                }
                $installed = 'composer';
              }
            }
          }
        }
        // Check if installed via 'git clone'.
        $path = explode('/', dirname($cmd));
        $bin = array_pop($path);
        if ($bin == 'bin') {
          $dir = implode('/', $path);
          $git_dir = "{$dir}{$slash}.git";
          if (is_dir($git_dir)) {
            exec("cd \"$dir\";git pull", $output);
            if (!empty($output)) {
              $installed = 'git';
            }
          }
        }
      }
    }
    if (!$installed) {
      $this->failure('Unable to update.');
    }
  }

}
