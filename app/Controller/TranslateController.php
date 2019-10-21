<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
class TranslateController extends AppController {
	public function index()
	{
		$values = $this->loadPo('C:\Tung\Job\Socialloft\3.0.1\app\Locale\eng\LC_MESSAGES\default.pot');
		$values1 = $this->loadPo('C:\Tung\Job\Socialloft\3.0.1\app\Locale\eng\LC_MESSAGES\default.po');
		
		$result = array();
		foreach ($values as $key =>$abc)
		{
			if (!isset($values1[$key]))
			{
				$result[$key] = $abc;
			}
		}

    	$path = APP.'tmp'.DS.'logs'.DS.'default.po';
    	$this->viewClass = 'Media';
    	$this->export($result, $path);
        // Download app/outside_webroot_dir/example.zip
        $params = array(
            'id'        => 'default.po',
            'name'      => 'default',
            'download'  => true,
            'extension' => 'po',
            'path'      => APP.'tmp'.DS.'logs'.DS
        );
        $this->set($params);
	}
	
	private function export($values,$path)
	{
		$output = '';
		$tmp = array();
    	foreach ($values as $message => $value)
    	{
    		if (!$message)
    			continue;
    			
    		$sentence = '';
    		$message = str_replace('"', '\"', $message);
    		if (is_array($value['']))
    		{
    			$value[''][1] = str_replace('"', '\"', $value[''][1]);
    			$sentence .= "msgid \"{$message}\"\n";
				$sentence .= "msgid_plural \"{$value[''][1]}\"\n";
				$sentence .= "msgstr[0] \"\"\n";
				$sentence .= "msgstr[1] \"\"\n\n";
    		}
    		else 
    		{	    		
	    		$sentence .= "msgid \"{$message}\"\n";
				$sentence .= "msgstr \"\"\n\n";
    		}
			$tmp[] = $sentence;
    	}
    	
    	$array_message = $tmp;
    	
    	foreach ($array_message as $header) {
			$output .= $header;
		}
		
		$File = new File($path);
		$File->write($output);
		$File->close();
	}
	
	public static function loadPo($filename) {
		if (!$file = fopen($filename, 'r')) {
			return false;
		}

		$type = 0;
		$translations = array();
		$translationKey = '';
		$translationContext = null;
		$plural = 0;
		$header = '';

		do {
			$line = trim(fgets($file));
			if ($line === '' || $line[0] === '#') {
				$translationContext = null;

				continue;
			}
			if (preg_match("/msgid[[:space:]]+\"(.+)\"$/i", $line, $regs)) {
				$type = 1;
				$translationKey = stripcslashes($regs[1]);
			} elseif (preg_match("/msgid[[:space:]]+\"\"$/i", $line, $regs)) {
				$type = 2;
				$translationKey = '';
			} elseif (preg_match("/msgctxt[[:space:]]+\"(.+)\"$/i", $line, $regs)) {
				$translationContext = $regs[1];
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && ($type == 1 || $type == 2 || $type == 3)) {
				$type = 3;
				$translationKey .= stripcslashes($regs[1]);
			} elseif (preg_match("/msgstr[[:space:]]+\"(.+)\"$/i", $line, $regs) && ($type == 1 || $type == 3) && $translationKey) {
				$translations[$translationKey][$translationContext] = stripcslashes($regs[1]);
				$type = 4;
			} elseif (preg_match("/msgstr[[:space:]]+\"\"$/i", $line, $regs) && ($type == 1 || $type == 3) && $translationKey) {
				$type = 4;
				$translations[$translationKey][$translationContext] = '';
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 4 && $translationKey) {
				$translations[$translationKey][$translationContext] .= stripcslashes($regs[1]);
			} elseif (preg_match("/msgid_plural[[:space:]]+\".*\"$/i", $line, $regs)) {
				$type = 6;
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 6 && $translationKey) {
				$type = 6;
			} elseif (preg_match("/msgstr\[(\d+)\][[:space:]]+\"(.+)\"$/i", $line, $regs) && ($type == 6 || $type == 7) && $translationKey) {
				$plural = $regs[1];
				$translations[$translationKey][$translationContext][$plural] = stripcslashes($regs[2]);
				$type = 7;
			} elseif (preg_match("/msgstr\[(\d+)\][[:space:]]+\"\"$/i", $line, $regs) && ($type == 6 || $type == 7) && $translationKey) {
				$plural = $regs[1];
				$translations[$translationKey][$translationContext][$plural] = '';
				$type = 7;
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 7 && $translationKey) {
				$translations[$translationKey][$translationContext][$plural] .= stripcslashes($regs[1]);
			} elseif (preg_match("/msgstr[[:space:]]+\"(.+)\"$/i", $line, $regs) && $type == 2 && !$translationKey) {
				$header .= stripcslashes($regs[1]);
				$type = 5;
			} elseif (preg_match("/msgstr[[:space:]]+\"\"$/i", $line, $regs) && !$translationKey) {
				$header = '';
				$type = 5;
			} elseif (preg_match("/^\"(.*)\"$/i", $line, $regs) && $type == 5) {
				$header .= stripcslashes($regs[1]);
			} else {
				unset($translations[$translationKey][$translationContext]);
				$type = 0;
				$translationKey = '';
				$translationContext = null;
				$plural = 0;
			}
		} while (!feof($file));
		fclose($file);

		$merge[''] = $header;
		return array_merge($merge, $translations);
	}
}