<?php
/**
 * @author  Danil Syromolotov <pelmennoteam@gmail.com>
 * @company 0DaySolution
 */

namespace Solution\Composer\AssagistAssetPlugin;

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
        'npm' => 'http://assagist.0daysolution.ru/packages/npm/%package%'
    );
    /**
     * @var RemoteFilesystem
     */
    protected $rfs;

    public function __construct(RemoteFilesystem $rfs)
    {
        $this->rfs = $rfs;
        parent::__construct();
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
        if (!isset($this->_typeMap[$assetsRepositoryType]))
            return null;
        $url = str_replace('%package%', $name, $this->_typeMap[$assetsRepositoryType]);
        $json = (string)$this->rfs->getContents((string)parse_url($url, PHP_URL_HOST), $url);
        $data = JsonFile::parseJson($json, $url);
        $items = array();
        foreach ($data as $cacheItem) {
            $item = array(
                'version' => $cacheItem['version'],
                'dist' => array(
                    'url' => $cacheItem['dist'],
                    'type' => isset($cacheItem['tarballType']) ? $cacheItem['tarballType'] : 'zip',
                ),
            );
            if (isset($cacheItem['require']))
                $item['require'] = $cacheItem['require'];
            $items[] = $item;
        }
        return $items;
    }
}
