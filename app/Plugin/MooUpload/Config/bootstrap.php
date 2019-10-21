<?php

App::uses('MooUploadListener','MooUpload.Lib');
CakeEventManager::instance()->attach(new MooUploadListener());