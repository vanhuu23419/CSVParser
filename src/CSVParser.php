<?php
/**
 * Owner: huu.nv
 * Parse CSV into 2D array. Properly handle enclosed multiline data.
 */
class CSVParser 
{
    public function __construct()
    {
        if (!defined('CSV_EOL')) {
            define('CSV_EOL', chr(13).chr(10));
        }
    }

    protected function removeEndOfLine(string $str) {
        $len = strlen($str);
        if ($len >= 2 && ($str[$len - 2].$str[$len - 1] === CSV_EOL)) {
            return substr($str, 0, $len - 2);
        }
        if ($len >= 1 && ($str[$len - 1] === chr(10) || $str[$len - 1] === chr(13))) {
            return substr($str, 0, $len - 1);
        }
        return $str;
    }

    protected function isEndOfLine(string $str) {
        $len = strlen($str);
        if ($len >= 1 && (($str[$len - 1] === chr(10)) || ($str[$len - 1] === chr(13)))) {
            return true;
        }
        if ($len >= 2 && ($str[$len - 2].$str[$len - 1] === CSV_EOL)) {
            return true;
        }
        return false;
    }

    protected function willEnclosed(string $str) {
        $str = $this->removeEndOfLine($str);
        $i = strlen($str) - 1;
		$enclosed = false;
        while($i >= 0) {
            if ($str[$i] != '"') {
                break;
            }
            else {
                $enclosed = !$enclosed;
            }
            $i -= 1;
        }
        return $enclosed;
    }

    protected function isEnclosing(string $str) {
        if (strlen($str) > 0 && $str[0] === '"') {
            return true;
        }
        return false;
    }
    
    public function parse(string $uri, callable $each = null) {
        $handle = fopen($uri, 'r');
        $result = [];
        $row = [];
        $data = '';
        $enclosure = false; 
        $count = 1;

        while(($line = fgets($handle)) !== false) {
            $contents = explode(',', $line);
            for ($i = 0, $total = count($contents); $i < $total; ++$i) {
                $str = $contents[$i];
                if ($enclosure === false) {
                    // Need to open enclosure. Remove '"' & re-process this data with enclosure opened
                    if ($this->isEnclosing($str)) {  
                        $enclosure = true;
                        $contents[$i] = substr($str, 1);
                        $i -= 1;
                        continue;
                    }
                    // Meet the end of line. Remove \r\n
                    if ($this->isEndOfLine($str)) {
                        $str = $this->removeEndOfLine($str);      
                    }
                    // Finished building column data. Add column to rows
                    $data .= $str;
                    array_push($row, str_replace('""', '"', $data));
                    $data = '';
                }
                else {
                    // Need to close enclosure. Remove '"' & re-process this data with enclosure closed
                    if ($this->willEnclosed($str)) {
                        if ($this->isEndOfLine($str)) {
                            $contents[$i] = $str = $this->removeEndOfLine($str); 
                        }
                        $contents[$i] = substr($str, 0, strlen($str) - 1); 
                        $enclosure = false;
                        $i -= 1;
                        continue;
                    }
                    else {
                        if ($this->isEndOfLine($str) === false) {
                            $data .= $str . ',';
                        }
                        else {
                            $data .= $str;
                        }
                    }
                }
            }  
            // Finished building row. Add to result
            if ($enclosure === false) {
                if ($each != null) {
                    $tmp = $each($row, $count);
                    if ($tmp !== false) {
                        array_push($result, $tmp);
                    }
                }else {
                    array_push($result, $row);
                }
                $row = [];
                $data = '';
            }
            //
            $count++;
        }

        // Add the remaining data (due to not closed enclosure)
        if ($enclosure === true) {
            array_push($row, '"' . str_replace('""', '"', $data));
        }
        if (!empty($row)) {
            if ($each != null) {
                $tmp = $each($row, $count);
                if ($tmp !== false) {
                    array_push($result, $tmp);
                }
            }else {
                array_push($result, $row);
            }
        }
        return $result;
    }
}