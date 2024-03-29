<?php

/**
 * Upload behavior
 *
 * Enables users to easily add file uploading and necessary validation rules
 *
 * PHP versions 4 and 5
 *
 * Copyright 2010, Jose Diaz-Gonzalez
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright 2010, Jose Diaz-Gonzalez
 * @package       upload
 * @subpackage    upload.models.behaviors
 * @link          http://github.com/josegonzalez/cakephp-upload
 * @license       MIT License (http://www.opensource.org/licenses/mit-license.php)
 */

/*
 * Customized by mooSocial
 */

App::uses('Folder', 'Utility');
App::uses('UploadException', 'Upload.Lib/Error/Exception');
App::uses('HttpSocket', 'Network/Http');

class UploadBehavior extends ModelBehavior {

    public $defaults = array(
        'rootDir' => null,
        'pathMethod' => 'primaryKey',
        'path' => '{ROOT}webroot{DS}files{DS}{model}{DS}{field}{DS}',
        'fields' => array('dir' => 'dir', 'type' => 'type', 'size' => 'size'),
        'mimetypes' => array(),
        'extensions' => array(),
        'maxSize' => 2097152,
        'minSize' => 8,
        'maxHeight' => 0,
        'minHeight' => 0,
        'maxWidth' => 0,
        'minWidth' => 0,
        'thumbnails' => true,
        'thumbnailMethod' => 'php',
        'thumbnailName' => null,
        'thumbnailPath' => null,
        'thumbnailPrefixStyle' => true,
        'thumbnailQuality' => 100,
        'thumbnailSizes' => array(),
        'thumbnailType' => false,
        'deleteOnUpdate' => true,
        'mediaThumbnailType' => 'png',
        'saveDir' => true,
        'deleteFolderOnDelete' => true,
        'keepFilesOnDelete' => false,
        'mode' => 0755,
        'handleUploadedFileCallback' => null,
        'nameCallback' => null,
    );
    protected $_imageMimetypes = array(
        'image/bmp',
        'image/gif',
        'image/jpeg',
        'image/pjpeg',
        'image/png',
        'image/vnd.microsoft.icon',
        'image/x-icon',
        'image/x-png',
    );
    protected $_mediaMimetypes = array(
        'application/pdf',
        'application/postscript',
    );
    public $thumbLargeThenPic = false;
    private $_hImg;
    protected $_pathMethods = array('flat', 'primaryKey', 'random', 'randomCombined');
    protected $_resizeMethods = array('php');
    private $__filesToRemove = array();
    private $__foldersToRemove = array();
    protected $_removingOnly = array();

    /**
     * Holds an ARRAY of meta information about an image
     *
     * @var arrat
     */
    protected $_aInfo = array();

    /**
     * Supported file types
     *
     * @var array
     */
    protected $_aTypes = array('', 'gif', 'jpg', 'png');

    /**
     * Runtime configuration for this behavior
     *
     * @var array
     * */
    public $runtime;

    /**
     * Initiate Upload behavior
     *
     * @param object $model instance of model
     * @param array $config array of configuration settings.
     * @return void
     */
    public function setup(Model $model, $config = array()) {
        if (isset($this->settings[$model->alias])) {
            return;
        }

        $this->settings[$model->alias] = array();

        foreach ($config as $field => $options) {
            $this->_setupField($model, $field, $options);
        }
    }

    /**
     * Setup a particular upload field
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param array $options array of configuration settings for a field
     * @return void
     */
    protected function _setupField(Model $model, $field, $options) {
        if (is_int($field)) {
            $field = $options;
            $options = array();
        }

        $this->defaults['rootDir'] = ROOT . DS . APP_DIR . DS;
        if (!isset($this->settings[$model->alias][$field])) {
            $options = array_merge($this->defaults, (array) $options);

            $options['fields'] += $this->defaults['fields'];
            $options['rootDir'] = $this->_getRootDir($options['rootDir']);
            $options['thumbnailName'] = $this->_getThumbnailName($options['thumbnailName'], $options['thumbnailPrefixStyle']);

            $options['thumbnailPath'] = Folder::slashTerm($this->_path($model, $field, array(
                                'isThumbnail' => true,
                                'path' => ($options['thumbnailPath'] === null ? $options['path'] : $options['thumbnailPath']),
                                'rootDir' => $options['rootDir']
            )));

            $options['path'] = Folder::slashTerm($this->_path($model, $field, array(
                                'isThumbnail' => false,
                                'path' => $options['path'],
                                'rootDir' => $options['rootDir']
            )));

            if (!in_array($options['thumbnailMethod'], $this->_resizeMethods)) {
                $options['thumbnailMethod'] = 'imagick';
            }

            if (!in_array($options['pathMethod'], $this->_pathMethods)) {
                $options['pathMethod'] = 'primaryKey';
            }

            $options['pathMethod'] = '_getPath' . Inflector::camelize($options['pathMethod']);
            $options['thumbnailMethod'] = '_resize' . Inflector::camelize($options['thumbnailMethod']);
            $this->settings[$model->alias][$field] = $options;
        }
    }


    protected function _load($sPath) {

        $this->sPath = $sPath;

        if ($this->_aInfo = @getImageSize($sPath)) {
            if (!isset($this->_aTypes[$this->_aInfo[2]])) {
                return false;
            }

            $this->nW = $this->_aInfo[0];
            $this->nH = $this->_aInfo[1];
            $this->sType = $this->_aTypes[$this->_aInfo[2]];
            $this->sMimeType = $this->_aInfo['mime'];

            return true;
        }

        return false;
    }


    protected function _calcSize($nMaxW, $nMaxH) {
        $w = $nMaxW;
        $h = $nMaxH;

        if ($this->nW > $nMaxW) {
            $w = $nMaxW;
            $h = floor($this->nH * $nMaxW / $this->nW);
            if ($h > $nMaxH) {
                $h = $nMaxH;
                $w = floor($this->nW * $nMaxH / $this->nH);
            }
        } elseif ($this->nH > $nMaxH) {
            $h = $nMaxH;
            $w = floor($this->nW * $nMaxH / $this->nH);
        }
        return array($w, $h);
    }


    public function createSquareThumbnail($sSrc, $sDestination, $iNewWIdth = 0, $iNewHeight = 0, $iZoom = 1, $iQuality = 100) {

        if ($iNewWIdth == 0 && $iNewHeight == 0) {
            $iNewWIdth = 100;
            $iNewHeight = 100;
        }

        switch ($this->sType) {
            case 'jpg':
                $hImage = @imagecreatefromjpeg($sSrc);
                break;
            case 'png':
                $hImage = @imagecreatefrompng($sSrc);
                break;
            case 'gif':
                $hImage = @imagecreatefromgif($sSrc);
                break;
        }
        
        if (!$hImage)
        {
        	$data = file_get_contents($sSrc);
        	$hImage = @imagecreatefromstring($data);
        }

        $iWidth = imagesx($hImage);
        $iHeight = imagesy($hImage);
        $origin_x = 0;
        $origin_y = 0;

        if ($iNewWIdth && !$iNewHeight) {
            $iNewHeight = floor($iHeight * ($iNewWIdth / $iWidth));
        } elseif ($iNewHeight && !$iNewWIdth) {
            $iNewWIdth = floor($iWidth * ($iNewHeight / $iHeight));
        }

        if ($iZoom == 3) {
            $final_height = $iHeight * ($iNewWIdth / $iWidth);

            if ($final_height > $iNewHeight) {
                $iNewWIdth = $iWidth * ($iNewHeight / $iHeight);
            } else {
                $iNewHeight = $final_height;
            }
        }

        $hNewImage = imagecreatetruecolor($iNewWIdth, $iNewHeight);
        imagealphablending($hNewImage, false);

        $color = imagecolorallocatealpha($hNewImage, 0, 0, 0, 127);

        imagefill($hNewImage, 0, 0, $color);

        if ($iZoom == 2) {
            $final_height = $iHeight * ($iNewWIdth / $iWidth);

            if ($final_height > $iNewHeight) {
                $origin_x = $iNewWIdth / 2;
                $iNewWIdth = $iWidth * ($iNewHeight / $iHeight);
                $origin_x = round($origin_x - ($iNewWIdth / 2));
            } else {
                $origin_y = $iNewHeight / 2;
                $iNewHeight = $final_height;
                $origin_y = round($origin_y - ($iNewHeight / 2));
            }
        }

        imagesavealpha($hNewImage, true);

        if ($iZoom > 0) {
            $sSrc_x = $sSrc_y = 0;
            $sSrc_w = $iWidth;
            $sSrc_h = $iHeight;

            $cmp_x = $iWidth / $iNewWIdth;
            $cmp_y = $iHeight / $iNewHeight;

            if ($cmp_x > $cmp_y) {
                $sSrc_w = round($iWidth / $cmp_x * $cmp_y);
                $sSrc_x = round(($iWidth - ($iWidth / $cmp_x * $cmp_y)) / 2);
            } elseif ($cmp_y > $cmp_x) {
                $sSrc_h = round($iHeight / $cmp_y * $cmp_x);
                $sSrc_y = round(($iHeight - ($iHeight / $cmp_y * $cmp_x)) / 2);
            }

            imagecopyresampled($hNewImage, $hImage, $origin_x, $origin_y, $sSrc_x, $sSrc_y, $iNewWIdth, $iNewHeight, $sSrc_w, $sSrc_h);
        } else {
            imagecopyresampled($hNewImage, $hImage, 0, 0, 0, 0, $iNewWIdth, $iNewHeight, $iWidth, $iHeight);
        }
        if (file_exists($sDestination)) {
            if (@unlink($sDestination) != true) {
                @rename($sDestination, $sDestination . '_' . rand(10, 99));
            }
        }
        switch ($this->sType) {
            case 'gif':
                if (!$hNewImage) {
                    @copy($this->sPath, $sDestination);
                } else {
                    @imagegif($hNewImage, $sDestination);
                }
                break;
            case 'png':
                if($iQuality > 9)
                    $iQuality = 9;
                imagepng($hNewImage, $sDestination,$iQuality);
                imagealphablending($hNewImage, false);
                imagesavealpha($hNewImage, true);
                break;
            default:
                @imagejpeg($hNewImage, $sDestination,$iQuality);
                break;
        }

        @imageDestroy($hNewImage);
        @imageDestroy($hImage);
    }


    /**
     * Before save method. Called before all saves
     *
     * Handles setup of file uploads
     *
     * @param Model $model Model instance
     * @param array $options Options passed from Model::save().
     * @return bool
     */
    public function beforeSave(Model $model, $options = array()) {
        $this->_removingOnly = array();
        $isUpdating = !empty($model->id);

        foreach ($this->settings[$model->alias] as $field => $options) {
            if (!isset($model->data[$model->alias][$field]) || !is_array($model->data[$model->alias][$field])) {
                // it may have previously been set by a prior save using this same instance
                unset($this->runtime[$model->alias][$field]);
                continue;
            }

            $this->runtime[$model->alias][$field] = $model->data[$model->alias][$field];

            $removing = !empty($model->data[$model->alias][$field]['remove']);
            if ($this->_shouldUpdate($model, $field, $removing)) {
                // We're updating the file, remove old versions
                if ($isUpdating) {
                    $data = $model->find('first', array(
                        'conditions' => array("{$model->alias}.{$model->primaryKey}" => $model->id),
                        'contain' => false,
                        'recursive' => -1,
                    ));
                    $this->_prepareFilesForDeletion($model, $field, $data, $options);
                }

                if ($removing) {
                    $model->data[$model->alias] = array_merge($model->data[$model->alias], array(
                        $field => null,
                        $options['fields']['type'] => null,
                        $options['fields']['size'] => null,
                        $options['fields']['dir'] => null,
                    ));

                    $this->_removingOnly[$field] = true;
                    continue;
                }

                $model->data[$model->alias][$field] = array(
                    $field => null,
                    $options['fields']['type'] => null,
                    $options['fields']['size'] => null,
                );
            } elseif (!isset($model->data[$model->alias][$field]['name']) || !strlen($model->data[$model->alias][$field]['name'])) {
                // if field is empty, don't delete/nullify existing file
                unset($model->data[$model->alias][$field]);
                continue;
            }

            $this->runtime[$model->alias][$field]['name'] = $this->_retrieveName(
                    $model, $field, $this->runtime[$model->alias][$field]['name'], $model->data, array(
                'saveType' => $isUpdating ? 'update' : 'create',
            ));

            $model->data[$model->alias] = array_merge($model->data[$model->alias], array(
                $field => $this->runtime[$model->alias][$field]['name'],
                /*$options['fields']['type'] => $this->runtime[$model->alias][$field]['type'],
                $options['fields']['size'] => $this->runtime[$model->alias][$field]['size']*/
            ));
        }
        return true;
    }

    /**
     * Transform Model.field value like as PHP upload array (name, tmp_name)
     * for UploadBehavior plugin processing.
     *
     * @param Model $model Model instance
     * @param array $options Options passed from Model::save().
     * @return bool
     */
    public function beforeValidate(Model $model, $options = array()) {
        foreach ($this->settings[$model->alias] as $field => $options) {
            //if (!empty($model->data[$model->alias][$field]) && $this->_isUrl($model->data[$model->alias][$field])) {
            if (!empty($model->data[$model->alias][$field])) {
                $uri = $model->data[$model->alias][$field];
                if (!$this->_grab($model, $field, $uri)) {
                    $model->invalidate($field, __('File was not downloaded.', true));
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * After save method. Called after all saves
     *
     * Handles moving file uploads
     *
     * @param Model $model Model instance
     * @param bool $created True if this save created a new record
     * @param array $options Options passed from Model::save().
     * @return bool
     * @throws UploadException
     */
    public function afterSave(Model $model, $created, $options = array()) {
        $temp = array($model->alias => array());
        
        foreach ($this->settings[$model->alias] as $field => $options) {
            if ($this->_shouldSkip($model, $field)) {
                continue;
            }

            $tempPath = $this->_getPath($model, $field);

            $path = $this->settings[$model->alias][$field]['path'];
            $thumbnailPath = $this->settings[$model->alias][$field]['thumbnailPath'];
            
            if (!empty($tempPath)) {
                $path .= $tempPath . DS;
                $thumbnailPath .= $tempPath . DS;
            }
            
            $tmp = $this->runtime[$model->alias][$field]['tmp_name'];
            $tmp_1 = str_replace("/", DS, $tmp);
            $array_ignores = array(WWW_ROOT . 'uploads' . DS  . 'tmp',WWW_ROOT . 'uploads' . DS  . 'attachments');
            $checked = false;
            foreach ($array_ignores as $key)
            {
            	if (strpos($tmp_1,$key) === 0)
	            {
	            	$checked = true;
	            }
            }
            if (!$checked)
            {
            	return false;
            }
            	
            $ext = pathinfo($tmp, PATHINFO_EXTENSION);
            $extensions = MooCore::getInstance()->_getPhotoAllowedExtension();
            if (count($this->defaults['extensions']))
            {
            	$extensions = $this->defaults['extensions'];
            }
            if (empty($ext) || !in_array($ext, $extensions) )
            {
            	return false;
            }
            
            $filePath = $path . $model->data[$model->alias][$field];
            if (!$this->handleUploadedFile($model, $field, $tmp, $filePath)) {
                CakeLog::error(sprintf('Model %s, Field %s: Unable to move the uploaded file to %s', $model->alias, $field, $filePath));
                $model->invalidate($field, sprintf('Unable to move the uploaded file to %s', $filePath));
                $db = $model->getDataSource();
                $db->rollback();
                throw new UploadException('Unable to upload file');
            }
            
            $this->_createThumbnails($model, $field, $path, $thumbnailPath);
        }
        
        $this->_updateRecord($model, $temp);
        return $this->_unlinkFiles($model);
    }

    /**
     * Moves the file into place from it's temporary directory
     * to the specified file path
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param String $filename The filename of the uploaded file
     * @param String $destination The configured destination of the moved file
     * @return bool
     * */
    public function handleUploadedFile(Model $model, $field, $filename, $destination) {
        $callback = Hash::get($this->settings[$model->alias][$field], 'handleUploadedFileCallback');
        if (is_callable(array($model, $callback), true)) {
            return $model->{$callback}($field, $filename, $destination);
        }

        if (is_uploaded_file($filename)) {
            return move_uploaded_file($filename, $destination);
        }
        
        if (file_exists($filename)){
            return rename($filename, $destination);
        }
        
        return false;
    }

    /**
     * Removes directory
     *
     * @param string $dirname Path to the directory
     * @return bool
     * */
    public function rmdir($dirname) {
        if (is_dir($dirname)) {
            return rmdir($dirname);
        }
        return true;
    }

    /**
     * Unlinks a file on disk
     *
     * @param string $file path to file
     * @return bool
     * */
    public function unlink($file) {
        if (file_exists($file)) {
            return unlink($file);
        }
        return true;
    }

    /**
     * Removes a folder and it's contents from disk
     *
     * @param Model $model Model instance
     * @param string $path path to directory
     * @return bool
     * */
    public function deleteFolder(Model $model, $path) {
        if (!isset($this->__foldersToRemove[$model->alias])) {
            return false;
        }

        $folders = $this->__foldersToRemove[$model->alias];
        foreach ($folders as $folder) {
            if (strlen((string) $folder) === 0) {
                continue;
            }

            $dir = $path . $folder;
            if(file_exists($dir)){
                $it = new RecursiveDirectoryIterator($dir);
                $files = new RecursiveIteratorIterator($it, RecursiveIteratorIterator::CHILD_FIRST);
                foreach ($files as $file) {
                    if ($file->getFilename() === '.' || $file->getFilename() === '..') {
                        continue;
                    }

                    if ($file->isDir()) {
                        $this->rmdir($file->getRealPath());
                    } else {
                        $this->unlink($file->getRealPath());
                    }
                }
                $this->rmdir($dir);
            }
        }

        return true;
    }

    /**
     * Called before every deletion operation.
     *
     * @param Model $model Model instance
     * @param bool $cascade If true records that depend on this record will also be deleted
     * @return bool True if the operation should continue, false if it should abort
     */
    public function beforeDelete(Model $model, $cascade = true) {
        $data = $model->find('first', array(
            'conditions' => array("{$model->alias}.{$model->primaryKey}" => $model->id),
            'contain' => false,
            'recursive' => -1,
        ));

        foreach ($this->settings[$model->alias] as $field => $options) {
            $this->_prepareFilesForDeletion($model, $field, $data, $options);
        }

        return parent::beforeDelete($model, $cascade);
    }

    /**
     * Called after every deletion operation.
     *
     * @param Model $model Model instance
     * @return void
     */
    public function afterDelete(Model $model) {
        $result = array();
        if (!empty($this->__filesToRemove[$model->alias])) {
            foreach ($this->__filesToRemove[$model->alias] as $i => $file) {
                $result[] = $this->unlink($file);
                unset($this->__filesToRemove[$model->alias][$i]);
            }
        }

        foreach ($this->settings[$model->alias] as $options) {
            if ($options['deleteFolderOnDelete'] == true) {
                $this->deleteFolder($model, $options['path']);
                return true;
            }
        }
        return $result;
    }

    /**
     * Check that the file does not exceed the max
     * file size specified by PHP
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @return bool Success
     */
    public function isUnderPhpSizeLimit(Model $model, $check) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        return Hash::get($check[$field], 'error') !== UPLOAD_ERR_INI_SIZE;
    }

    /**
     * Check that the file does not exceed the max
     * file size specified in the HTML Form
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @return bool Success
     */
    public function isUnderFormSizeLimit(Model $model, $check) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        return Hash::get($check[$field], 'error') !== UPLOAD_ERR_FORM_SIZE;
    }

    /**
     * Check that the file was completely uploaded
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @return bool Success
     */
    public function isCompletedUpload(Model $model, $check) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        return Hash::get($check[$field], 'error') !== UPLOAD_ERR_PARTIAL;
    }

    /**
     * Check that a file was uploaded
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @return bool Success
     */
    public function isFileUpload(Model $model, $check) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        return Hash::get($check[$field], 'error') !== UPLOAD_ERR_NO_FILE;
    }

    /**
     * Check that either a file was uploaded,
     * or the existing value in the database is not blank.
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @return bool Success
     */
    public function isFileUploadOrHasExistingValue(Model $model, $check) {
        if (!$this->isFileUpload($model, $check)) {
            $pkey = $model->primaryKey;
            if (!empty($model->data[$model->alias][$pkey])) {
                $field = $this->_getField($check);
                $fieldValue = $model->field($field, array($pkey => $model->data[$model->alias][$pkey]));
                return !empty($fieldValue);
            }

            return false;
        }
        return true;
    }

    /**
     * Check that the PHP temporary directory is missing
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @param bool $requireUpload Whether or not to require a file upload
     * @return bool Success
     */
    public function tempDirExists(Model $model, $check, $requireUpload = true) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        $error = (int) Hash::get($check[$field], 'error');

        // Allow circumvention of this rule if uploads is not required
        if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        return $error !== UPLOAD_ERR_NO_TMP_DIR;
    }

    /**
     * Check that the file was successfully written to the server
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @param bool $requireUpload Whether or not to require a file upload
     * @return bool Success
     */
    public function isSuccessfulWrite(Model $model, $check, $requireUpload = true) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        $error = (int) Hash::get($check[$field], 'error');

        // Allow circumvention of this rule if uploads is not required
        if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        return $error !== UPLOAD_ERR_CANT_WRITE;
    }

    /**
     * Check that a PHP extension did not cause an error
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @param bool $requireUpload Whether or not to require a file upload
     * @return bool Success
     */
    public function noPhpExtensionErrors(Model $model, $check, $requireUpload = true) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        $error = (int) Hash::get($check[$field], 'error');

        // Allow circumvention of this rule if uploads is not required
        if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        return $error !== UPLOAD_ERR_EXTENSION;
    }

    /**
     * Check that the file is of a valid mimetype
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @param array $mimetypes file mimetypes to allow
     * @param bool $requireUpload Whether or not to require a file upload
     * @return bool Success
     */
    public function isValidMimeType(Model $model, $check, $mimetypes = array(), $requireUpload = true) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        $error = (int) Hash::get($check[$field], 'error');

        // Allow circumvention of this rule if uploads is not required
        if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        // Non-file uploads also mean the mimetype is invalid
        if (!isset($check[$field]['type']) || !strlen($check[$field]['type'])) {
            return false;
        }

        // Sometimes the user passes in a string instead of an array
        if (is_string($mimetypes)) {
            $mimetypes = array($mimetypes);
        }

        $keys = array_keys($mimetypes);
        foreach ($keys as $key) {
            if (!is_int($key)) {
                $mimetypes = $this->settings[$model->alias][$field]['mimetypes'];
                break;
            }
        }

        if (empty($mimetypes)) {
            $mimetypes = $this->settings[$model->alias][$field]['mimetypes'];
        }

        return in_array($check[$field]['type'], $mimetypes);
    }

    /**
     * Check that the upload directory is writable
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @param bool $requireUpload Whether or not to require a file upload
     * @return bool Success
     */
    public function isWritable(Model $model, $check, $requireUpload = true) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        $error = (int) Hash::get($check[$field], 'error');

        // Allow circumvention of this rule if uploads is not required
        if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        return is_writable($this->settings[$model->alias][$field]['path']);
    }

    /**
     * Check that the upload directory exists
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @param bool $requireUpload Whether or not to require a file upload
     * @return bool Success
     */
    public function isValidDir(Model $model, $check, $requireUpload = true) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        $error = (int) Hash::get($check[$field], 'error');

        // Allow circumvention of this rule if uploads is not required
        if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        return is_dir($this->settings[$model->alias][$field]['path']);
    }

    /**
     * Check that the file is below the maximum file upload size
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @param int $size Maximum file size
     * @param bool $requireUpload Whether or not to require a file upload
     * @return bool Success
     */
    public function isBelowMaxSize(Model $model, $check, $size = null, $requireUpload = true) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        $error = (int) Hash::get($check[$field], 'error');

        // Allow circumvention of this rule if uploads is not required
        if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        // Non-file uploads also mean the size is too small
        if (!isset($check[$field]['size']) || !strlen($check[$field]['size'])) {
            return false;
        }

        if (!$size) {
            $size = $this->settings[$model->alias][$field]['maxSize'];
        }

        return $check[$field]['size'] <= $size;
    }

    /**
     * Check that the file is above the minimum file upload size
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @param int $size Minimum file size
     * @param bool $requireUpload Whether or not to require a file upload
     * @return bool Success
     */
    public function isAboveMinSize(Model $model, $check, $size = null, $requireUpload = true) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        $error = (int) Hash::get($check[$field], 'error');

        // Allow circumvention of this rule if uploads is not required
        if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        // Non-file uploads also mean the size is too small
        if (!isset($check[$field]['size']) || !strlen($check[$field]['size'])) {
            return false;
        }

        if (!$size) {
            $size = $this->settings[$model->alias][$field]['minSize'];
        }

        return $check[$field]['size'] >= $size;
    }

    /**
     * Check that the file has a valid extension
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @param array $extensions file extenstions to allow
     * @param bool $requireUpload Whether or not to require a file upload
     * @return bool Success
     */
    public function isValidExtension(Model $model, $check, $extensions = array(), $requireUpload = true) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        $error = (int) Hash::get($check[$field], 'error');

        // Allow circumvention of this rule if uploads is not required
        if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        // Non-file uploads also mean the extension is invalid
        if (!isset($check[$field]['name']) || !strlen($check[$field]['name'])) {
            return false;
        }

        // Sometimes the user passes in a string instead of an array
        if (is_string($extensions)) {
            $extensions = array($extensions);
        }

        // Sometimes a user does not specify any extensions in the validation rule
        $keys = array_keys($extensions);
        foreach ($keys as $key) {
            if (!is_int($key)) {
                $extensions = $this->settings[$model->alias][$field]['extensions'];
                break;
            }
        }

        if (empty($extensions)) {
            $extensions = $this->settings[$model->alias][$field]['extensions'];
        }

        $pathInfo = $this->_pathinfo($check[$field]['name']);

        $extensions = array_map('strtolower', $extensions);
        return in_array(strtolower($pathInfo['extension']), $extensions);
    }

    /**
     * Check that the file is above the minimum height requirement
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @param int $height Height of Image
     * @param bool $requireUpload Whether or not to require a file upload
     * @return bool Success
     */
    public function isAboveMinHeight(Model $model, $check, $height = null, $requireUpload = true) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        $error = (int) Hash::get($check[$field], 'error');

        // Allow circumvention of this rule if uploads is not required
        if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        // Non-file uploads also mean the height is too big
        if (!isset($check[$field]['tmp_name']) || !strlen($check[$field]['tmp_name'])) {
            return false;
        }

        if (!$height) {
            $height = $this->settings[$model->alias][$field]['minHeight'];
        }

        list(, $imgHeight) = getimagesize($check[$field]['tmp_name']);
        return $height > 0 && $imgHeight >= $height;
    }

    /**
     * Check that the file is below the maximum height requirement
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @param int $height Height of Image
     * @param bool $requireUpload Whether or not to require a file upload
     * @return bool Success
     */
    public function isBelowMaxHeight(Model $model, $check, $height = null, $requireUpload = true) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        $error = (int) Hash::get($check[$field], 'error');

        // Allow circumvention of this rule if uploads is not required
        if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        // Non-file uploads also mean the height is too big
        if (!isset($check[$field]['tmp_name']) || !strlen($check[$field]['tmp_name'])) {
            return false;
        }

        if (!$height) {
            $height = $this->settings[$model->alias][$field]['maxHeight'];
        }

        list(, $imgHeight) = getimagesize($check[$field]['tmp_name']);
        return $height > 0 && $imgHeight <= $height;
    }

    /**
     * Check that the file is above the minimum width requirement
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @param int $width Width of Image
     * @param bool $requireUpload Whether or not to require a file upload
     * @return bool Success
     */
    public function isAboveMinWidth(Model $model, $check, $width = null, $requireUpload = true) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        $error = (int) Hash::get($check[$field], 'error');

        // Allow circumvention of this rule if uploads is not required
        if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        // Non-file uploads also mean the height is too big
        if (!isset($check[$field]['tmp_name']) || !strlen($check[$field]['tmp_name'])) {
            return false;
        }

        if (!$width) {
            $width = $this->settings[$model->alias][$field]['minWidth'];
        }

        list($imgWidth) = getimagesize($check[$field]['tmp_name']);
        return $width > 0 && $imgWidth >= $width;
    }

    /**
     * Check that the file is below the maximum width requirement
     *
     * @param Model $model Model instance
     * @param mixed $check Value to check
     * @param int $width Width of Image
     * @param bool $requireUpload Whether or not to require a file upload
     * @return bool Success
     */
    public function isBelowMaxWidth(Model $model, $check, $width = null, $requireUpload = true) {
        $field = $this->_getField($check);

        if (!empty($check[$field]['remove'])) {
            return true;
        }

        $error = (int) Hash::get($check[$field], 'error');

        // Allow circumvention of this rule if uploads is not required
        if (!$requireUpload && $error === UPLOAD_ERR_NO_FILE) {
            return true;
        }

        // Non-file uploads also mean the height is too big
        if (!isset($check[$field]['tmp_name']) || !strlen($check[$field]['tmp_name'])) {
            return false;
        }

        if (!$width) {
            $width = $this->settings[$model->alias][$field]['maxWidth'];
        }

        list($imgWidth) = getimagesize($check[$field]['tmp_name']);
        return $width > 0 && $imgWidth <= $width;
    }

    /**
     * Returns a root directory
     *
     * @param string $rootDir A specified root dir
     * @return string
     */
    protected function _getRootDir($rootDir = null) {
        if ($rootDir === null) {
            $rootDir = $this->defaults['rootDir'];
        }

        return $rootDir;
    }

    /**
     * Returns the thumbnail name format
     *
     * @param string $configuredName Configured name
     * @param string $usePrefixStyle Whether to use prefix style or not
     * @return string
     */
    protected function _getThumbnailName($configuredName, $usePrefixStyle) {
        if ($configuredName !== null) {
            return $configuredName;
        }

        if ($usePrefixStyle) {
            return '{size}_{filename}';
        }

        return '{filename}_{size}';
    }

    /**
     * Checks whether we should process an update for a given upload field
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param bool $removing Whether the record should be removed
     * @return string
     */
    protected function _shouldUpdate($model, $field, $removing) {
        if ($removing) {
            return true;
        }

        $deleteOnUpdate = $this->settings[$model->alias][$field]['deleteOnUpdate'];
        $isset = isset($model->data[$model->alias][$field]['name']);
        return $deleteOnUpdate && $isset && strlen($model->data[$model->alias][$field]['name']);
    }

    /**
     * Checks whether we should process an update for a given upload field
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param string $currentName current filename
     * @param array $data Array of data being manipulated in the current request
     * @param array $options Array of options for the current rename
     * @return string
     */
    protected function _retrieveName(Model $model, $field, $currentName, $data, $options = array()) {
        $options = array_merge(array(
            'rootDir' => $this->settings[$model->alias][$field]['rootDir'],
            'saveType' => 'create',
            'geometry' => null,
            'size' => null,
            'thumbnailType' => null,
            'thumbnailName' => null,
            'thumbnailMethod' => null,
            'mediaThumbnailType' => null,
            'dir' => null,
                ), $options);

        $name = $currentName;
        $callback = Hash::get($this->settings[$model->alias][$field], 'nameCallback');

        if (!empty($callback)) {
            $newName = null;

            if (is_callable(array($model, $callback), true)) {
                $newName = $model->{$callback}($field, $currentName, $data, $options);
            }

            if (!is_string($newName) || strlen($newName) == 0) {
                CakeLog::write('debug', sprintf(__('No filename after parsing. Function %s returned an invalid filename'), $callback));
            } else {
                $name = $newName;
            }
        }

        return $name;
    }

    /**
     * Unlinks files if necessary
     *
     * @param Model $model Model instance
     * @return mixed
     */
    protected function _unlinkFiles(Model $model) {
        if (empty($this->__filesToRemove[$model->alias])) {
            return true;
        }

        foreach ($this->__filesToRemove[$model->alias] as $i => $file) {
            $result[] = $this->unlink($file);
            unset($this->__filesToRemove[$model->alias][$i]);
        }

        return $result;
    }

    /**
     * Updates a database record with the necessary extra data
     *
     * @param Model $model Model instance
     * @param array $data array containing data to be saved to the record
     * @return void
     */
    protected function _updateRecord(Model $model, $data) {
        if (!empty($data[$model->alias])) {
            $model->updateAll($data[$model->alias], array(
                $model->alias . '.' . $model->primaryKey => $model->id
            ));
        }
    }

    /**
     * Checks if we can skip processing of a field
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @return bool
     */
    protected function _shouldSkip(Model $model, $field) {
        if (!in_array($field, array_keys($model->data[$model->alias]))) {
            return true;
        }

        if (empty($this->runtime[$model->alias][$field])) {
            return true;
        }

        if (isset($this->_removingOnly[$field])) {
            return true;
        }

        if (empty($model->data[$model->alias][$field])) {
            return true;
        }

        return false;
    }

    /**
     * Resizes an image using gd
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param string $path Path to existing file on disk
     * @param string $size Name of size to use
     * @param string $geometry Dimensions for current size
     * @param string $thumbnailPath Output thumbnail path
     * @return bool
     */
    protected function _resizePhp(Model $model, $field, $path, $thumbnailPath) {
        $srcFile = $path . $model->data[$model->alias][$field];
        $pathInfo = $this->_pathinfo($srcFile);
        $thumbnailType = $this->settings[$model->alias][$field]['thumbnailType'];
        
        if (!$thumbnailType || !is_string($thumbnailType)) {
            $thumbnailType = $pathInfo['extension'];
        }

        if (!$thumbnailType) {
            $thumbnailType = 'png';
        }

        $fileName = str_replace(
                array('_', '{size}', '{geometry}', '{filename}', '{primaryKey}'), array('', '', '', $pathInfo['filename'], $model->id), $this->settings[$model->alias][$field]['thumbnailName']
        );

        
        $thumbnailSizes = $this->settings[$model->alias][$field]['thumbnailSizes'];
        $photoConfig = Configure::read('core.photo_image_sizes');
        $photoSizes = explode('|', $photoConfig);
        if (isset($thumbnailSizes['config'])) {
            $photoConfig = Configure::read($thumbnailSizes['config']);
            $photoSizes = explode('|', $photoConfig);
        } else if (isset($thumbnailSizes['size'])) {
            $photoSizes = $thumbnailSizes['size'];
        }

        if (!is_array($photoSizes)) {
            return false;
        }

        $bRatio = true;
        foreach ($photoSizes as $key => $size) {

            $destFile = "{$thumbnailPath}" . $size . "_{$fileName}.{$thumbnailType}";

            // force to creating square thumbnail
            if (strstr($size, 'square')) {
                $size = str_replace('_square', '', $size);
                $bRatio = false;
            } else {
                $bRatio = true;
            }

            if (!$this->_load($srcFile)) {
                return false;
            }
            $nMaxW = $size;
            $nMaxH = $size;
            if ($bRatio) {
            	$nNewW = $nNewH = 0;
            	if ($size)
            	{
            		list($nNewW, $nNewH) = $this->_calcSize($nMaxW, $nMaxH);
            	}                
                if ($this->nW < $nNewW || $this->nH < $nNewH || ($this->nW == $nNewW && $this->nH == $nNewH)) {
                    @copy($this->sPath, $destFile);
                    continue; //return true;
                }

                @ini_set('memory_limit', '500M');

                switch ($this->_aInfo[2]) {
                    case 1:
                        $hFrm = @imageCreateFromGif($this->sPath);
                        break;
                    case 3:
                        $hFrm = @imageCreateFromPng($this->sPath);
                        break;
                    default:
                        $hFrm = @imageCreateFromJpeg($this->sPath);
                        break;
                }
                
                if (!$hFrm)
                {
                	$data = file_get_contents($this->sPath);
                	$hFrm = @imagecreatefromstring($data);
                }

                if ((int) $nNewH === 0) {
                    $nNewH = 1;
                }

                if ((int) $nNewW === 0) {
                    $nNewW = 1;
                }

                $hTo = imagecreatetruecolor($nNewW, $nNewH);

                switch ($this->sType) {
                    case 'gif':
                        $iBlack = imagecolorallocate($hTo, 0, 0, 0);
                        imagecolortransparent($hTo, $iBlack);
                        break;
                    case 'jpeg':
                    case 'jpg':
                    case 'jpe':
                        imagealphablending($hTo, true);
                        break;
                    case 'png':
                        imagealphablending($hTo, false);
                        imagesavealpha($hTo, true);
                        break;
                }

                if ($this->thumbLargeThenPic === false && $this->nH <= $nNewH && $this->nW <= $nNewW) {
                    $hTo = $hFrm;
                } else {
                    if ($hFrm) {
                        imageCopyResampled($hTo, $hFrm, 0, 0, 0, 0, $nNewW, $nNewH, $this->nW, $this->nH);
                    }
                }
                if (file_exists($destFile)) {
                    if (@unlink($destFile) != true) {
                        @rename($destFile, $destFile . '_' . rand(10, 99));
                    }
                }
                switch ($this->sType) {
                    case 'gif':
                        if (!$hTo) {
                            @copy($this->sPath, $destFile);
                        } else {
                            @imagegif($hTo, $destFile);
                        }
                        break;
                    case 'png':
                        imagepng($hTo, $destFile);
                        imagealphablending($hTo, false);
                        imagesavealpha($hTo, true);
                        break;
                    default:
                        @imagejpeg($hTo, $destFile);
                        break;
                }

                @imageDestroy($hTo);
                @imageDestroy($hFrm);
            } else {
                $this->createSquareThumbnail($srcFile, $destFile, $nMaxW, $nMaxH);
            }
        }

        return true;
    }

    /**
     * Creates an image resource for a given file
     *
     * @param string $filename full path to file
     * @param string $pathInfo Array of path information
     * @return bool
     */
    protected function _createImageResource($filename, $pathInfo) {
        switch (strtolower($pathInfo['extension'])) {
            case 'gif':
                $src = @imagecreatefromgif($filename);
                break;
            case 'jpg':
            case 'jpeg':
                $src = $this->_imagecreatefromjpegexif($filename);
                break;
            case 'png':
                $src = @imagecreatefrompng($filename);
                break;
            default:
                return false;
        }

        return $src;
    }

    /**
     * Same as imagecreatefromjpeg, but honouring the file's Exif data.
     * See http://www.php.net/manual/en/function.imagecreatefromjpeg.php#112902
     *
     * @param string $filename full path to file
     * @return resource rotated image
     */
    protected function _imagecreatefromjpegexif($filename) {
        $image = @imagecreatefromjpeg($filename);
        $exif = false;
        if (function_exists('exif_read_data')) {
            $exif = exif_read_data($filename);
        }

        if ($image && $exif && isset($exif['Orientation'])) {
            $ort = $exif['Orientation'];
        } else {
            return $image;
        }

        $trans = $this->_exifOrientationTransformations($ort);

        if ($trans['flip_vert']) {
            $image = $this->_flipImage($image, 'vert');
        }

        if ($trans['flip_horz']) {
            $image = $this->_flipImage($image, 'horz');
        }

        if ($trans['rotate_clockwise']) {
            $image = imagerotate($image, -1 * $trans['rotate_clockwise'], 0);
        }

        return $image;
    }

    /**
     * Determine what transformations need to be applied to an image,
     * in order to maintain it's orientation and get rid of it's Exif Orientation data
     * http://www.impulseadventure.com/photo/exif-orientation.html
     *
     * @param int $orientation The exif orientation of the image
     * @return array of transformations - array keys are:
     *         'flip_vert' - true if the image needs to be flipped vertically
     *         'flip_horz' - true if the image needs to be flipped horizontally
     *         'rotate_clockwise' - number of degrees image needs to be rotated, clockwise
     */
    protected function _exifOrientationTransformations($orientation) {
        $trans = array(
            'flip_vert' => false,
            'flip_horz' => false,
            'rotate_clockwise' => 0,
        );

        switch ($orientation) {
            case 1:
                break;

            case 2:
                $trans['flip_horz'] = true;
                break;

            case 3:
                $trans['rotate_clockwise'] = 180;
                break;

            case 4:
                $trans['flip_vert'] = true;
                break;

            case 5:
                $trans['flip_vert'] = true;
                $trans['rotate_clockwise'] = 90;
                break;

            case 6:
                $trans['rotate_clockwise'] = 90;
                break;

            case 7:
                $trans['flip_horz'] = true;
                $trans['rotate_clockwise'] = 90;
                break;

            case 8:
                $trans['rotate_clockwise'] = -90;
                break;
        }

        return $trans;
    }

    /**
     * Flip an image object. Code from http://www.roscripts.com/snippets/show/55
     *
     * @param resource $img An image resource, such as one returned by imagecreatefromjpeg()
     * @param string $type 'horz' or 'vert'
     * @return resource The flipped image
     */
    protected function _flipImage($img, $type) {
        $width = imagesx($img);
        $height = imagesy($img);
        $dest = imagecreatetruecolor($width, $height);
        switch ($type) {
            case 'vert':
                for ($i = 0; $i < $height; $i++) {
                    imagecopy($dest, $img, 0, ($height - $i - 1), 0, $i, $width, 1);
                }
                break;
            case 'horz':
                for ($i = 0; $i < $width; $i++) {
                    imagecopy($dest, $img, ($width - $i - 1), 0, $i, 0, 1, $height);
                }
                break;
        }
        return $dest;
    }

    /**
     * Retrieves the output path for uploaded files
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @return string
     */
    protected function _getPath(Model $model, $field) {
        $path = $this->settings[$model->alias][$field]['path'];
        $pathMethod = $this->settings[$model->alias][$field]['pathMethod'];

        if (method_exists($this, $pathMethod)) {
            return $this->$pathMethod($model, $field, $path);
        }

        return $this->_getPathPrimaryKey($model, $field, $path);
    }

    /**
     * Creates a path for file uploading
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param string $path Base directory
     * @return string
     */
    protected function _getPathFlat(Model $model, $field, $path) {
        $destDir = $path;
        $this->_mkPath($model, $field, $destDir);
        return '';
    }

    /**
     * Creates a path for file uploading based on the model primaryKey
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param string $path Base directory
     * @return string
     */
    protected function _getPathPrimaryKey(Model $model, $field, $path) {
        $destDir = $path . $model->id . DIRECTORY_SEPARATOR;
        $this->_mkPath($model, $field, $destDir);
        return $model->id;
    }

    /**
     * Creates a path for file uploading based on a random string
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param string $path Base directory
     * @return string
     */
    protected function _getPathRandom(Model $model, $field, $path) {
        $endPath = null;
        $decrement = 0;
        $string = crc32($field . microtime());

        for ($i = 0; $i < 3; $i++) {
            $decrement = $decrement - 2;
            $endPath .= sprintf("%02d" . DIRECTORY_SEPARATOR, substr('000000' . $string, $decrement, 2));
        }

        $destDir = $path . $endPath;
        $this->_mkPath($model, $field, $destDir);

        return substr($endPath, 0, -1);
    }

    /**
     * Creates a path for file uploading based on a random string and model primaryKey
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param string $path Base directory
     * @return string
     */
    protected function _getPathRandomCombined(Model $model, $field, $path) {
        $endPath = $model->id . DIRECTORY_SEPARATOR;
        $decrement = 0;
        $string = crc32($field . microtime() . $model->id);

        for ($i = 0; $i < 3; $i++) {
            $decrement = $decrement - 2;
            $endPath .= sprintf("%02d" . DIRECTORY_SEPARATOR, substr('000000' . $string, $decrement, 2));
        }

        $destDir = $path . $endPath;
        $this->_mkPath($model, $field, $destDir);

        return substr($endPath, 0, -1);
    }

    /**
     * Download remote file into PHP's TMP dir
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param string $uri URI for file to retrieve
     * @return bool
     */
    protected function _grab(Model $model, $field, $uri) {
        $fileName = '';
        $fileNameExpl = '';
        $contentType = '';
        $tmpFile = '';
        $contentLength = 0;
        
        if (is_string($uri)){
            // open socket for external link
            if (!filter_var($uri, FILTER_VALIDATE_URL) === false){
           
                
                $headers = MooCore::getInstance()->getHeader($uri);
                $file = MooCore::getInstance()->getHtmlContent($uri);
                $contentType = $headers['contentType'];
                $contentLength = $headers['contentLength'];

//                $fileName = basename($uri);
                
                // MOOSOCIAL-2617
//                $fileNameExplore = explode("?", $fileName);
//                $fileNameExpl = $fileNameExplore[0];
                $ext = pathinfo($uri, PATHINFO_EXTENSION);
                $extensions = MooCore::getInstance()->_getPhotoAllowedExtension();
                if (count($this->defaults['extensions']))
                {
                	$extensions = $this->defaults['extensions'];
                }
                if (empty($ext) || !in_array($ext, $extensions) )
                {
                	return false;
                }

                $fileNameExpl = md5(uniqid()).'.jpg';
                $tmpFile = WWW_ROOT . 'uploads' . DS . 'tmp' . DS . strtolower($fileNameExpl);
                if (isset($model->data[$model->alias]['file_name_override'])) {
                    $fileName = $model->data[$model->alias]['file_name_override'] . '.' . pathinfo($uri, PATHINFO_EXTENSION);
                }

                $file = file_put_contents($tmpFile, $file);

                if (!$file) {
                    return false;
                }
            }
            // absolute path
            else {
                $uri = WWW_ROOT . $uri;
                if (file_exists($uri)){
                    $mimetypes = array(
                        'gif' => 'image/gif',
                        'png' => 'image/png',
                        'jpg' => 'image/jpeg',
                        'jpeg' => 'image/jpeg',
                        'css' => 'text/css',
                        'js' => 'text/javascript',
                    );
                    $path_parts = pathinfo($uri);
                                       
                    if (array_key_exists(strtolower($path_parts['extension']), $mimetypes)) {
                        $contentType = $mimetypes[strtolower($path_parts['extension'])];
                    } else {
                        $contentType = 'application/octet-stream';
                    }
                    $tmpFile = $uri;
                    $fileNameExpl = basename($uri);
                }
            }
          
            $model->data[$model->alias][$field] = array(
                'name' => $fileNameExpl,
                'type' => $contentType,
                'tmp_name' => $tmpFile,
                'error' => 0,
                'size' => $contentLength,
            );
        }
        
        return true;
    }

    /**
     * Creates a directory
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param string $destDir directory to create
     * @return bool
     */
    protected function _mkPath(Model $model, $field, $destDir) {
        if (!file_exists($destDir)) {
            mkdir($destDir, $this->settings[$model->alias][$field]['mode'], true);
            chmod($destDir, $this->settings[$model->alias][$field]['mode']);
        }
        return true;
    }

    /**
     * Returns a path based on settings configuration
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param array $options Options to use when building a path
     * @return string
     * */
    protected function _path(Model $model, $field, $options = array()) {
        $defaults = array(
            'isThumbnail' => true,
            'path' => '{ROOT}webroot{DS}files{DS}{model}{DS}{field}{DS}',
            'rootDir' => $this->defaults['rootDir'],
        );

        $options = array_merge($defaults, $options);

        foreach ($options as $key => $value) {
            if ($value === null) {
                $options[$key] = $defaults[$key];
            }
        }

        if (!$options['isThumbnail']) {
            $options['path'] = str_replace(array('{size}', '{geometry}'), '', $options['path']);
        }

        $current_year = date('Y');
        $current_month = date('m');
        $current_day = date('d');
        $replacements = array(
            '{ROOT}' => $options['rootDir'],
            '{primaryKey}' => $model->id,
            '{model}' => Inflector::underscore($model->alias),
            '{field}' => $field,
            '{year}' => $current_year,
            '{month}' => $current_month,
            '{day}' => $current_day,
            '{time}' => time(),
            '{microtime}' => microtime(),
            '{DS}' => DIRECTORY_SEPARATOR,
            '//' => DIRECTORY_SEPARATOR,
            '/' => DIRECTORY_SEPARATOR,
            '\\' => DIRECTORY_SEPARATOR,
        );

        $newPath = Folder::slashTerm(str_replace(
                                array_keys($replacements), array_values($replacements), $options['path']
        ));

        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (!preg_match('/^([a-zA-Z]:\\\|\\\\)/', $newPath)) {
                $newPath = $options['rootDir'] . $newPath;
            }
        } elseif ($newPath[0] !== DIRECTORY_SEPARATOR) {
            $newPath = $options['rootDir'] . $newPath;
        }

        $pastPath = $newPath;
        while (true) {
            $pastPath = $newPath;
            $newPath = str_replace(array(
                '//',
                '\\',
                DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR
                    ), DIRECTORY_SEPARATOR, $newPath);
            if ($pastPath == $newPath) {
                break;
            }
        }

        return $newPath;
    }

    /**
     * Returns the path for a given thumbnail size
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param array $params Array of parameters to use for the thumbnail
     * @return string
     * */
    protected function _pathThumbnail(Model $model, $field, $params = array()) {
        return $params['thumbnailPath'];
    }

    /**
     * Creates thumbnails for images
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param string $path Path to existing file on disk
     * @param string $thumbnailPath Output thumbnail path
     * @return void
     * @throws Exception
     */
    protected function _createThumbnails(Model $model, $field, $path, $thumbnailPath) {
        $isImage = $this->_isImage($this->runtime[$model->alias][$field]['type']);
        $isMedia = $this->_isMedia($this->runtime[$model->alias][$field]['type']);
        $createThumbnails = $this->settings[$model->alias][$field]['thumbnails'];
        

        if (($isImage || $isMedia) && $createThumbnails) {
            $method = $this->settings[$model->alias][$field]['thumbnailMethod'];


            $thumbnailPathSized = $this->_pathThumbnail($model, $field, compact('thumbnailPath'));
            $this->_mkPath($model, $field, $thumbnailPathSized);
            
            $valid = false;
            if (method_exists($model, $method)) {
                $valid = $model->$method($model, $field, $path, $thumbnailPathSized);
            } elseif (method_exists($this, $method)) {
                $valid = $this->$method($model, $field, $path, $thumbnailPathSized);
            } else {
                CakeLog::error(sprintf('Model %s, Field %s: Invalid thumbnailMethod %s', $model->alias, $field, $method));
                $db = $model->getDataSource();
                $db->rollback();
                throw new Exception("Invalid thumbnailMethod %s", $method);
            }

            if (!$valid) {
                $model->invalidate($field, 'resizeFail');
            }
        }
    }

    /**
     * Checks if a given mimetype is an image mimetype
     *
     * @param string $mimetype mimetype
     * @return bool
     * */
    protected function _isImage($mimetype) {
        return in_array($mimetype, $this->_imageMimetypes);
    }

    /**
     * Checks if a given string is a url
     *
     * @param string $string string to check
     * @return bool
     * */
    protected function _isUrl($string) {
        return (filter_var($string, FILTER_VALIDATE_URL) ? true : false);
    }

    /**
     * Checks if a given mimetype is a media mimetype
     *
     * @param string $mimetype mimetype
     * @return bool
     * */
    protected function _isMedia($mimetype) {
        return in_array($mimetype, $this->_mediaMimetypes);
    }

    /**
     * Retrieves the mimetype for a given file
     *
     * @param string $filePath path to file
     * @return string
     * */
    protected function _getMimeType($filePath) {
        if (!file_exists($filePath)) {
            return '';
        }
        if (class_exists('finfo')) {
            $finfo = new finfo(defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME);
            return $finfo->file($filePath);
        }

        if (function_exists('exif_imagetype') && function_exists('image_type_to_mime_type')) {
            $mimetype = image_type_to_mime_type(exif_imagetype($filePath));
            if ($mimetype !== false) {
                return $mimetype;
            }
        }

        if (function_exists('mime_content_type')) {
            return mime_content_type($filePath);
        }

        return 'application/octet-stream';
    }

    /**
     * Sets up an array of files to be deleted
     *
     * @param Model $model Model instance
     * @param string $field Name of field being modified
     * @param array $data array of data
     * @param array $options array of configuration settings for a field
     * @return bool
     * */
    protected function _prepareFilesForDeletion(Model $model, $field, $data, $options = array()) {
        if ($options['keepFilesOnDelete'] === true) {
            return array();
        }

        if (!strlen($data[$model->alias][$field])) {
            return $this->__filesToRemove;
        }

        if (!empty($options['fields']['dir']) && isset($data[$model->alias][$options['fields']['dir']])) {
            $dir = $data[$model->alias][$options['fields']['dir']];
        } else {
            if (in_array($options['pathMethod'], array('_getPathFlat', '_getPathPrimaryKey'))) {
                $model->id = $data[$model->alias][$model->primaryKey];
                $dir = call_user_func(array($this, '_getPath'), $model, $field);
            } else {
                CakeLog::error(sprintf('Cannot get directory to %s.%s: %s pathMethod is not supported.', $model->alias, $field, $options['pathMethod']));
            }
        }

        $filePathDir = $this->settings[$model->alias][$field]['path'] . (empty($dir) ? '' : $dir . DS);
        $filePath = $filePathDir . $data[$model->alias][$field];
        
        if (isset($data[$model->alias]['year_folder']) && $data[$model->alias]['year_folder']){
            $filePath = str_replace("/". date('Y') ."/", "/" . date('Y', strtotime($data[$model->alias]['created'])) ."/", $filePath);
            $filePath = str_replace("/". date('m') ."/", "/" . date('m', strtotime($data[$model->alias]['created'])) ."/", $filePath);
            $filePath = str_replace("/". date('d') ."/", "/" . date('d', strtotime($data[$model->alias]['created'])) ."/", $filePath);
        }else{
            
        }
        
        $pathInfo = $this->_pathinfo($filePath);

        if (!isset($this->__filesToRemove[$model->alias])) {
            $this->__filesToRemove[$model->alias] = array();
        }

        $this->__filesToRemove[$model->alias][] = $filePath;
        $this->__foldersToRemove[$model->alias][] = $dir;

        $directorySeparator = empty($dir) ? '' : DIRECTORY_SEPARATOR;
        $mimeType = $this->_getMimeType($filePath);
        $isMedia = $this->_isMedia($mimeType);
        $isImagickResize = $options['thumbnailMethod'] == 'imagick';
        $thumbnailType = $options['thumbnailType'];

        if ($isImagickResize) {
            if ($isMedia) {
                $thumbnailType = $options['mediaThumbnailType'];
            }

            if (!$thumbnailType || !is_string($thumbnailType)) {
                try {
                    $srcFile = $filePath;
                    $image = new imagick();
                    if ($isMedia) {
                        $image->setResolution(300, 300);
                        $srcFile = $srcFile . '[0]';
                    }

                    $image->readImage($srcFile);
                    $thumbnailType = $image->getImageFormat();
                } catch (Exception $e) {
                    $thumbnailType = 'png';
                }
            }
        } else {
            if (!$thumbnailType || !is_string($thumbnailType)) {
                $thumbnailType = $pathInfo['extension'];
            }

            if (!$thumbnailType) {
                $thumbnailType = 'png';
            }
        }

        // MOOSOCIAL-2933
        $thumbnailPath = $options['thumbnailPath'];
        if (isset($data[$model->alias]['year_folder']) && $data[$model->alias]['year_folder']){
            $thumbnailPath = str_replace("/". date('Y') ."/", "/" . date('Y', strtotime($data[$model->alias]['created'])) ."/", $thumbnailPath);
            $thumbnailPath = str_replace("/". date('m') ."/", "/" . date('m', strtotime($data[$model->alias]['created'])) ."/", $thumbnailPath);
            $thumbnailPath = str_replace("/". date('d') ."/", "/" . date('d', strtotime($data[$model->alias]['created'])) ."/", $thumbnailPath);
        }else{
            
        }
        
        $thumbnailSizes = $this->settings[$model->alias][$field]['thumbnailSizes'];
        $photoConfig = Configure::read('core.photo_image_sizes');
        $photoSizes = explode('|', $photoConfig);
        if (isset($thumbnailSizes['config'])) {
            $photoConfig = Configure::read($thumbnailSizes['config']);
            $photoSizes = explode('|', $photoConfig);
        } else if (isset($thumbnailSizes['size'])) {
            $photoSizes = $thumbnailSizes['size'];
        }

        $fileName = str_replace(
                array('_', '{size}', '{geometry}', '{filename}', '{primaryKey}', '{time}', '{microtime}'), array('', '', '', $pathInfo['filename'], $model->id, time(), microtime()), $options['thumbnailName']
        );
        foreach ($photoSizes as $key => $size) {
            $destFile = "{$thumbnailPath}{$dir}{$directorySeparator}" . $size . "_{$fileName}.{$thumbnailType}";
            $this->__filesToRemove[$model->alias][] = $destFile;
        }

        return $this->__filesToRemove;
    }

    /**
     * Returns the field to check
     *
     * @param array $check array of validation data
     * @return string
     * */
    protected function _getField($check) {
        $fieldKeys = array_keys($check);
        return array_pop($fieldKeys);
    }

    /**
     * Returns the pathinfo for a file
     *
     * @param string $filename name of file on disk
     * @return array
     * */
    protected function _pathinfo($filename) {
        $pathInfo = pathinfo($filename);

        if (!isset($pathInfo['extension']) || !strlen($pathInfo['extension'])) {
            $pathInfo['extension'] = '';
        }

        // PHP < 5.2.0 doesn't include 'filename' key in pathinfo. Let's try to fix this.
        if (empty($pathInfo['filename'])) {
            $pathInfo['filename'] = basename($pathInfo['basename'], '.' . $pathInfo['extension']);
        }
        return $pathInfo;
    }

}
