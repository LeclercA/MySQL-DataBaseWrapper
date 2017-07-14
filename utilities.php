<?php

class utilities {

    public function isAssoc(array $arr) {
        if (array() === $arr) {
            return false;
        }
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public function rotateArray($array) {
        $newArray = [];
        foreach ($array as $reverseKey => $reverseValue) {
            foreach ($reverseValue as $reverseSubKey => $reverseSubValue) {
                $newArray[$reverseSubKey][$reverseKey] = $reverseSubValue;
            }
        }
        return $newArray;
    }

    public function checkForSubArray($array) {
        return is_array(reset($array));
    }

    public function sanitizeInput($input, $type) {
        
    }

    public function cleanArray($arrayToClean, $arrayToCheckKeysFor) {
        if (!$this->isAssoc($arrayToCheckKeysFor)) {
            $arrayToCheckKeysFor = array_flip($arrayToCheckKeysFor);
        }
        foreach ($arrayToClean as $cleanKey => $cleanValue) {
            if (!array_key_exists($cleanKey, $arrayToCheckKeysFor)) {
                unset($arrayToClean[$cleanKey]);
            }
        }
        return $arrayToClean;
    }

}
