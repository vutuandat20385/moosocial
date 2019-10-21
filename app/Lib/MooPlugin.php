<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
interface MooPlugin
{
    public function install();
    public function uninstall();
    public function settingGuide();
    public function menu();
}