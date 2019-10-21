<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ConvertShell
 *
 * @author sangpq
 */
class ConvertShell extends Shell {

    protected $_ffmpeg = 'ffmpeg';
    protected $_mencoder = 'mencoder';
    public $uses = array('Video.Video');

    //put your code here
    public function main() {
        // disable render layout
        $aVideo = $this->Video->find('all', array('condition' => array(
                'Video.in_process' => true
        )));

        if (empty($aVideo)) {
            exit;
        }

        $default_lib = Configure::read('Video.video_setting_lib_converting');
        foreach ($aVideo as $video) {
            switch ($default_lib) {
                case $this->_mencoder:
                    $this->Video->convert_mencoder($video);
                    break;
                case $this->_ffmpeg:
                default:
                    $this->Video->convert_ffmpeg($video);
                    break;
            }
        }
    }

}
