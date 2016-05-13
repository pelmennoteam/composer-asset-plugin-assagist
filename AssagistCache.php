<?php
/**
 * @author  Danil Syromolotov <pelmennoteam@gmail.com>
 * @company 0DaySolution
 */

namespace Solution\Composer\AssagistAssetPlugin;

use Composer\IO\IOInterface;
use Composer\Json\JsonFile;
use Composer\Util\RemoteFilesystem;
use Fxp\Composer\AssetPlugin\Repository\Cache\AbstractAssetsRepositoryCache;
use Fxp\Composer\AssetPlugin\Repository\Util;

class AssagistCache extends AbstractAssetsRepositoryCache
{
    /**
     * @var array
     */
    protected $_typeMap = array(
        'bower' => 'http://assagist.0daysolution.ru/packages/bower/%package%',
        'npm' => 'http://assagist.0daysolution.ru/packages/npm/%package%',
    );
    /**
     * @var RemoteFilesystem
     */
    protected $rfs;
    /**
     * @var IOInterface
     */
    protected $io;

    public function __construct(RemoteFilesystem $rfs, IOInterface $io)
    {
        $this->rfs = $rfs;
        $this->io = $io;
        parent::__construct();
    }

    private $currentStep = 0;

    /**
     * @param string $message
     */
    public function showProgress($message)
    {
        $steps = [
            '[|]',
            '[/]',
            '[-]',
            '[\]',
        ];
        $this->io->overwrite($message . ' ' . $steps[$this->currentStep], false);
        $this->currentStep++;
        if ($this->currentStep >= count($steps)) {
            $this->currentStep = 0;
        }
    }

    /**
     * @param string $packageName
     * @param string $assetsRepositoryType
     *
     * @return array|null
     */
    public function findItems($packageName, $assetsRepositoryType)
    {
        $name = Util::cleanPackageName($packageName);
        if (!isset($this->_typeMap[$assetsRepositoryType])) {
            return null;
        }
        $url = str_replace('%package%', $name, $this->_typeMap[$assetsRepositoryType]);
        $data = [];
        $message = "Try to resolve cache for " . $packageName;

        while (true) {
            if (!$this->io->isVerbose()) {
                $this->showProgress($message);
            }
            $json = (string)$this->rfs->getContents((string)parse_url($url, PHP_URL_HOST), $url, false);
            $refreshTime = $this->rfs->findHeaderValue($this->rfs->getLastHeaders(), 'refresh');
            if ($refreshTime !== null) {
                $refreshTime = intval($refreshTime);
            }
            if (empty($refreshTime) || $refreshTime <= 0) {
                $refreshTime = 2;
            }
            $data = JsonFile::parseJson($json, $url);
            if (!isset($data['status']) || $data['status'] == 0) {
                if (!$this->io->isVerbose()) {
                    $this->io->overwrite($message, true);
                }
                $this->io->write("Cache was successfully resolved for " . $packageName);
                break;
            }
            for ($i = 0; $i < $refreshTime; $i++) {
                $this->showProgress($message);
                sleep(1);
            }
        }

        $items = array();
        if (!empty($data['items'])) {
            foreach ($data['items'] as $cacheItem) {
                if (isset($cacheItem['dist']) && is_string($cacheItem['dist'])) {
                    $cacheItem['dist'] = array(
                        'url' => $cacheItem['dist'],
                        'type' => isset($cacheItem['tarballType']) ? $cacheItem['tarballType'] : 'zip',
                    );
                    unset($cacheItem['tarballType']);
                }
                if (isset($cacheItem['source'])) {
                    unset($cacheItem['source']);
                }
                $items[] = $cacheItem;
            }
        }

        return $items;
    }
}
