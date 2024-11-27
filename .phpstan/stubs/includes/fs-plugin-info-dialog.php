<?php
    class FS_Plugin_Plan extends FS_Entity {

        /**
         * @var int
         */
        public $plugin_id;

        /**
         * @var string
         */
        public $name;

        /**
         * @var string
         */
        public $title;

        /**
         * @var string
         */
        public $description;

        /**
         * @var bool
         */
        public $is_free_localhost;

        /**
         * @var bool
         */
        public $is_block_features;

        /**
         * @var string
         */
        public $license_type;

        /**
         * @var bool
         */
        public $is_https_support;

        /**
         * @var int
         */
        public $trial_period;

        /**
         * @var bool
         */
        public $is_require_subscription;

        /**
         * @var string
         */
        public $support_kb;

        /**
         * @var string
         */
        public $support_forum;

        /**
         * @var string
         */
        public $support_email;

        /**
         * @var string
         */
        public $support_phone;

        /**
         * @var string
         */
        public $support_skype;

        /**
         * @var bool
         */
        public $is_success_manager;

        /**
         * @var bool
         */
        public $is_featured;

        /**
         * @var bool
         */
        public $is_hidden;

        /**
         * @var array<string, mixed>
         */
        public $pricing;

        /**
         * @var array<string, mixed>
         */
        public $features;

        /**
         * FS_Plugin_Plan constructor.
         * @param object|bool $plan
         */
        public function __construct($plan = false) {}

        /**
         * @return string
         */
        public static function get_type() {}

        /**
         * @return bool
         */
        public function is_free() {}

        /**
         * @return bool
         */
        public function has_technical_support() {}

        /**
         * @return bool
         */
        public function has_trial() {}
    }
