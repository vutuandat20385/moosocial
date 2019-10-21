<?php
/*
 * Copyright (c) SocialLOFT LLC
 * mooSocial - The Web 2.0 Social Network Software
 * @website: http://www.moosocial.com
 * @author: mooSocial
 * @license: https://moosocial.com/license/
 */
App::uses('TimeHelper', 'View/Helper');

final class AppTimeHelper extends TimeHelper
{
    public static $niceFormat = '%a, %b %eS %Y, %H:%M';

    public static $wordFormat = 'j/n/y';

    public static $niceShortFormat = '%B %d, %H:%M';

    public static $wordAccuracy = array(
        'year' => "day",
        'month' => "day",
        'week' => "day",
        'day' => "hour",
        'hour' => "minute",
        'minute' => "minute",
        'second' => "second",
    );

    public static $wordEnd = '+1 month';

    protected static $_time = null;

    public function timeAgoInWords($dateTime, $options = array())
    {
        $element = null;

        if (!empty($options['element'])) {
            $element = array(
                'tag' => 'span',
                'class' => 'time-ago-in-words',
                'title' => $dateTime
            );

            if (is_array($options['element'])) {
                $element = array_merge($element, $options['element']);
            } else {
                $element['tag'] = $options['element'];
            }
            unset($options['element']);
        }
        $relativeDate = $this->timeUtility($dateTime, $options);

        if ($element) {
            $relativeDate = sprintf(
                '<%s%s>%s</%s>',
                $element['tag'],
                $this->_parseAttributes($element, array('tag')),
                $relativeDate,
                $element['tag']
            );
        }
        return $relativeDate;
    }

    public function timeUtility($dateTime, $options = array())
    {
        $timezone = null;
        $format = self::$wordFormat;
        $end = self::$wordEnd;
        $relativeString = __( '%s ago');
        $absoluteString = __( 'on %s');
        $accuracy = self::$wordAccuracy;

        if (is_array($options)) {
            if (isset($options['timezone'])) {
                $timezone = $options['timezone'];
            } elseif (isset($options['userOffset'])) {
                $timezone = $options['userOffset'];
            }

            if (isset($options['accuracy'])) {
                if (is_array($options['accuracy'])) {
                    $accuracy = array_merge($accuracy, $options['accuracy']);
                } else {
                    foreach ($accuracy as $key => $level) {
                        $accuracy[$key] = $options['accuracy'];
                    }
                }
            }

            if (isset($options['format'])) {
                $format = $options['format'];
            }
            if (isset($options['end'])) {
                $end = $options['end'];
            }
            if (isset($options['relativeString'])) {
                $relativeString = $options['relativeString'];
                unset($options['relativeString']);
            }
            if (isset($options['absoluteString'])) {
                $absoluteString = $options['absoluteString'];
                unset($options['absoluteString']);
            }
            unset($options['end'], $options['format']);
        } else {
            $format = $options;
        }

        $now = self::fromString(time(), $timezone);
        $inSeconds = self::fromString($dateTime, $timezone);
        $backwards = ($inSeconds > $now);

        $futureTime = $now;
        $pastTime = $inSeconds;
        if ($backwards) {
            $futureTime = $inSeconds;
            $pastTime = $now;
        }
        $diff = $futureTime - $pastTime;

        if (!$diff) {
            return __( 'just now', 'just now');
        }

        if ($diff > abs($now - self::fromString($end))) {
           // $format = '%B %d at %I:%M%P';
            return sprintf($absoluteString, strftime($format, $inSeconds));
        }

        // If more than a week, then take into account the length of months
        if ($diff >= 604800) {
            list($future['H'], $future['i'], $future['s'], $future['d'], $future['m'], $future['Y']) = explode('/', date('H/i/s/d/m/Y', $futureTime));

            list($past['H'], $past['i'], $past['s'], $past['d'], $past['m'], $past['Y']) = explode('/', date('H/i/s/d/m/Y', $pastTime));
            $years = $months = $weeks = $days = $hours = $minutes = $seconds = 0;

            $years = $future['Y'] - $past['Y'];
            $months = $future['m'] + ((12 * $years) - $past['m']);

            if ($months >= 12) {
                $years = floor($months / 12);
                $months = $months - ($years * 12);
            }
            if ($future['m'] < $past['m'] && $future['Y'] - $past['Y'] === 1) {
                $years--;
            }

            if ($future['d'] >= $past['d']) {
                $days = $future['d'] - $past['d'];
            } else {
                $daysInPastMonth = date('t', $pastTime);
                $daysInFutureMonth = date('t', mktime(0, 0, 0, $future['m'] - 1, 1, $future['Y']));

                if (!$backwards) {
                    $days = ($daysInPastMonth - $past['d']) + $future['d'];
                } else {
                    $days = ($daysInFutureMonth - $past['d']) + $future['d'];
                }

                if ($future['m'] != $past['m']) {
                    $months--;
                }
            }

            if (!$months && $years >= 1 && $diff < ($years * 31536000)) {
                $months = 11;
                $years--;
            }

            if ($months >= 12) {
                $years = $years + 1;
                $months = $months - 12;
            }

            if ($days >= 7) {
                $weeks = floor($days / 7);
                $days = $days - ($weeks * 7);
            }
        } else {
            $years = $months = $weeks = 0;
            $days = floor($diff / 86400);

            $diff = $diff - ($days * 86400);

            $hours = floor($diff / 3600);
            $diff = $diff - ($hours * 3600);

            $minutes = floor($diff / 60);
            $diff = $diff - ($minutes * 60);
            $seconds = $diff;
        }

        $fWord = $accuracy['second'];
        if ($years > 0) {
            $fWord = $accuracy['year'];
        } elseif (abs($months) > 0) {
            $fWord = $accuracy['month'];
        } elseif (abs($weeks) > 0) {
            $fWord = $accuracy['week'];
        } elseif (abs($days) > 0) {
            $fWord = $accuracy['day'];
        } elseif (abs($hours) > 0) {
            $fWord = $accuracy['hour'];
        } elseif (abs($minutes) > 0) {
            $fWord = $accuracy['minute'];
        }

        $fNum = str_replace(array('year', 'month', 'week', 'day', 'hour', 'minute', 'second'), array(1, 2, 3, 4, 5, 6, 7), $fWord);

        $relativeDate = '';
        if ($fNum >= 1 && $years > 0) {
            $relativeDate .= ($relativeDate ? ', ' : '') . __n( '%d year', '%d years', $years, $years);
        }
        if ($fNum >= 2 && $months > 0) {
            $relativeDate .= ($relativeDate ? ', ' : '') . __n( '%d month', '%d months', $months, $months);
        }
        if ($fNum >= 3 && $weeks > 0) {
            $relativeDate .= ($relativeDate ? ', ' : '') . __n( '%d week', '%d weeks', $weeks, $weeks);
        }
        if ($fNum >= 4 && $days > 0) {
            $relativeDate .= ($relativeDate ? ', ' : '') . __n( '%d day', '%d days', $days, $days);
        }
        if ($fNum >= 5 && $hours > 0) {
            $relativeDate .= ($relativeDate ? ', ' : '') . __n( '%d hour', '%d hours', $hours, $hours);
        }
        if ($fNum >= 6 && $minutes > 0) {
            $relativeDate .= ($relativeDate ? ', ' : '') . __n( '%d minute', '%d minutes', $minutes, $minutes);
        }
        if ($fNum >= 7 && $seconds > 0) {
            $relativeDate .= ($relativeDate ? ', ' : '') . __n( '%d second', '%d seconds', $seconds, $seconds);
        }

        // When time has passed
        if (!$backwards && $relativeDate) {
            return sprintf($relativeString, $relativeDate);
        }
        if (!$backwards) {
            $aboutAgo = array(
                'second' => __( 'about a second ago'),
                'minute' => __( 'about a minute ago'),
                'hour' => __( 'about an hour ago'),
                'day' => __( 'about a day ago'),
                'week' => __( 'about a week ago'),
                'year' => __( 'about a year ago')
            );

            return $aboutAgo[$fWord];
        }

        // When time is to come
        if (!$relativeDate) {
            $aboutIn = array(
                'second' => __( 'in about a second'),
                'minute' => __( 'in about a minute'),
                'hour' => __( 'in about an hour'),
                'day' => __( 'in about a day'),
                'week' => __( 'in about a week'),
                'year' => __( 'in about a year')
            );

            return $aboutIn[$fWord];
        }

        return $relativeDate;
    }
    
    // overwrite default format function CakeTime for translation
    public function format($dateTime, $format = null, $default = false, $timezone = null) {
        
        if (empty($format)){
            $format = Configure::read('core.date_format');
        }
        
        if (empty($timezone)){
            $timezone = (!is_numeric(Configure::read('core.timezone'))) ? Configure::read('core.timezone') : 'UTC';
        }
        
        $inSeconds = self::fromString($dateTime, $timezone);
        
        return strftime($format, $inSeconds);
    }
    
    // format time for Event plugin, it not base on timezone so we will skip it.
    public function event_format($dateTime, $format = null){
        if (empty($format)){
            $format = Configure::read('core.date_format_no_time');
        }
        
        $inSeconds = self::fromString($dateTime);
        
        return strftime($format, $inSeconds);
    }
    
    public function format_date($dateTime, $timezone = null) {
   
    	if (empty($timezone)){
    		$timezone = (!is_numeric(Configure::read('core.timezone'))) ? Configure::read('core.timezone') : 'UTC';
    	}
    	
    	$format = Configure::read('core.date_format_no_time');
    	
    	$inSeconds = self::fromString($dateTime, $timezone);
    	
    	return strftime($format, $inSeconds);
    }
    
    public function format_date_time($dateTime, $timezone = null) {
    	
    	if (empty($timezone)){
    		$timezone = (!is_numeric(Configure::read('core.timezone'))) ? Configure::read('core.timezone') : 'UTC';
    	}
    	
    	$format = Configure::read('core.date_format_time');
    	
    	$inSeconds = self::fromString($dateTime, $timezone);
    	
    	return strftime($format, $inSeconds);
    }
}
