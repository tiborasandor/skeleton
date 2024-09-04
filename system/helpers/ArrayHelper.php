<?php
declare(strict_types=1);

namespace system\helpers;

class ArrayHelper {

    /**
     * stdClass-ből csinál tömböt
     */
    public function stdToArray($stdclass):array {
        return $stdclass ? json_decode(json_encode($stdclass), true) : [];
    }

    /**
     * Tömb első eleméből kulcsokat csinál a tömb többi eleméhez
     */
    public function firstRowToKeys($array):array {
        return $this->rowToKeys($array, 1);
    }

    /**
     * Tömb egyik eleméből kulcsokat csinál a tömb többi eleméhez
     */
    public function rowToKeys($array, $row_number):array {
        $row = array_slice($array, $row_number, 1, true);

        $keys = array_map('trim', array_slice($array, $row_number-1, null, true));
        return array_map(function($value) use ($keys) {
            return array_combine($keys, $value);
        }, $array);
    }

    /**
     * Filterezi (üríri) az üres elemeket egy tömbben rekurzívan
     * Az $exceptions tömbbe megadható olyan érték amiket kikerüljön pl. (0, '0' false stb.)
     * Ha nincs megadva kivétel akkor az empty() funkció érvényesül minden elemre
     */
    public function filterRecursive(array $array, array $exceptions = null) {
        $array = array_map(function($value) use ($exceptions) {
            if (is_array($value)) {
                return $this->filterRecursive($value, $exceptions);
            } else {
                return trim($value);
            }
        }, $array);

        return array_filter($array, function($v, $k) use ($exceptions) {
            if (!empty($exceptions) && in_array($v, $exceptions, true)) {
                return true;
            } else {
                return !empty($v);
            }
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Kiveszi azokat a kulcsokat amelyek mindegyik sorban üresek
     */
    public function removeEmptySameRows(array $rows):array {
        // üres kulcsok kivétele, nem üres kulcsok megjegyzése ($keys)
        $keys = [];
        array_walk($rows, function(&$item) use (&$keys) {
            $item = array_filter($item, function($v, $k) use (&$keys) {
                if (empty($v) && $v !== 0 && $v !== '0') {
                    return false;
                } else {
                    $keys[$k] = null;
                    return true;
                }
            }, ARRAY_FILTER_USE_BOTH);
        });

        // nem üres kulcsok visszarakása azokba a tömbökbe ahol üresek voltak
        array_walk($rows, function(&$item) use ($keys) {
            $item += $keys;
            ksort($item);
        });

        return $rows;
    }

    /**
     * Keresés két dimenziós tömbben kulcs alapján
     */
    public function multiSearch(array $array, string $key, $searchValue):array {
        $searchValues = is_array($searchValue) ? $searchValue : [$searchValue];

        $result = [];
        foreach ($searchValues as $value) {
            // Kinyerjük az összes kulcsot és értéket.
            $keys = array_keys($array);
            $values = array_column($array, $key);
            $valueToKey = array_combine($keys, $values);

            // Kulcsok kiválasztása az érték alapján.
            $matchingKeys = array_keys($valueToKey, $value, true);

            // Kiválasztjuk az eredményt a kulcsok alapján.
            $matchingItems = array_intersect_key($array, array_flip($matchingKeys));

            // Hozzáadjuk az eredményeket a végső tömbhöz.
            $result += $matchingItems;
        }

        return $result;
    }

    public function diff(array $array1, array $array2):array {
        return [
            'normal' => array_filter(array_diff($array1, $array2)),
            'reverse' => array_filter(array_diff($array2, $array1))
        ];
    }

    public function diffKeys(array $array1, array $array2):array {
        return [
            'normal' => array_keys(array_filter(array_diff_key($array1, $array2))),
            'reverse' => array_keys(array_filter(array_diff_key($array2, $array1)))
        ];
    }

}

?>