<?php
class CheckComponent extends Component{
    public $plugin='Lophoc';
    function rename($name, $length, $minword = 3){
		$sub = '';
		$len = 0;
		foreach (explode(' ', $name) as $word)
		{
			$part = (($sub != '') ? ' ' : '') . $word;
			$sub .= $part;
			$len += strlen($part);
			if (strlen($word) > $minword && strlen($sub) >= $length)
			{
				break;
			}
		}
		return $sub . (($len < strlen($name)) ? '...' : '');
	}
}