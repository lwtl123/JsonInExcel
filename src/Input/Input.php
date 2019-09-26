<?php
namespace JsonInExcel\Input;

    class Input {

        public $data;

        public function read ($file) {
            if (!file_exists($file)) {
                echo "Datei konnte nicht gefunden werden.";
                exit;
            }
            if (!fopen($file, "r")) {
                echo "Datei konnte nicht geöffnet werden.";
                exit;
            }
            $this->data = file_get_contents($file);
            $this->data = json_decode($this->data, true);
            return $this->data;
        }

    }





