<?php

namespace AppBundle\Service;

use FM\ElfinderBundle\Configuration\ElFinderConfigurationReader;
use FM\ElfinderBundle\Model\ElFinderConfigurationProviderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ConfigurationReader extends ElFinderConfigurationReader
{
    /**
     * @var array $options
     */
    protected $options = array();

    /**
     * @var array $parameters
     */
    protected $parameters;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param $parameters
     * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
     * @param ContainerInterface $container
     */
    public function __construct($parameters, RequestStack $requestStack, ContainerInterface $container)
    {
        parent::__construct($parameters, $requestStack, $container);
    }

    /**
     * @param $instance
     * @return array
     */
    public function getConfiguration($instance)
    {
        $request = $this->requestStack->getCurrentRequest();
        $efParameters = $this->parameters;
        $parameters = $efParameters['instances'][$instance];
        $options = array();
        $options['corsSupport'] = $parameters['cors_support'];
        $options['debug'] = $parameters['connector']['debug'];
        $options['bind'] =  $parameters['connector']['binds'];
        $options['plugins'] =  $parameters['connector']['plugins'];
        $options['roots'] = array();

        foreach ($parameters['connector']['roots'] as $parameter) {
            $path = $parameter['path'];
            $homeFolder = $this->container->get('security.token_storage')->getToken()->getUser()->getUsername();

//            var_dump($path.$homeFolder);
            $driver = $this->container->has($parameter['driver']) ? $this->container->get($parameter['driver']) : null;

            $driverOptions = array(
                'driver'            => $parameter['driver'],
                'service'           => $driver,
                'glideURL'          => $parameter['glide_url'],
                'glideKey'          => $parameter['glide_key'],
                'plugin'            => $parameter['plugins'],
                'path'              => $path .'/'. $homeFolder, //removed slash for Flysystem compatibility
                'startPath'         => $parameter['start_path'],
                'URL'               => $this->getURL($parameter, $request, $homeFolder, $path),
                'alias'             => $parameter['alias'],
                'mimeDetect'        => $parameter['mime_detect'],
                'mimefile'          => $parameter['mimefile'],
                'imgLib'            => $parameter['img_lib'],
                'tmbPath'           => $parameter['tmb_path'],
                'tmbPathMode'       => $parameter['tmb_path_mode'],
                'tmbUrl'            => $parameter['tmb_url'],
                'tmbSize'           => $parameter['tmb_size'],
                'tmbCrop'           => $parameter['tmb_crop'],
                'tmbBgColor'        => $parameter['tmb_bg_color'],
                'copyOverwrite'     => $parameter['copy_overwrite'],
                'copyJoin'          => $parameter['copy_join'],
                'copyFrom'          => $parameter['copy_from'],
                'copyTo'            => $parameter['copy_to'],
                'uploadOverwrite'   => $parameter['upload_overwrite'],
                'uploadAllow'       => $parameter['upload_allow'],
                'uploadDeny'        => $parameter['upload_deny'],
                'uploadMaxSize'     => $parameter['upload_max_size'],
                'defaults'          => $parameter['defaults'],
                'attributes'        => $parameter['attributes'],
                'acceptedName'      => $parameter['accepted_name'],
                'disabled'          => $parameter['disabled_commands'],
                'treeDeep'          => $parameter['tree_deep'],
                'checkSubfolders'   => $parameter['check_subfolders'],
                'separator'         => $parameter['separator'],
                'timeFormat'        => $parameter['time_format'],
                'archiveMimes'      => $parameter['archive_mimes'],
                'archivers'         => $parameter['archivers']
            );
            if(!$parameter['show_hidden']) {
                $driverOptions['accessControl'] = array($this, 'access');
            };

            if($parameter['driver'] == 'Flysystem') {
                $driverOptions['filesystem'] = $filesystem;
            }
            $options['roots'][] = array_merge($driverOptions, $this->configureDriver($parameter));
        }

        return $options;
    }

    /**
     * @param  array $parameter
     * @return array
     */
    private function configureDriver(array $parameter)
    {
        $settings = array();

        switch (strtolower($parameter['driver'])) {
            case "ftp":
                $settings['host'] = $parameter['ftp_settings']['host'];
                $settings['user'] = $parameter['ftp_settings']['user'];
                $settings['pass'] = $parameter['ftp_settings']['password'];
                $settings['path'] = $parameter['ftp_settings']['path'];
                break;
            case "ftpiis":
                $settings['host'] = $parameter['ftp_settings']['host'];
                $settings['user'] = $parameter['ftp_settings']['user'];
                $settings['pass'] = $parameter['ftp_settings']['password'];
                $settings['path'] = $parameter['ftp_settings']['path'];
                break;
            case "dropbox":
                $settings['consumerKey']       = $parameter['dropbox_settings']['consumer_key'];
                $settings['consumerSecret']    = $parameter['dropbox_settings']['consumer_secret'];
                $settings['accessToken']       = $parameter['dropbox_settings']['access_token'];
                $settings['accessTokenSecret'] = $parameter['dropbox_settings']['access_token_secret'];
                $settings['dropboxUid']        = $parameter['dropbox_settings']['dropbox_uid'];
                $settings['metaCachePath']     = $parameter['dropbox_settings']['meta_cache_path'];
                break;
            case "s3":
                $settings['accesskey'] = $parameter['s3_settings']['access_key'];
                $settings['secretkey'] = $parameter['s3_settings']['secret_key'];
                $settings['bucket']    = $parameter['s3_settings']['bucket'];
                $settings['tmpPath']   = $parameter['s3_settings']['tmp_path'];
                break;
            default:
                break;
        }

        return $settings;
    }

    /**
     * @param $parameter
     * @param $request
     * @param $homeFolder
     * @param $path
     * @return string
     */
    private function getURL($parameter, $request, $homeFolder, $path)
    {
        return isset($parameter['url']) && $parameter['url']
            ? strpos($parameter['url'], 'http') === 0
                ? $parameter['url']
                : sprintf('%s://%s%s/%s/%s', $request->getScheme(), $request->getHttpHost(), $request->getBasePath(), $parameter['url'], $homeFolder)
            : sprintf('%s://%s%s/%s/%s', $request->getScheme(), $request->getHttpHost(), $request->getBasePath(), $path, $homeFolder);
    }

    /**
     * Simple function to demonstrate how to control file access using "accessControl" callback.
     * This method will disable accessing files/folders starting from '.' (dot)
     *
     * @param  string    $attr attribute name (read|write|locked|hidden)
     * @param  string    $path file path relative to volume root directory started with directory separator
     * @param $data
     * @param $volume
     * @return bool|null
     */
    public function access($attr, $path, $data, $volume)
    {
        return strpos(basename($path), '.') === 0       // if file/folder begins with '.' (dot)
            ? !($attr == 'read' || $attr == 'write')    // set read+write to false, other (locked+hidden) set to true
            :  null;                                    // else elFinder decide it itself
    }

}