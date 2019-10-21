<?php
App::uses('ExceptionRenderer', 'Error');

class AppExceptionRenderer extends ExceptionRenderer {
    public function tokenHasExpired($error) {

        $url = $this->controller->request->here();
        $code = 500;
        $this->controller->response->statusCode($code);
        $this->controller->set(array(
            'isAndroidApp' =>$this->controller->request->is('androidApp'),
            'isIosApp'=>$this->controller->request->is('iosApp'),
            'code' => $code,
            'name' => h($error->getMessage()),
            'message' => h($error->getMessage()),
            'url' => h($url),
            'error' => $error,
            '_serialize' => array('code', 'name', 'message', 'url', 'error')
        ));

        $this->_outputMessage($this->template);
    }
}