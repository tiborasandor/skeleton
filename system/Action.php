<?php
declare(strict_types=1);

namespace system;

class Action extends Core {

    public function throwMessage(\Throwable $th): string {
        return $th->getMessage().' (file: '.$th->getFile().' line: '.$th->getLine().')';
    }
}
?>