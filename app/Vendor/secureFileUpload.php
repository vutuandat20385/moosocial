<?php
class SecureFileUpload
{
    // http://software-security.sans.org/blog/2009/12/28/8-basic-rules-to-implement-secure-file-uploads/
    var $fileKeyName = "";
    var $path = "";
    var $newFileName;
    var $checklist = array(
        'create-new-file-name' => true, //
        'webserver-blacklist' => true,  //
        'store-the-file-outside-of-your-document-root' => false,
        'check-the-file-size' => true,  //
        'extensions-are-meaningless' => true,
        'try-a-malware-scan' => false,
        'keep-tight-control-of-permissions' => false,
        'authenticate-file-uploads' => false,
        'limit-the-number-of-uploaded-files' => false,
    );
    var $blacklist = array(".php", ".phtml", ".php3", ".php4", ".php5", ".html", ".js", ".shtml", ".pl", ".py");
    var $whitelist = array("extensions" => array(), "type" => array());
    var $maxSize = 1048576 ; // 1 Mb = 1 * 1024 *1024
    var $errorMessage = "";
    var $isSecured = true;

    public function __construct($config, $checklist = array())
    {
        if (empty($_FILES[$config['fileKeyName']])){
            $this->isSecured = false;
            $this->errorMessage = __("No file uploaded");
            return;
        }
        
        $this->fileKeyName = $config['fileKeyName'];
        if (empty($_FILES[$config['fileKeyName']]) && ($_FILES[$config['fileKeyName']]['error'] != 0)) {
            $this->isSecured = false;
            $this->errorMessage = __("No file uploaded");
        }
        $this->path   = $config['path'];

        $this->maxSize = (!empty($config['maxSize'])) ? $config['maxSize'] : $this->maxSize;
        $this->whitelist = (!empty($config['whitelist'])) ? $config['whitelist'] : $this->whitelist;
        $this->checklist = array_merge($this->checklist, $checklist);

        if ($this->checklist['webserver-blacklist']) {
            foreach ($this->blacklist as $file) {
                if (preg_match("/$file\$/i", $_FILES[$config['fileKeyName']]['name'])) {
                    $this->errorMessage = __("Uploading executable file Not Allowed");
                    $this->isSecured = false;
                }
            }
        }
        if ($this->isSecured && $this->checklist['check-the-file-size']) {
            if ($_FILES[$config['fileKeyName']]['size'] > $this->maxSize) {
                
                $fileSizeInMb = number_format($this->maxSize / (1024 * 1024), 2);
                $this->errorMessage = __("File is too big. Max allowed file size is %s Mb", $fileSizeInMb);
                $this->isSecured = false;
            }
        }
        if ($this->isSecured && $this->checklist['extensions-are-meaningless']) {
            $file_info = pathinfo($_FILES[$config['fileKeyName']]['name']);
            $file_extension = $file_info['extension'];
            $file_type = $_FILES[$config['fileKeyName']]['type'];
            if (!in_array($file_extension, $this->whitelist['extensions'])) {
                $this->errorMessage = __("Invalid file Extension");
                $this->isSecured = false;
            }
            if (!in_array($file_type, $this->whitelist['type'])) {
                $this->errorMessage = __("Invalid file Type");
                $this->isSecured = false;
            }
            if ($this->isSecured && $this->checklist['create-new-file-name']) {
                $this->newFileName = $this->generateRandomString() . "." . $file_extension;
            }
        }

    }

    private function generateRandomString($length = 15)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $length);
        return $randomString;
    }

    public function execute()
    {
        if (!$this->isSecured) {
            return false;
        }
        $fileKeyName = $this->fileKeyName;
        if ($this->checklist['create-new-file-name']) {

            if (!move_uploaded_file($_FILES[$fileKeyName]['tmp_name'], $this->path . $this->newFileName)) {
                $this->errorMessage = __("Server Error!");
                return false;
            }
        } else {

            if (!move_uploaded_file($_FILES[$fileKeyName]['tmp_name'], $this->path . $_FILES[$fileKeyName]['name'])) {
                $this->errorMessage = __("Server Error!");
                return false;
            }
        }
        return true;
    }

    public function getMessage()
    {
        return $this->errorMessage;
    }
    public function getFileName(){
        return $this->newFileName;
    }
}

class SecureImageUpload extends SecureFileUpload{
    var $num_type,$width, $height,$width_orig,$height_orig, $scaleUp = false ;
    public function __construct($config, $checklist = array()){
        parent::__construct($config,$checklist);
        $this->height = (!empty($config['height'])) ? $config['height'] : null;
        $this->width = (!empty($config['width'])) ? $config['width'] : null;
        $this->scaleUp = (!empty($config['scaleUp'])) ? $config['scaleUp'] : $this->scaleUp;
    }
    public function execute(){

        if (!$this->isSecured) {
            return false;
        }
        $fileKeyName = $this->fileKeyName;
        @list($this->width_orig, $this->height_orig, $this->num_type) = getimagesize($_FILES[$fileKeyName]['tmp_name']);
        if((!$this->scaleUp && ($this->width > $this->width_orig && $this->height > $this->height_orig)) || (empty($this->width) && empty($this->height)))
        {
            $this->width = $this->width_orig;
            $this->height = $this->height_orig;
        }
        else
        {
            // if height diff is less than width dif, calc height
            if(($this->height_orig - $this->height) <= ($this->width_orig - $this->width))
                $this->height = ($this->width / $this->width_orig) * $this->height_orig;
            else
                $this->width = ($this->height / $this->height_orig) * $this->width_orig;
        }


        if (!$this->num_type)
        {
            $this->errorMessage = __("No Image");
            return false;
        }
        // Resample

        switch($this->num_type)
        {
            case IMAGETYPE_GIF: $image_o = imagecreatefromgif($_FILES[$fileKeyName]['tmp_name']); $ext = '.gif'; break;
            case IMAGETYPE_JPEG: $image_o = imagecreatefromjpeg($_FILES[$fileKeyName]['tmp_name']); $ext = '.jpg'; break;
            case IMAGETYPE_PNG: $image_o = imagecreatefrompng($_FILES[$fileKeyName]['tmp_name']); $ext = '.png'; break;
        }
        $image_r = imagecreatetruecolor($this->width, $this->height);

        switch($this->num_type)
        {
            case IMAGETYPE_GIF: break;
            case IMAGETYPE_JPEG: break;
            case IMAGETYPE_PNG:
                imagealphablending( $image_r, false );
                imagesavealpha( $image_r, true );
                break;
        }

        imagecopyresampled($image_r, $image_o, 0, 0, 0, 0, $this->width, $this->height, $this->width_orig, $this->height_orig);

        if ($this->checklist['create-new-file-name']) {
            $imagePath = $this->path . $this->newFileName;
        } else {
            $imagePath = $_FILES[$fileKeyName]['name'];
        }
        switch($this->num_type)
        {
            case IMAGETYPE_GIF: imagegif($image_r,$imagePath ); break;
            case IMAGETYPE_JPEG: imagejpeg($image_r, $imagePath); break;
            case IMAGETYPE_PNG: imagepng($image_r, $imagePath); break;
        }
        imagedestroy($image_o);
        imagedestroy($image_r);
        return true;
    }
}
