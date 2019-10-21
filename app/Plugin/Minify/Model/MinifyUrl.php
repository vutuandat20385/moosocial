<?php 
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('Model', 'Model');
class MinifyUrl extends Model{
    public function getMinify($name,$paths = null)
    {
        $minify = Cache::read('minify_'.$name);
        if (!$minify)
        {
            $minify = $this->findByName($name);
            if (!$minify && $paths)
            {
                $this->save(array(
                    'name' => $name,
                    'url' => json_encode($paths)
                ));
                $minify = $this->read();
            }

            if ($minify)
                Cache::write('minify_'.$name,$minify);
        }

        return $minify;
    }
}