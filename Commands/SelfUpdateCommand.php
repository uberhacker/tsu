<?php

namespace Terminus\Commands;

use Terminus\Commands\TerminusCommand;
use Terminus\Exceptions\TerminusException;
use Terminus\Utils;

/**
 * Terminus self update plugin
 *
 * @command selfupdate
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
    $home = getenv('HOME');
    $windows = Utils\isWindows();
    if ($windows) {
      $home = str_replace('\\', '\\\\', $home);
      $homepath = str_replace('\\', '/', getenv('HOMEPATH'));
    }
    exec('which terminus', $terminus_array, $terminus_error);
    if (!$terminus_error && !empty($terminus_array)) {
      $terminus = array_pop($terminus_array);
      // Check if installed via composer.
      $patterns = array(
        "|({$home})/vendor/bin/terminus|U",
        "|({$home}/.composer)/vendor/bin/terminus|U",
      );
      if ($windows) {
        $patterns = array(
          "|({$homepath})/vendor/bin/terminus|U",
          "|({$homepath}/AppData/Roaming/Composer)/vendor/bin/terminus|U",
        );
      }
      $global = '';
      foreach ($patterns as $pattern) {
        if (preg_match($pattern, $terminus, $matches)) {
          if (isset($matches[1]) && ($composer_dir = $matches[1])) {
            $json_exists = false;
            if ($windows) {
              if ($global) {
                $json = "{$home}\\\\AppData\\\\Roaming\\\\Composer\\\\composer.json";
              } else {
                $json = "{$home}\\\\composer.json";
              }
              if (file_exists($json)) {
                $composer_dir = dirname($json);
                $json_exists = true;
              }
            } else {
              $json = "{$composer_dir}/composer.json";
              if (file_exists($json)) {
                $json_exists = true;
              }
            }
            if ($json_exists) {
              exec('which composer', $composer_array, $composer_error);
              if (!$composer_error && !empty($composer_array)) {
                $composer = array_pop($composer_array);
                if ($windows) {
                  $shell = getenv('SHELL');
                  $cmd = "cd \"$composer_dir\" && \"$shell\" -c '\"$composer\" $global remove pantheon-systems/terminus'";
                } else {
                  $cmd = "cd \"$composer_dir\" && \"$composer\" $global remove pantheon-systems/terminus";
                }
                exec($cmd, $remove_output, $remove_error);
                if (!$remove_error && !empty($remove_output)) {
                  foreach ($remove_output as $remove_line) {
                    $this->log()->notice($remove_line);
                  }
                }
                if ($windows) {
                  $cmd = "cd \"$composer_dir\" && \"$shell\" -c '\"$composer\" $global require pantheon-systems/terminus'";
                } else {
                  $cmd = "cd \"$composer_dir\" && \"$composer\" $global require pantheon-systems/terminus";
                }
                exec($cmd, $require_output, $require_error);
                if (!$require_error && !empty($require_output)) {
                  foreach ($require_output as $require_line) {
                    $this->log()->notice($require_line);
                  }
                }
                exit(0);
              } else {
                $this->log()->error('Unable to locate composer.');
              }
            } else {
              $this->log()->error('Unable to locate composer.json.');
            }
          }
        }
        $global = 'global';
      }
      // Check if installed via 'git clone'.
      $path = explode('/', dirname($terminus));
      $bin = array_pop($path);
      if ($bin == 'bin') {
        $dir = implode('/', $path);
        $git_head = "{$dir}/.git/HEAD";
        exec("ls $git_head", $git_repo, $error);
        if (!$error && !empty($git_repo)) {
          $git = '';
          exec('which git 2> /dev/null', $git_array, $git_error);
          if (!$git_error && !empty($git_array)) {
            $git = array_pop($git_array);
          } else {
            if ($windows) {
              // Attempt to locate git.exe.
              $git_locations = array(
                '\\Program Files\\Git\\bin\\git.exe',
                '\\Program Files (x86)\\Git\\bin\\git.exe',
              );
              foreach ($git_locations as $git_exe) {
                if (file_exists($git_exe)) {
                  $git = str_replace('\\', '/', $git_exe);
                  $git = str_replace('.exe', '', $git);
                  break;
                }
              }
              // Check if installed in /tmp directory.
              $paths = explode('/', $dir);
              if (isset($paths[1]) && ($paths[1] == 'tmp')) {
                array_shift($paths);
                array_shift($paths);
                $dir = $homepath . '/AppData/Local/Temp/' . implode('/', $paths);
              }
            }
          }
          if ($git) {
            exec("cd \"$dir\" && \"$git\" pull", $output, $error);
            if (!$error && !empty($output)) {
              foreach ($output as $line) {
                $this->log()->notice($line);
              }
            }
            exit(0);
          } else {
            $this->log()->error('Unable to locate git.');
          }
        }
      }
    } else {
      $this->log()->error('Unable to locate terminus.');
    }
    $this->failure('Unable to update terminus.');
  }

}
