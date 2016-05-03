<?php
/**
 * @author  Danil Syromolotov <pelmennoteam@gmail.com>
 * @company 0DaySolution
 */

namespace Solution\Composer\AssagistAssetPlugin;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Util\RemoteFilesystem;

/**
 * Class Plugin
 *
 * @package Solution\Composer\AssagistAssetPlugin
 */
class Plugin implements PluginInterface
{
    public function activate(Composer $composer, IOInterface $io)
    {
        $rfs = new RemoteFilesystem($io);
        AssagistCache::newInstance(array(
            'rfs' => $rfs,
        ));
    }
}
