<?php

/****************************************
Example of how to use this uploader class...
You can uncomment the following lines (minus the require) to use these as your defaults.

// list of valid extensions, ex. array("jpeg", "xml", "bmp")
$allowedExtensions = array();
// max file size in bytes
$sizeLimit = 10 * 1024 * 1024;
//the input name set in the javascript
$inputName = 'qqfile'

require('valums-file-uploader/server/php.php');
$uploader = new qqFileUploader($allowedExtensions, $sizeLimit, $inputName);

// Call handleUpload() with the name of the folder, relative to PHP's getcwd()
$result = $uploader->handleUpload('uploads/');

// to pass data through iframe you will need to encode all html tags
header("Content-Type: text/plain");
echo htmlspecialchars(json_encode($result), ENT_NOQUOTES);

/******************************************/



/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    private $inputName;
    
    /**
     * @param string $inputName; defaults to the javascript default: 'qqfile'
     */
    public function __construct($inputName = 'qqfile'){
        $this->inputName = $inputName;
    }
    
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    public function save($path) {    
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        
        if ($realSize != $this->getSize()){            
            return false;
        }
        
        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        
        return true;
    }

    /**
     * Get the original filename
     * @return string filename
     */
    public function getName() {
        return $_GET[$this->inputName];
    }
    
    /**
     * Get the file size
     * @return integer file-size in byte
     */
    public function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new Exception(__("Getting content length is not supported."));
        }      
    }   
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {
    private $inputName;
	
    /**
     * @param string $inputName; defaults to the javascript default: 'qqfile'
     */
    public function __construct($inputName = 'qqfile'){
        $this->inputName = $inputName;
    }
    
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    public function save($path) {
        return move_uploaded_file($_FILES[$this->inputName]['tmp_name'], $path);
    }
    
    /**
     * Get the original filename
     * @return string filename
     */
    public function getName() {
        return $_FILES[$this->inputName]['name'];
    }
    
    /**
     * Get the file size
     * @return integer file-size in byte
     */
    public function getSize() {
        return $_FILES[$this->inputName]['size'];
    }
}

/**
 * Class that encapsulates the file-upload internals
 */
class qqFileUploader {
    private $allowedExtensions;
    private $sizeLimit;
    private $file;
    private $uploadName;

    /**
     * @param array $allowedExtensions; defaults to an empty array
     * @param int $sizeLimit; defaults to the server's upload_max_filesize setting
     * @param string $inputName; defaults to the javascript default: 'qqfile'
     */
    function __construct(array $allowedExtensions = null, $sizeLimit = null, $inputName = 'qqfile'){
        if($allowedExtensions===null) {
            $allowedExtensions = array();
    	}
    	if($sizeLimit===null) {
    	    $sizeLimit = min($this->toBytes(ini_get('post_max_size')), $this->toBytes(ini_get('upload_max_filesize')));
    	}
    	        
        $allowedExtensions = array_map("strtolower", $allowedExtensions);
            
        $this->allowedExtensions = $allowedExtensions;        
        $this->sizeLimit = $sizeLimit;
        
        $this->checkServerSettings();       

        if(!isset($_SERVER['CONTENT_TYPE'])) {
            $this->file = false;	
        } else if (strpos(strtolower($_SERVER['CONTENT_TYPE']), 'multipart/') === 0) {
            $this->file = new qqUploadedFileForm($inputName);
        } else {
            $this->file = new qqUploadedFileXhr($inputName);
        }
    }
    
    /**
     * Get the name of the uploaded file
     * @return string
     */
    public function getUploadName(){
        if( isset( $this->uploadName ) )
            return $this->uploadName;
    }
	
    /**
     * Get the original filename
     * @return string filename
     */
    public function getName(){
        if ($this->file)
            return $this->file->getName();
    }
    
    /**
     * Internal function that checks if server's may sizes match the
     * object's maximum size for uploads
     */
    private function checkServerSettings(){        
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));        
        
        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';             
            die(json_encode(array('error'=> __("increase post_max_size and upload_max_filesize to %s",$size))));
        }        
    }
    
    /**
     * Convert a given size with units to bytes
     * @param string $str
     */
    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        $number=substr($val,0,-1);
        switch($last) {
            case 'g': return $number*pow(1024,3);
            case 'm': return $number*pow(1024,2);
            case 'k': return $number*1024;
        }
        return $val;
    }
    
    /**
     * Handle the uploaded file
     * @param string $uploadDirectory
     * @param string $replaceOldFile=true
     * @returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
        if (!is_writable($uploadDirectory)){
            return array('error' => __("Server error. Upload directory isn't writable."));
        }
        
        if (!$this->file){
            return array('error' => __("No files were uploaded."));
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            return array('error' => __("File is empty"));
        }
        
        if ($size > $this->sizeLimit) {
            return array('error' => __("File is too large"));
        }
        
        $pathinfo = pathinfo($this->file->getName());
        //$filename = $pathinfo['filename'];
        $filename = md5(uniqid());
        $ext = @$pathinfo['extension'];		// hide notices if extension is empty

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => __("File has an invalid extension, it should be one of  %s .",$these));
        }
        
        $ext = ($ext == '') ? $ext : '.' . strtolower($ext);
        
        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            while (file_exists($uploadDirectory . DIRECTORY_SEPARATOR . $filename . $ext)) {
                $filename .= rand(10, 99);
            }
        }
        
        $this->uploadName = $filename . $ext;
		
        if ($this->file->save($uploadDirectory . DIRECTORY_SEPARATOR . $filename . $ext)){
            if(in_array(strtolower($ext), array('jpg', 'jpeg') ) )
                $this->image_fix_orientation($uploadDirectory . DIRECTORY_SEPARATOR . $filename . $ext);
            return array('success'=>true, 'filename' => $filename . $ext);
        } else {
            return array('error'=> __("Could not save uploaded file.The upload was cancelled, or server error encountered"));
        }
        
    }
    // MOOSOCIAL-1311
    // Problems with iPhone photos uploaded sideways
    function image_fix_orientation($filename) {
        if(!$this->isImage($filename))
            return false;
        $exif = exif_read_data($filename);
        if (!empty($exif['Orientation'])) {
            $image = imagecreatefromjpeg($filename);
            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;

                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;

                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }

            imagejpeg($image, $filename, 100);
        }
        return true;
    }
    function isImage($filename)
    {
        $ext = end(explode('.', $filename));
        $image_types = array('gif', 'jpg', 'jpeg', 'png', 'jpe');
        if (in_array($ext, $image_types))
        {
            $file_data = getimagesize($filename);
            if(is_array($file_data) && strpos($file_data['mime'],'image') !== false)
            {
                return TRUE;
            }
        }
        return FALSE;
    }
}
