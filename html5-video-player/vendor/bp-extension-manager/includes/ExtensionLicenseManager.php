<?php
/**
 * License AJAX endpoints (Freemius convention) + Max-Plan suppression.
 *
 * One AJAX action per host, dispatched by `op`.
 *
 * @package BPEM
 */

namespace BPEM;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles license activate / status / deactivate for a host's extensions, and
 * silences Freemius connect prompts when the host holds the Max Plan.
 */
final class ExtensionLicenseManager {

	/**
	 * Host manager.
	 *
	 * @var Manager
	 */
	private $manager;

	/**
	 * Constructor.
	 *
	 * @param Manager $manager Host manager.
	 */
	public function __construct( Manager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Register AJAX + Max-Plan suppression hooks.
	 */
	public function register_hooks(): void {
		add_action( "wp_ajax_bpem_{$this->manager->get_slug()}_license", array( $this, 'handle' ) );

		// Max Plan: skip Freemius opt-in prompts for this host's extensions, before Freemius (pri 10).
		add_action( 'admin_init', array( $this, 'auto_skip_extension_connections' ), 5 );
	}

	/**
	 * AJAX dispatcher. Request: { _wpnonce, ext_id, op, license_key? }.
	 */
	public function handle(): void {
		$slug = $this->manager->get_slug();

		check_ajax_referer( "bpem_{$slug}_admin" );

		if ( ! current_user_can( (string) $this->manager->get_config( 'capability', 'manage_options' ) ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'bp-extension-manager' ) ), 403 );
		}

		$ext_id = isset( $_POST['ext_id'] ) ? sanitize_key( wp_unslash( $_POST['ext_id'] ) ) : '';
		$op      = isset( $_POST['op'] ) ? sanitize_key( wp_unslash( $_POST['op'] ) ) : '';

		$ext = $this->manager->get_extensions()[ $ext_id ] ?? null;
		if ( ! $ext ) {
			wp_send_json_error( array( 'message' => __( 'Unknown extension.', 'bp-extension-manager' ) ), 404 );
		}

		// Max Plan: report licensed without contacting Freemius.
		if ( $this->manager->is_max_plan() ) {
			wp_send_json_success(
				array(
					'status'  => 'licensed',
					'message' => __( 'Included with your plan.', 'bp-extension-manager' ),
				)
			);
		}

		$fs = $ext->get_freemius();
		if ( ! $fs ) {
			wp_send_json_success(
				array(
					'status'  => 'licensed',
					'message' => __( 'This extension is free.', 'bp-extension-manager' ),
				)
			);
		}

		switch ( $op ) {
			case 'activate':
				$this->op_activate( $fs );
				break;
			case 'deactivate':
				$this->op_deactivate( $fs );
				break;
			case 'sync':
				$this->op_sync( $fs );
				break;
			case 'status':
			default:
				$this->op_status( $fs );
				break;
		}
	}

	/* ---------------------------------------------------------------------- */

	/**
	 * Activate a license key against the extension's Freemius instance.
	 *
	 * @param object $fs Freemius instance.
	 */
	private function op_activate( $fs ): void {
		// Nonce + capability verified in handle() before dispatch.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$key = isset( $_POST['license_key'] ) ? sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) : '';
		if ( '' === $key ) {
			wp_send_json_error( array( 'message' => __( 'License key required.', 'bp-extension-manager' ) ), 400 );
		}

		try {
			// Freemius opt-in-with-license. Method name varies by SDK; some are
			// private/protected, so invoke via reflection.
			if ( method_exists( $fs, 'opt_in' ) ) {
				$this->call_protected( $fs, 'opt_in', array( false, false, false, $key ) );
			} elseif ( method_exists( $fs, 'activate_migrated_license' ) ) {
				$this->call_protected( $fs, 'activate_migrated_license', array( $key ) );
			}
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
		}

		$this->op_status( $fs );
	}

	/**
	 * Deactivate the current license.
	 *
	 * @param object $fs Freemius instance.
	 */
	private function op_deactivate( $fs ): void {
		try {
			// `_deactivate_license` is the SDK call that actually releases the
			// license on this site. It's private — invoke via reflection.
			if ( method_exists( $fs, '_deactivate_license' ) ) {
				$this->call_protected( $fs, '_deactivate_license', array( false ) ); // show_notice = false
			} elseif ( method_exists( $fs, 'deactivate_premium_only_addon_without_license' ) ) {
				$this->call_protected( $fs, 'deactivate_premium_only_addon_without_license' );
			}
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
		}

		// Report the authoritative post-deactivation state rather than assuming.
		$this->op_status( $fs );
	}

	/**
	 * Pull the latest license/subscription from the buyer's Freemius account.
	 *
	 * Used right after an in-context purchase: the checkout ties the new license
	 * to the user's Freemius account, and a sync activates it on this site without
	 * needing the raw key client-side. Best-effort — the authoritative status is
	 * reported regardless of whether the sync call succeeds.
	 *
	 * @param object $fs Freemius instance.
	 */
	private function op_sync( $fs ): void {
		try {
			// `_sync_license` refreshes license + plan from the server. It's private
			// in the SDK — invoke via reflection. First arg is the "background" flag.
			if ( method_exists( $fs, '_sync_license' ) ) {
				$this->call_protected( $fs, '_sync_license', array( true ) );
			}
		} catch ( \Throwable $e ) {
			// Non-fatal: fall through and report whatever state we can read.
			unset( $e );
		}

		$this->op_status( $fs );
	}

	/**
	 * Invoke a Freemius method regardless of visibility (many are private/protected).
	 *
	 * @param object  $object Target instance.
	 * @param string  $method Method name.
	 * @param mixed[] $args   Arguments.
	 * @return mixed
	 */
	private function call_protected( $object, string $method, array $args = array() ) {
		$ref = new \ReflectionMethod( $object, $method );
		if ( $ref->getNumberOfRequiredParameters() > count( $args ) ) {
			throw new \RuntimeException(
				sprintf( 'Incompatible SDK signature for %s: requires %d args, got %d.', $method, $ref->getNumberOfRequiredParameters(), count( $args ) )
			);
		}
		if ( ! $ref->isPublic() ) {
			$ref->setAccessible( true );
		}
		return $ref->invokeArgs( $object, $args );
	}

	/**
	 * Report the current license status.
	 *
	 * @param object $fs Freemius instance.
	 */
	private function op_status( $fs ): void {
		try {
			$can_use = method_exists( $fs, 'can_use_premium_code' ) && $fs->can_use_premium_code();
			$expired = method_exists( $fs, 'is_expired' ) && $fs->is_expired();
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
		}

		$status = $can_use ? 'licensed' : ( $expired ? 'expired' : 'unlicensed' );
		wp_send_json_success(
			array(
				'status'  => $status,
				'message' => '',
			)
		);
	}

	/**
	 * On Max Plan, skip Freemius connection prompts for every registered extension.
	 *
	 * Runs at admin_init priority 5 (before Freemius' own priority-10 hooks).
	 */
	public function auto_skip_extension_connections(): void {
		if ( ! $this->manager->is_max_plan() ) {
			return;
		}

		foreach ( $this->manager->get_extensions() as $ext ) {
			$fs = $ext->get_freemius();
			if ( ! $fs ) {
				continue;
			}
			try {
				$registered = method_exists( $fs, 'is_registered' ) && $fs->is_registered();
				$anonymous  = method_exists( $fs, 'is_anonymous' ) && $fs->is_anonymous();
				if ( ! $registered && ! $anonymous && method_exists( $fs, 'skip_connection' ) ) {
					$fs->skip_connection();
				}
			} catch ( \Throwable $e ) {
				// Non-fatal — a flaky SDK call must not break admin_init.
				continue;
			}
		}
	}
}
