<?php

    /**
     * @param mixed $object
     *
     * @return array<string, mixed>
     */
    function fs_get_object_public_vars($object) {
        return [];
    }

    class FS_Entity {

        /**
         * @var int
         */
        public $id;

        /**
         * @var string
         */
        public $updated;

        /**
         * @var string
         */
        public $created;

        /**
         * FS_Entity constructor.
         * @param mixed $entity
         */
        function __construct($entity = false) {
            // Implementation here
        }

        /**
         * @return string
         */
        static function get_type() {
            // Implementation here
            return '';
        }

        /**
         * @param mixed $entity1
         * @param mixed $entity2
         * @return bool
         */
        static function equals($entity1, $entity2) {
            // Implementation here
            return false;
        }

        /**
         * @var bool
         */
        private $_is_updated = false;

        /**
         * @param string $key
         * @param bool $val
         * @return bool
         */
        function update($key, $val = false) {
            return false;
        }

        /**
         * @return bool
         */
        function is_updated() {
            // Implementation here
            return $this->_is_updated;
        }

        /**
         * @param int $id
         * @return bool
         */
        static function is_valid_id($id) {
            // Implementation here
            return false;
        }

        /**
         * @return string
         */
        public static function get_class_name() {
            // Implementation here
            return '';
        }
    }
