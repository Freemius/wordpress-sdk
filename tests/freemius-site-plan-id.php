<?php
/**
 * Regression tests using the SDK's real entity inheritance chain.
 * Run with: php tests/freemius-site-plan-id.php
 * No WordPress bootstrap, account connection, or network requests required.
 *
 * CLI diagnostics contain no HTML and do not use WordPress escaping functions.
 * phpcs:disable WordPress.Security.EscapeOutput
 */
if ( 'cli' !== PHP_SAPI ) {
    exit( 1 );
}

if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'WP_FS__SCRIPT_START_TIME' ) ) {
    define( 'WP_FS__SCRIPT_START_TIME', 1700000000 );
}

require_once dirname( __DIR__ ) . '/includes/entities/class-fs-entity.php';
require_once dirname( __DIR__ ) . '/includes/entities/class-fs-scope-entity.php';
require_once dirname( __DIR__ ) . '/includes/entities/class-fs-site.php';

set_error_handler( function ( $severity, $message, $file, $line ) {
    throw new ErrorException( $message, 0, $severity, $file, $line );
} );

$tests = array(
    'missing plan ID remains null without a warning' => function () {
        $site = new FS_Site( (object) array( 'id' => 123 ) );
        if ( null !== $site->plan_id ) {
            throw new Exception( 'Missing plan ID must remain null.' );
        }
    },
    'present plan IDs retain their values and types' => function () {
        foreach ( array( null, 0, '0', 42, '42' ) as $plan_id ) {
            $site = new FS_Site( (object) array( 'plan_id' => $plan_id ) );
            if ( $plan_id !== $site->plan_id ) {
                throw new Exception( 'Present plan ID changed: ' . var_export( $plan_id, true ) );
            }
        }
    },
    'inherited site and licensing fields are preserved' => function () {
        $data = (object) array(
            'id' => 123,
            'created' => '2023-01-01 00:00:00',
            'updated' => '2023-01-02 00:00:00',
            'public_key' => 'fixture-public',
            'secret_key' => 'fixture-only-not-a-credential',
            'url' => 'https://example.invalid/',
            'user_id' => 234,
            'plugin_id' => 374,
            'plan_id' => 42,
            'license_id' => 567,
            'is_premium' => true,
            'is_active' => false,
            'is_uninstalled' => true,
            'is_disconnected' => true,
        );
        foreach ( array( true, false ) as $has_plan ) {
            if ( ! $has_plan ) {
                unset( $data->plan_id );
            }
            $site = new FS_Site( $data );
            foreach ( get_object_vars( $data ) as $key => $value ) {
                if ( $value !== $site->{$key} ) {
                    throw new Exception( 'Inherited field changed: ' . $key );
                }
            }
            if ( ! $has_plan && null !== $site->plan_id ) {
                throw new Exception( 'Missing plan ID must not acquire a value.' );
            }
            $copy = new FS_Site( $site );
            if ( get_object_vars( $site ) !== get_object_vars( $copy ) ) {
                throw new Exception( 'FS_Site copy changed entity fields.' );
            }
        }
    },
    'trial behavior is independent of a missing plan ID' => function () {
        foreach ( array( false, true ) as $active ) {
            $site = new FS_Site( (object) array(
                'trial_plan_id' => 42,
                'trial_ends' => $active ? '2099-01-01 00:00:00' : '2000-01-01 00:00:00',
            ) );
            if ( $active !== $site->is_trial() || ! $site->is_trial_utilized() || null !== $site->plan_id ) {
                throw new Exception( 'Trial status changed.' );
            }
        }
        $site = new FS_Site( new stdClass() );
        if ( $site->is_trial() || $site->is_trial_utilized() ) {
            throw new Exception( 'Empty site must not acquire a trial.' );
        }
    },
    'default construction and disconnected normalization are unchanged' => function () {
        foreach ( array( false, null, new stdClass(), (object) array( 'is_disconnected' => 'yes' ) ) as $data ) {
            $site = new FS_Site( $data );
            if ( null !== $site->plan_id || false !== $site->is_disconnected || true !== $site->is_active ) {
                throw new Exception( 'Default or disconnected state changed.' );
            }
        }
    },
);

$failures = 0;
foreach ( $tests as $name => $test ) {
    try {
        $test();
        print "PASS: $name\n";
    } catch ( Exception $error ) {
        $failures++;
        print "FAIL: $name: " . $error->getMessage() . "\n";
    }
}
restore_error_handler();
print count( $tests ) . " tests, $failures failures.\n";
exit( $failures ? 1 : 0 );
