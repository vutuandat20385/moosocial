<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('CakeEventListener', 'Event');

class MinifyListener implements CakeEventListener
{

    public function implementedEvents()
    {
        return array(
            'StorageHelper.minify.getUrl.local' => 'storage_geturl_local',
            'StorageHelper.minify.getUrl.amazon' => 'storage_geturl_amazon',
            'StorageAmazon.minify.getFilePath' => 'storage_amazon_get_file_path',
            'StorageAmazon.minify.putObject.success' => 'storage_amazon_putObject_success',
        );
    }

    public function storage_geturl_local($e)
    {
        $v = $e->subject();
        $request = Router::getRequest();

        $thumb = $e->data['thumb'];

        if ($e->data['thumb']) {
            $url = FULL_BASE_LOCAL_URL . $request->base . '/min-css/' . $thumb;
        }
        $e->result['url'] = $url;
    }

    public function storage_geturl_amazon($e)
    {


        if(Configure::read('Storage.storage_amazon_use_css_js_path') != "1"){
            $v = $e->subject();
            $e->result['url'] = $v->getAwsURL($e->data['oid'], "minify", $e->data['prefix'], $e->data['thumb']);
        }

    }

    public function storage_amazon_get_file_path($e)
    {

        $objectId = $e->data['oid'];
        $name = $e->data['name'];
        $thumb = $e->data['thumb'];

        // Get minify css content
        $minifyModel = MooCore::getInstance()->getModel("Minify.MinifyUrl");
        $minify = $minifyModel->getMinify($thumb);
        if ($minify) {
            $paths = json_decode($minify['MinifyUrl']['url'], true);
            $_GET['f'] = implode(',', $paths);
        }

        $request = Router::getRequest();
        if (!empty($request->base)) {
            $baseUrl = substr($request->base, 1) . '/';
            $baseLen = strlen($baseUrl);
            $files = explode(',', $_GET['f']);
            foreach ($files as &$file) {
                if (!strncmp($file, $baseUrl, $baseLen)) {
                    $file = substr($file, $baseLen);
                }
            }
            $_GET['f'] = implode(',', $files);
        }
        define('MINIFY_MIN_DIR', APP_PATH . "Plugin" . DS . "Minify" . DS . "Vendor" . DS . "minify");
        // load config , for more information , check   MINIFY_MIN_DIR . '/config.php'
        $min_enableBuilder = false;
        $min_builderPassword = 'admin';
        $min_errorLogger = false;
        $min_allowDebugFlag = false;
        $min_cachePath = TMP . 'cache' . DS . 'minify';
        App::uses('Asset', 'Minify.Utility/Routing');
        if (!empty($_GET['f'])) {
            list($min_documentRoot, $_GET['f']) = Asset::getAssetFile($_GET['f']);
        }
        $min_cacheFileLocking = true;
        $min_serveOptions['bubbleCssImports'] = false;
        $min_serveOptions['maxAge'] = 1800;
        $min_serveOptions['minApp']['groupsOnly'] = false;
        $min_symlinks = (isset($_GET['symlinks']) ? $_GET['symlinks'] : array());
        $min_uploaderHoursBehind = 0;
        $min_libPath = MINIFY_MIN_DIR . '/lib';

        if (isset($_GET['test'])) {
            include MINIFY_MIN_DIR . '/config-test.php';
        }

        require "$min_libPath/Minify/Loader.php";
        Minify_Loader::register();

        Minify::$uploaderHoursBehind = $min_uploaderHoursBehind;
        Minify::setCache(
            isset($min_cachePath) ? $min_cachePath : ''
            , $min_cacheFileLocking
        );

        if ($min_documentRoot) {
            $_SERVER['DOCUMENT_ROOT'] = $min_documentRoot;
            Minify::$isDocRootSet = true;
        }

        $min_serveOptions['minifierOptions']['text/css']['symlinks'] = $min_symlinks;
// auto-add targets to allowDirs
        foreach ($min_symlinks as $uri => $target) {
            $min_serveOptions['minApp']['allowDirs'][] = $target;
        }

        if ($min_allowDebugFlag) {
            $min_serveOptions['debug'] = Minify_DebugDetector::shouldDebugRequest($_COOKIE, $_GET, $_SERVER['REQUEST_URI']);
        }

        if ($min_errorLogger) {
            if (true === $min_errorLogger) {
                $min_errorLogger = FirePHP::getInstance(true);
            }
            Minify_Logger::setLogger($min_errorLogger);
        }

// check for URI versioning
        if (preg_match('/&\\d/', $_SERVER['QUERY_STRING'])) {
            $min_serveOptions['maxAge'] = 31536000;
        }
        if (isset($_GET['g'])) {
            // well need groups config
            $min_serveOptions['minApp']['groups'] = (require MINIFY_MIN_DIR . '/groupsConfig.php');
        }
        $min_serveOptions["quiet"] = true;
        $min_serveOptions["encodeMethod"] = '';

        $minifyFile = false;
        if (isset($_GET['f']) || isset($_GET['g'])) {
            // serve!

            if (!isset($min_serveController)) {
                $min_serveController = new Minify_Controller_MinApp();
            }
            $minifyFile = Minify::serve($min_serveController, $min_serveOptions);

        }

        if(isset($minifyFile['content'])){
            $path = WWW_ROOT . "uploads" . DS . "minify" . DS . $thumb . ".css";
            $file = new File($path, true);
            $file->write($minifyFile['content']);
            $file->close();
        }

        $e->result['path'] = $path;
    }
    public function storage_amazon_putObject_success($e){
        if (Configure::read('Storage.storage_current_type') == 'amazon' ) {
            $file = new File($e->data['path']);
            if($file->exists()){
                $file->delete();
            }
            $file->close();
        }

    }
}
