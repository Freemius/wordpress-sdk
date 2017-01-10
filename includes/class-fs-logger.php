<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.0.3
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	class FS_Logger {
		private $_id;
		private $_on = false;
		private $_echo = false;
		private $_file_start = 0;

		private static $LOGGERS = array();
		private static $LOG = array();
		private static $CNT = 0;
		private static $_HOOKED_FOOTER = false;

		private function __construct( $id, $on = false, $echo = false ) {
			$this->_id = $id;

			$bt     = debug_backtrace();
			$caller = $bt[2];

			$this->_file_start = strpos( $caller['file'], 'plugins' ) + strlen( 'plugins/' );

			if ( $on ) {
				$this->on();
			}
			if ( $echo ) {
				$this->echo_on();
			}
		}

		/**
		 * @param string $id
		 * @param bool   $on
		 * @param bool   $echo
		 *
		 * @return FS_Logger
		 */
		public static function get_logger( $id, $on = false, $echo = false ) {
			$id = strtolower( $id );

			if ( ! isset( self::$LOGGERS[ $id ] ) ) {
				self::$LOGGERS[ $id ] = new FS_Logger( $id, $on, $echo );
			}

			return self::$LOGGERS[ $id ];
		}

		private static function _hook_footer() {
			if ( self::$_HOOKED_FOOTER ) {
				return;
			}

			if ( is_admin() ) {
				add_action( 'admin_footer', 'FS_Logger::dump', 100 );
			} else {
				add_action( 'wp_footer', 'FS_Logger::dump', 100 );
			}
		}

		function is_on() {
			return $this->_on;
		}

		function on() {
			$this->_on = true;

			self::_hook_footer();
		}

		function echo_on() {
			$this->on();

			$this->_echo = true;
		}

		function is_echo_on() {
			return $this->_echo;
		}

		function get_id() {
			return $this->_id;
		}

		function get_file() {
			return $this->_file_start;
		}

		private function _log( &$message, $type = 'log', $wrapper ) {
			if ( ! $this->is_on() ) {
				return;
			}

			$bt    = debug_backtrace();
			$depth = $wrapper ? 3 : 2;
			while ( $depth < count( $bt ) - 1 && 'eval' === $bt[ $depth ]['function'] ) {
				$depth ++;
			}

			$caller = $bt[ $depth ];

			$log = array_merge( $caller, array(
				'cnt'       => self::$CNT ++,
				'logger'    => $this,
				'timestamp' => microtime(true),
				'type'      => $type,
				'msg'       => $message,
			) );

			self::$LOG[] = $log;

			if ( $this->is_echo_on() ) {
				echo self::format_html( $log ) . "\n";
			}
		}

		function log( $message, $wrapper = false ) {
			$this->_log( $message, 'log', $wrapper );
		}

		function info( $message, $wrapper = false ) {
			$this->_log( $message, 'info', $wrapper );
		}

		function warn( $message, $wrapper = false ) {
			$this->_log( $message, 'warn', $wrapper );
		}

		function error( $message, $wrapper = false ) {
			$this->_log( $message, 'error', $wrapper );
		}

		/**
		 * Log API error.
		 *
		 * @author Vova Feldman (@svovaf)
		 * @since  1.2.1.5
		 *
		 * @param mixed $api_result
		 * @param bool  $wrapper
		 */
		function api_error( $api_result, $wrapper = false ) {
			$message = '';
			if ( is_object( $api_result ) && isset( $api_result->error ) ) {
				$message = $api_result->error->message;
			} else if ( is_object( $api_result ) ) {
				$message = var_export( $api_result, true );
			} else if ( is_string( $api_result ) ) {
				$message = $api_result;
			} else if ( empty( $api_result ) ) {
				$message = 'Empty API result.';
			}

			$message = 'API Error: ' . $message;

			$this->_log( $message, 'error', $wrapper );
		}

		function entrance( $message = '', $wrapper = false ) {
			$msg = 'Entrance' . ( empty( $message ) ? '' : ' > ' ) . $message;

			$this->_log( $msg, 'log', $wrapper );
		}

		function departure( $message = '', $wrapper = false ) {
			$msg = 'Departure' . ( empty( $message ) ? '' : ' > ' ) . $message;

			$this->_log( $msg, 'log', $wrapper );
		}

		private static function format( $log, $show_type = true ) {
			return '[' . str_pad( $log['cnt'], strlen( self::$CNT ), '0', STR_PAD_LEFT ) . '] [' . $log['logger']->_id . '] ' . ( $show_type ? '[' . $log['type'] . ']' : '' ) . $log['function'] . ' >> ' . $log['msg'] . ( isset( $log['file'] ) ? ' (' . substr( $log['file'], $log['logger']->_file_start ) . ' ' . $log['line'] . ') ' : '' ) . ' [' . $log['timestamp'] . ']';
		}

		private static function format_html( $log ) {
			return '<div style="font-size: 13px; font-family: monospace; color: #7da767; padding: 8px 3px; background: #000; border-bottom: 1px solid #555;">[' . $log['cnt'] . '] [' . $log['logger']->_id . '] [' . $log['type'] . '] <b><code style="color: #c4b1e0;">' . $log['function'] . '</code> >> <b style="color: #f59330;">' . esc_html($log['msg']) . '</b></b>' . ( isset( $log['file'] ) ? ' (' . substr( $log['file'], $log['logger']->_file_start ) . ' ' . $log['line'] . ')' : '' ) . ' [' . $log['timestamp'] . ']</div>';
		}

		static function dump() {
			?>
			<!-- BEGIN: Freemius PHP Console Log -->
			<script type="text/javascript">
				<?php
					foreach (self::$LOG as $log)
					{
						echo 'console.' . $log['type'] . '(' . json_encode(self::format($log, false)) . ')' . "\n";
					}
				?>
			</script>
			<!-- END: Freemius PHP Console Log -->
		<?php
		}

		static function get_log() {
			return self::$LOG;
		}
	}