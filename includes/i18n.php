<?php
	/**
	 * @package     Freemius
	 * @copyright   Copyright (c) 2015, Freemius, Inc.
	 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 * @since       1.1.4
	 */

	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * All strings can now be overridden.
	 *
	 * For example, if we want to override:
	 *      'you-are-step-away' => 'You are just one step away - %s',
	 *
	 * We can use the filter:
	 *      fs_override_i18n( array(
	 *          'opt-in-connect' => __( "Yes - I'm in!", '{your-text_domain}' ),
	 *          'skip'           => __( 'Not today', '{your-text_domain}' ),
	 *      ), '{plugin_slug}' );
	 *
	 * Or with the Freemius instance:
	 *
	 *      my_freemius->override_i18n( array(
	 *          'opt-in-connect' => __( "Yes - I'm in!", '{your-text_domain}' ),
	 *          'skip'           => __( 'Not today', '{your-text_domain}' ),
	 *      );
	 */
	global $fs_text;

	$fs_text = array(
		'account'                                  => __( 'Account', 'freemius' ),
		'addon'                                    => __( 'Add On', 'freemius' ),
		'contact-us'                               => __( 'Contact Us', 'freemius' ),
		'change-ownership'                         => __( 'Change Ownership', 'freemius' ),
		'support'                                  => __( 'Support', 'freemius' ),
		'support-forum'                            => __( 'Support Forum', 'freemius' ),
		'add-ons'                                  => __( 'Add Ons', 'freemius' ),
		'upgrade'                                  => _x( 'Upgrade', 'verb', 'freemius' ),
		'awesome'                                  => __( 'Awesome', 'freemius' ),
		'pricing'                                  => _x( 'Pricing', 'noun', 'freemius' ),
		'price'                                    => _x( 'Price', 'noun', 'freemius' ),
		'unlimited-updates'                        => __( 'Unlimited Updates', 'freemius' ),
		'downgrade'                                => _x( 'Downgrade', 'verb', 'freemius' ),
		'cancel-trial'                             => __( 'Cancel Trial', 'freemius' ),
		'free-trial'                               => __( 'Free Trial', 'freemius' ),
		'start-free-x'                             => /* translators: %s: Trial name */
		                                              __( 'Start my free %s', 'freemius' ),
		'no-commitment-x'                          => /* translators: %s: Plan name (e.g. professional) */
		                                              __( 'No commitment for %s - cancel anytime', 'freemius' ),
		'after-x-pay-as-little-y'                  => /* translators: 1: Trial name 2: Price */
		                                              __( 'After your free %s, pay as little as %s', 'freemius' ),
		'details'                                  => __( 'Details', 'freemius' ),
		'account-details'                          => __( 'Account Details', 'freemius' ),
		'delete'                                   => _x( 'Delete', 'verb', 'freemius' ),
		'delete-account'                           => __( 'Delete Account', 'freemius' ),
		'dismiss'                                  => _x( 'Dismiss', 'as close a window', 'freemius' ),
		'plan'                                     => _x( 'Plan', 'as product pricing plan', 'freemius' ),
		'change-plan'                              => __( 'Change Plan', 'freemius' ),
		'download-x-version'                       => /* translators: %s: Plan name (e.g. professional) */
		                                              __( 'Download %s Version', 'freemius' ),
		'download-x-version-now'                   => /* translators: %s: Plan name (e.g. professional) */
		                                              __( 'Download %s version now', 'freemius' ),
		'download-latest'                          => _x( 'Download Latest', 'as download latest version', 'freemius' ),
		'you-have-x-license'                       => /* translators: %s: Plan name (e.g. professional) */
		                                              __( 'You have a %s license.', 'freemius' ),
		'new'                                      => __( 'New', 'freemius' ),
		'free'                                     => __( 'Free', 'freemius' ),
		'trial'                                    => _x( 'Trial', 'as trial plan', 'freemius' ),
		'purchase'                                 => _x( 'Purchase', 'verb', 'freemius' ),
		'purchase-license'                         => __( 'Purchase License', 'freemius' ),
		'buy'                                      => _x( 'Buy', 'verb', 'freemius' ),
		'buy-license'                              => __( 'Buy License', 'freemius' ),
		'license-single-site'                      => __( 'Single Site License', 'freemius' ),
		'license-unlimited'                        => __( 'Unlimited Licenses', 'freemius' ),
		'license-x-sites'                          => /* translators: %s: Number of licenses */
		                                              __( 'Up to %s Sites', 'freemius' ),
		'renew-license-now'                        => /* translators: 1: Starting HTML tag 2: Starting HTML tag 3: Plan name */
		                                              __( '%sRenew your license now%s to access version %s features and support.', 'freemius' ),
		'x-plan'                                   => /* translators: %s: Plan name (e.g. professional) */
		                                              __( '%s Plan', 'freemius' ),
		'you-are-step-away'                        => /* translators: %s: Plan name (e.g. professional) */
		                                              __( 'You are just one step away - %s', 'freemius' ),
		'activate-x-now'                           => /* translators: %s: Plugin name */
		                                              __( 'Complete "%s" Activation Now', 'freemius' ),
		'few-plugin-tweaks'                        => /* translators: %s: Plugin name */
		                                              __( 'We made a few tweaks to the plugin, %s', 'freemius' ),
		'optin-x-now'                              => /* translators: %s: Plugin name */
		                                              __( 'Opt-in to make "%s" Better!', 'freemius' ),
		'error'                                    => __( 'Error', 'freemius' ),
		'failed-finding-main-path'                 => __( 'Freemius SDK couldn\'t find the plugin\'s main file. Please contact sdk@freemius.com with the current error.', 'freemius' ),
		#region Account

		'expiration'                               => _x( 'Expiration', 'as expiration date', 'freemius' ),
		'license'                                  => _x( 'License', 'as software license', 'freemius' ),
		'not-verified'                             => __( 'not verified', 'freemius' ),
		'verify-email'                             => __( 'Verify Email', 'freemius' ),
		'expires-in'                               => /* translators: %s: Time period (e.g. 2 months) */
		                                              __( 'Expires in %s', 'freemius' ),
		'renews-in'                                => /* translators: %s: Time period (e.g. 2 months) */
		                                              __( 'Auto renews in %s', 'freemius' ),
		'no-expiration'                            => __( 'No expiration', 'freemius' ),
		'expired'                                  => __( 'Expired', 'freemius' ),
		'cancelled'                                => __( 'Cancelled', 'freemius' ),
		'in-x'                                     => /* translators: %s: Time period (e.g. 2 hours) */
		                                              __( 'In %s', 'freemius' ),
		'x-ago'                                    => /* translators: %s: Time period (e.g. 2 min) */
		                                              __( '%s ago', 'freemius' ),
		'version'                                  => _x( 'Version', 'as plugin version', 'freemius' ),
		'name'                                     => __( 'Name', 'freemius' ),
		'email'                                    => __( 'Email', 'freemius' ),
		'verified'                                 => __( 'Verified', 'freemius' ),
		'plugin'                                   => __( 'Plugin', 'freemius' ),
		'plugins'                                  => __( 'Plugins', 'freemius' ),
		'themes'                                   => __( 'Themes', 'freemius' ),
		'path'                                     => _x( 'Path', 'as file/folder path', 'freemius' ),
		'title'                                    => __( 'Title', 'freemius' ),
		'free-version'                             => __( 'Free version', 'freemius' ),
		'premium-version'                          => __( 'Premium version', 'freemius' ),
		'slug'                                     => _x( 'Slug', 'as WP plugin slug', 'freemius' ),
		'id'                                       => __( 'ID', 'freemius' ),
		'users'                                    => __( 'Users', 'freemius' ),
		'plugin-installs'                          => __( 'Plugin Installs', 'freemius' ),
		'sites'                                    => _x( 'Sites', 'like websites', 'freemius' ),
		'user-id'                                  => __( 'User ID', 'freemius' ),
		'site-id'                                  => __( 'Site ID', 'freemius' ),
		'public-key'                               => __( 'Public Key', 'freemius' ),
		'secret-key'                               => __( 'Secret Key', 'freemius' ),
		'no-secret'                                => _x( 'No Secret', 'as secret encryption key missing', 'freemius' ),
		'no-id'                                    => __( 'No ID', 'freemius' ),
		'sync-license'                             => _x( 'Sync License', 'as synchronize license', 'freemius' ),
		'sync'                                     => _x( 'Sync', 'as synchronize', 'freemius' ),
		'deactivate-license'                       => __( 'Deactivate License', 'freemius' ),
		'activate'                                 => __( 'Activate', 'freemius' ),
		'deactivate'                               => __( 'Deactivate', 'freemius' ),
		'skip-deactivate'                          => __( 'Skip & Deactivate', 'freemius' ),
		'no-deactivate'                            => __( 'No - just deactivate', 'freemius' ),
		'yes-do-your-thing'                        => __( 'Yes - do your thing', 'freemius' ),
		'active'                                   => _x( 'Active', 'active mode', 'freemius' ),
		'is-active'                                => _x( 'Is Active', 'is active mode?', 'freemius' ),
		'install-now'                              => __( 'Install Now', 'freemius' ),
		'install-update-now'                       => __( 'Install Update Now', 'freemius' ),
		'more-information-about-x'                 => /* translators: %s: Plugin name */
		                                              __( 'More information about %s', 'freemius' ),
		'localhost'                                => __( 'Localhost', 'freemius' ),
		'activate-x-plan'                          => /* translators: %s: Plan name (e.g. professional) */
		                                              __( 'Activate %s Plan', 'freemius' ),
		'x-left'                                   => /* translators: %s: Number of... (e.g. 5 licenses) */
		                                              __( '%s left', 'freemius' ),
		'last-license'                             => __( 'Last license', 'freemius' ),
		'what-is-your-x'                           => /* translators: %s: ??? */
		                                              __( 'What is your %s?', 'freemius' ),
		'activate-this-addon'                      => __( 'Activate this add-on', 'freemius' ),
		'deactivate-license-confirm'               => __( 'Deactivating your license will block all premium features, but will enable you to activate the license on another site. Are you sure you want to proceed?', 'freemius' ),
		'delete-account-x-confirm'                 => /* translators: %s: Plan name (e.g. professional) */
		                                              __( 'Deleting the account will automatically deactivate your %s plan license so you can use it on other sites. If you want to terminate the recurring payments as well, click the "Cancel" button, and first "Downgrade" your account. Are you sure you would like to continue with the deletion?', 'freemius' ),
		'delete-account-confirm'                   => __( 'Deletion is not temporary. Only delete if you no longer want to use this plugin anymore. Are you sure you would like to continue with the deletion?', 'freemius' ),
		'downgrade-x-confirm'                      => /* translators: 1: Plan name (e.g. professional) 2: Time period (e.g. 2 months) */
		                                              __( 'Downgrading your plan will immediately stop all future recurring payments and your %s plan license will expire in %s.', 'freemius' ),
		'cancel-trial-confirm'                     => __( 'Cancelling the trial will immediately block access to all premium features. Are you sure?', 'freemius' ),
		'after-downgrade-non-blocking'             => /* translators: %s: Plan name (e.g. professional) */
		                                              __( 'You can still enjoy all %s features but you will not have access to plugin updates and support.', 'freemius' ),
		'after-downgrade-blocking'                 => /* translators: %s: Plan name (e.g. professional) */
		                                              __( 'Once your license expire you can still use the Free version but you will NOT have access to the %s features.', 'freemius' ),
		'proceed-confirmation'                     => __( 'Are you sure you want to proceed?', 'freemius' ),
		#endregion Account

		'add-ons-for-x'                            => /* translators: %s: Plugin name */
		                                              __( 'Add Ons for %s', 'freemius' ),
		'add-ons-missing'                          => __( 'We could\'nt load the add-ons list. It\'s probably an issue on our side, please try to come back in few minutes.', 'freemius' ),
		#region Plugin Deactivation
		'deactivation-share-reason'                => __( 'If you have a moment, please let us know why you are deactivating', 'freemius' ),
		'deactivation-modal-button-confirm'        => __( 'Yes - Deactivate', 'freemius' ),
		'deactivation-modal-button-submit'         => __( 'Submit & Deactivate', 'freemius' ),
		'deactivation-modal-button-cancel'         => _x( 'Cancel', 'the text of the cancel button of the plugin deactivation dialog box.', 'freemius' ),
		'reason-no-longer-needed'                  => __( 'I no longer need the plugin', 'freemius' ),
		'reason-found-a-better-plugin'             => __( 'I found a better plugin', 'freemius' ),
		'reason-needed-for-a-short-period'         => __( 'I only needed the plugin for a short period', 'freemius' ),
		'reason-broke-my-site'                     => __( 'The plugin broke my site', 'freemius' ),
		'reason-suddenly-stopped-working'          => __( 'The plugin suddenly stopped working', 'freemius' ),
		'reason-cant-pay-anymore'                  => __( "I can't pay for it anymore", 'freemius' ),
		'reason-temporary-deactivation'            => __( "It's a temporary deactivation. I'm just debugging an issue.", 'freemius' ),
		'reason-other'                             => _x( 'Other', 'the text of the "other" reason for deactivating the plugin that is shown in the modal box.', 'freemius' ),
		'ask-for-reason-message'                   => __( 'Kindly tell us the reason so we can improve.', 'freemius' ),
		'placeholder-plugin-name'                  => __( "What's the plugin's name?", 'freemius' ),
		'placeholder-comfortable-price'            => __( 'What price would you feel comfortable paying?', 'freemius' ),
		'reason-couldnt-make-it-work'              => __( "I couldn't understand how to make it work", 'freemius' ),
		'reason-great-but-need-specific-feature'   => __( "The plugin is great, but I need specific feature that you don't support", 'freemius' ),
		'reason-not-working'                       => __( 'The plugin is not working', 'freemius' ),
		'reason-not-what-i-was-looking-for'        => __( "It's not what I was looking for", 'freemius' ),
		'reason-didnt-work-as-expected'            => __( "The plugin didn't work as expected", 'freemius' ),
		'placeholder-feature'                      => __( 'What feature?', 'freemius' ),
		'placeholder-share-what-didnt-work'        => __( "Kindly share what didn't work so we can fix it for future users...", 'freemius' ),
		'placeholder-what-youve-been-looking-for'  => __( "What you've been looking for?", 'freemius' ),
		'placeholder-what-did-you-expect'          => __( "What did you expect?", 'freemius' ),
		'reason-didnt-work'                        => __( "The plugin didn't work", 'freemius' ),
		'reason-dont-like-to-share-my-information' => __( "I don't like to share my information with you", 'freemius' ),
		#endregion Plugin Deactivation

		#region Connect
		'hey-x'                                    => /* translators: %s: User name (e.g. John) */
		                                              __( 'Hey %s,', 'freemius' ),
		'thanks-x'                                 => /* translators: %s: User name (e.g. John) */
		                                              __( 'Thanks %s!', 'freemius' ),
		'connect-message'                          => /* translators: 1: Plugin name 2: ??? 3: ??? 4: ??? */
		                                              __( 'In order to enjoy all our features and functionality, %s needs to connect your user, %s at %s, to %s', 'freemius' ),
		'connect-message_on-update'                => /* translators: 2: Plugin name 5: Email Address */
		                                              __( 'Please help us improve %2$s! If you opt-in, some data about your usage of %2$s will be sent to %5$s. If you skip this, that\'s okay! %2$s will still work just fine.', 'freemius' ),
		'pending-activation-message'               => /* translators: 1: Plugin name 2: Email Address */
		                                              __( 'You should receive an activation email for %s to your mailbox at %s. Please make sure you click the activation button in that email to complete the install.', 'freemius' ),
		'what-permissions'                         => __( 'What permissions are being granted?', 'freemius' ),
		'permissions-profile'                      => __( 'Your Profile Overview', 'freemius' ),
		'permissions-profile_desc'                 => __( 'Name and email address', 'freemius' ),
		'permissions-site'                         => __( 'Your Site Overview', 'freemius' ),
		'permissions-site_desc'                    => __( 'Site URL, WP version, PHP info, plugins & themes', 'freemius' ),
		'permissions-events'                       => __( 'Current Plugin Events', 'freemius' ),
		'permissions-events_desc'                  => __( 'Activation, deactivation and uninstall', 'freemius' ),
		'permissions-plugins_themes'               => __( 'Plugins & Themes', 'freemius' ),
		'permissions-plugins_themes_desc'          => __( 'Titles, versions and state.', 'freemius' ),
		'permissions-newsletter'                   => __( 'Newsletter', 'freemius' ),
		'permissions-newsletter_desc'              => __( 'Updates, announcements, marketing, no spam', 'freemius' ),
		'privacy-policy'                           => __( 'Privacy Policy', 'freemius' ),
		'tos'                                      => __( 'Terms of Service', 'freemius' ),
		'activating'                               => _x( 'Activating', 'as activating plugin', 'freemius' ),
		'sending-email'                            => _x( 'Sending email', 'as in the process of sending an email', 'freemius' ),
		'opt-in-connect'                           => _x( 'Allow & Continue', 'button label', 'freemius' ),
		'skip'                                     => _x( 'Skip', 'verb', 'freemius' ),
		'resend-activation-email'                  => __( 'Re-send activation email', 'freemius' ),
		#endregion Connect

		#region Screenshots
		'screenshots'                              => __( 'Screenshots', 'freemius' ),
		'view-full-size-x'                         => /* translators: %s: Screenshot number */
		                                              __( 'Click to view full-size screenshot %d', 'freemius' ),
		#endregion Screenshots

		#region Debug
		'freemius-debug'                           => __( 'Freemius Debug', 'freemius' ),
		'on'                                       => _x( 'On', 'as turned on', 'freemius' ),
		'off'                                      => _x( 'Off', 'as turned off', 'freemius' ),
		'debugging'                                => _x( 'Debugging', 'as code debugging', 'freemius' ),
		'freemius-state'                           => __( 'Freemius State', 'freemius' ),
		'connected'                                => _x( 'Connected', 'as connection was successful', 'freemius' ),
		'blocked'                                  => _x( 'Blocked', 'as connection blocked', 'freemius' ),
		'api'                                      => _x( 'API', 'as application program interface', 'freemius' ),
		'sdk'                                      => _x( 'SDK', 'as software development kit versions', 'freemius' ),
		'sdk-versions'                             => _x( 'SDK Versions', 'as software development kit versions', 'freemius' ),
		'plugin-path'                              => _x( 'Plugin Path', 'as plugin folder path', 'freemius' ),
		'sdk-path'                                 => _x( 'SDK Path', 'as sdk path', 'freemius' ),
		'addons-of-x'                              => /* translators: %s: Plugin name */
		                                              __( 'Add Ons of Plugin %s', 'freemius' ),
		'delete-all-confirm'                       => __( 'Are you sure you want to delete all Freemius data?', 'freemius' ),
		'actions'                                  => __( 'Actions', 'freemius' ),
		'delete-all-accounts'                      => __( 'Delete All Accounts', 'freemius' ),
		'start-fresh'                              => __( 'Start Fresh', 'freemius' ),
		'clear-api-cache'                          => __( 'Clear API Cache', 'freemius' ),
		'sync-data-from-server'                    => __( 'Sync Data From Server', 'freemius' ),
		'scheduled-crons'                          => __( 'Scheduled Crons', 'freemius' ),
		'plugins-themes-sync'                      => __( 'Plugins & Themes Sync', 'freemius' ),
		#endregion Debug

		#region Expressions
		'congrats'                                 => _x( 'Congrats', 'as congratulations', 'freemius' ),
		'oops'                                     => _x( 'Oops', 'exclamation', 'freemius' ),
		'yee-haw'                                  => _x( 'Yee-haw', 'interjection expressing joy or exuberance', 'freemius' ),
		'woot'                                     => _x( 'W00t', '(especially in electronic communication) used to express elation, enthusiasm, or triumph.', 'freemius' ),
		'right-on'                                 => _x( 'Right on', 'a positive response', 'freemius' ),
		'hmm'                                      => _x( 'Hmm', 'something somebody says when they are thinking about what you have just said. ', 'freemius' ),
		'ok'                                       => __( 'O.K', 'freemius' ),
		'hey'                                      => _x( 'Hey', 'exclamation', 'freemius' ),
		'heads-up'                                 => _x( 'Heads up', 'advance notice of something that will need attention.', 'freemius' ),
		#endregion Expressions

		#region Admin Notices
		'you-have-latest'                          => __( 'Seems like you got the latest release.', 'freemius' ),
		'you-are-good'                             => __( 'You are all good!', 'freemius' ),
		'user-exist-message'                       => __( 'Sorry, we could not complete the email update. Another user with the same email is already registered.', 'freemius' ),
		'user-exist-message_ownership'             => /* translators: %s: User name */
		                                              __( 'If you would like to give up the ownership of the plugin\'s account to %s click the Change Ownership button.', 'freemius' ),
		'email-updated-message'                    => __( 'Your email was successfully updated. You should receive an email with confirmation instructions in few moments.', 'freemius' ),
		'name-updated-message'                     => __( 'Your name was successfully updated.', 'freemius' ),
		'x-updated'                                => /* translators: %s: The updated item */
		                                              __( 'You have successfully updated your %s.', 'freemius' ),
		'name-update-failed-message'               => __( 'Please provide your full name.', 'freemius' ),
		'verification-email-sent-message'          => /* translators: %s: Email Address */
		                                              __( 'Verification mail was just sent to %s. If you can\'t find it after 5 min, please check your spam box.', 'freemius' ),
		'addons-info-external-message'             => /* translators: %s: Plugin name */
		                                              __( 'Just letting you know that the add-ons information of %s is being pulled from an external server.', 'freemius' ),
		'no-cc-required'                           => __( 'No credit card required', 'freemius' ),
		'premium-activated-message'                => __( 'Premium plugin version was successfully activated.', 'freemius' ),
		'successful-version-upgrade-message'       => /* translators: %s: Plugin name */
		                                              __( 'The upgrade of %s was successfully completed.', 'freemius' ),
		'activation-with-plan-x-message'           => /* translators: %s: Plan name (e.g. professional) */
		                                              __( 'Your account was successfully activated with the %s plan.', 'freemius' ),
		'download-latest-x-version'                => /* translators: %s: Plugin name */
		                                              __( 'Download the latest %s version now', 'freemius' ),
		'download-latest-version'                  => __( 'Download the latest version now', 'freemius' ),
		'addon-successfully-purchased-message'     => /* translators: %s: Add-on name */
		                                              __( '%s Add-on was successfully purchased.', 'freemius' ),
		'addon-successfully-upgraded-message'      => /* translators: %s: Add-on name */
		                                              __( 'Your %s Add-on plan was successfully upgraded.', 'freemius' ),
		'email-verified-message'                   => __( 'Your email has been successfully verified - you are AWESOME!', 'freemius' ),
		'plan-upgraded-message'                    => __( 'Your plan was successfully upgraded.', 'freemius' ),
		'plan-changed-to-x-message'                => /* translators: %s: Plan name (e.g. professional) */
		                                              __( 'Your plan was successfully changed to %s.', 'freemius' ),
		'license-expired-blocking-message'         => __( 'Your license has expired. You can still continue using the free plugin forever.', 'freemius' ),
		'trial-started-message'                    => __( 'Your trial has been successfully started.', 'freemius' ),
		'license-activated-message'                => __( 'Your license was successfully activated.', 'freemius' ),
		'no-active-license-message'                => __( 'It looks like your site currently doesn\'t have an active license.', 'freemius' ),
		'license-deactivation-message'             => /* translators: %s: Plan name */
		                                              __( 'Your license was successfully deactivated, you are back to the %s plan.', 'freemius' ),
		'license-deactivation-failed-message'      => __( 'It looks like the license deactivation failed.', 'freemius' ),
		'license-activation-failed-message'        => __( 'It looks like the license could not be activated.', 'freemius' ),
		'server-error-message'                     => __( 'Error received from the server:', 'freemius' ),
		'trial-expired-message'                    => __( 'Your trial has expired. You can still continue using all our free features.', 'freemius' ),
		'plan-x-downgraded-message'                => /* translators: 1: Plan name 2: Plan expiration date */
		                                              __( 'Your plan was successfully downgraded. Your %s plan license will expire in %s.', 'freemius' ),
		'plan-downgraded-failure-message'          => __( 'Seems like we are having some temporary issue with your plan downgrade. Please try again in few minutes.', 'freemius' ),
		'trial-cancel-no-trial-message'            => __( 'It looks like you are not in trial mode anymore so there\'s nothing to cancel :)', 'freemius' ),
		'trial-cancel-message'                     => /* translators: %s: Plugin name */
		                                              __( 'Your %s free trial was successfully cancelled.', 'freemius' ),
		'version-x-released'                       => /* translators: %s: Numeric version number */
		                                              __( 'Version %s was released.', 'freemius' ),
		'please-download-x'                        => /* translators: %s: Plugin name */
		                                              __( 'Please download %s.', 'freemius' ),
		'latest-x-version'                         => /* translators: %s: Plan name (e.g. professional) */
		                                              _x( 'the latest %s version here', 'freemius' ),
		'trial-x-promotion-message'                => /* translators: %s: Plugin name %d: Days */
		                                              __( 'How do you like %s so far? Test all our %s premium features with a %d-day free trial.', 'freemius' ),
		'start-free-trial'                         => _x( 'Start free trial', 'call to action', 'freemius' ),
		'trial-cancel-failure-message'             => __( 'Seems like we are having some temporary issue with your trial cancellation. Please try again in few minutes.', 'freemius' ),
		'trial-utilized'                           => __( 'You already utilized a trial before.', 'freemius' ),
		'in-trial-mode'                            => __( 'You are already running the plugin in a trial mode.', 'freemius' ),
		'trial-plan-x-not-exist'                   => /* translators: %s: Plan name */
		                                              __( 'Plan %s do not exist, therefore, can\'t start a trial.', 'freemius' ),
		'plan-x-no-trial'                          => /* translators: %s: Plan name (e.g. professional) */
		                                              __( 'Plan %s does not support a trial period.', 'freemius' ),
		'no-trials'                                => __( 'None of the plugin\'s plans supports a trial period.', 'freemius' ),
		'unexpected-api-error'                     => __( 'Unexpected API error. Please contact the plugin\'s author with the following error.', 'freemius' ),
		'no-commitment-for-x-days'                 => /* translators: %s: Numeric time period in days */
		                                              __( 'No commitment for %s days - cancel anytime!', 'freemius' ),
		'license-expired-non-blocking-message'     => /* translators: %s: Plan name (e.g. professional) */
		                                              __( 'Your license has expired. You can still continue using all the %s features, but you\'ll need to renew your license to continue getting updates and support.', 'freemius' ),
		'could-not-activate-x'                     => /* translators: %s: Plugin name */
		                                              __( 'Couldn\'t activate %s.', 'freemius' ),
		'contact-us-with-error-message'            => __( 'Please contact us with the following message:', 'freemius' ),
		'plan-did-not-change-message'              => /* translators: %s: Plan name */
		                                              __( 'It looks like you are still on the %s plan. If you did upgrade or change your plan, it\'s probably an issue on our side - sorry.', 'freemius' ),
		'contact-us-here'                          => __( 'Please contact us here', 'freemius' ),
		'plan-did-not-change-email-message'        => /* translators: %s: Plan name */
		                                              __( 'I have upgraded my account but when I try to Sync the License, the plan remains %s.', 'freemius' ),
		#endregion Admin Notices
		#region Connectivity Issues
		'connectivity-test-fails-message'          => __( 'From unknown reason, the API connectivity test failed.', 'freemius' ),
		'connectivity-test-maybe-temporary'        => __( 'It\'s probably a temporary issue on our end. Just to be sure, with your permission, would it be o.k to run another connectivity test?', 'freemius' ),
		'curl-missing-message'                     => __( 'We use PHP cURL library for the API calls, which is a very common library and usually installed out of the box. Unfortunately, cURL is not installed on your server.', 'freemius' ),
		'cloudflare-blocks-connection-message'     => __( 'From unknown reason, CloudFlare, the firewall we use, blocks the connection.', 'freemius' ),
		'x-requires-access-to-api'                 => /* translators: %s: Plugin name */
		                                              __( '%s requires an access to our API.', 'freemius' ),
		'squid-blocks-connection-message'          => __( 'It looks like your server is using Squid ACL (access control lists), which blocks the connection.', 'freemius' ),
		'squid-no-clue-title'                      => __( 'I don\'t know what is Squid or ACL, help me!', 'freemius' ),
		'squid-no-clue-desc'                       => /* translators: %s: Email Address */
		                                              __( 'We\'ll make sure to contact your hosting company and resolve the issue. You will get a follow-up email to %s once we have an update.', 'freemius' ),
		'sysadmin-title'                           => __( 'I\'m a system administrator', 'freemius' ),
		'squid-sysadmin-desc'                      => /* translators: %s: Domain names */
		                                              __( 'Great, please whitelist the following domains: %s. Once you done, deactivate the plugin and activate it again.', 'freemius' ),
		'curl-missing-no-clue-title'               => __( 'I don\'t know what is cURL or how to install it, help me!', 'freemius' ),
		'curl-missing-no-clue-desc'                => /* translators: %s: Email Address */
		                                              __( 'We\'ll make sure to contact your hosting company and resolve the issue. You will get a follow-up email to %s once we have an update.', 'freemius' ),
		'curl-missing-sysadmin-desc'               => __( 'Great, please install cURL and enable it in your php.ini file. To make sure it was successfully activated, use \'phpinfo()\'. Once activated, deactivate the plugin and reactivate it back again.', 'freemius' ),
		'happy-to-resolve-issue-asap'              => __( 'We are sure it\'s an issue on our side and more than happy to resolve it for you ASAP if you give us a chance.', 'freemius' ),
		'fix-issue-title'                          => __( 'Yes - I\'m giving you a chance to fix it', 'freemius' ),
		'fix-issue-desc'                           => /* translators: %s: Email Address */
		                                              __( 'We will do our best to whitelist your server and resolve this issue ASAP. You will get a follow-up email to %s once we have an update.', 'freemius' ),
		'install-previous-title'                   => __( 'Let\'s try your previous version', 'freemius' ),
		'install-previous-desc'                    => __( 'Uninstall this version and install the previous one.', 'freemius' ),
		'deactivate-plugin-title'                  => __( 'That\'s exhausting, please deactivate', 'freemius' ),
		'deactivate-plugin-desc'                   => __( 'We feel your frustration and sincerely apologize for the inconvenience. Hope to see you again in the future.', 'freemius' ),
		'fix-request-sent-message'                 => /* translators: %s: Issue */
		                                              __( 'Thank for giving us the chance to fix it! A message was just sent to our technical staff. We will get back to you as soon as we have an update to %s. Appreciate your patience.', 'freemius' ),
		'server-blocking-access'                   => /* translators: 1: Plugin name 2: API domain */
		                                              __( 'Your server is blocking the access to Freemius\' API, which is crucial for %1s synchronization. Please contact your host to whitelist %2s', 'freemius' ),
		'wrong-authentication-param-message'       => __( 'It seems like one of the authentication parameters is wrong. Update your Public Key, Secret Key & User ID, and try again.', 'freemius' ),
		#endregion Connectivity Issues
		#region Change Owner
		'change-owner-request-sent-x'              => /* translators: %s: Email Address */
		                                              __( 'Please check your mailbox, you should receive an email via %s to confirm the ownership change. From security reasons, you must confirm the change within the next 15 min. If you cannot find the email, please check your spam folder.', 'freemius' ),
		'change-owner-request_owner-confirmed'     => /* translators: %s: Email Address */
		                                              __( 'Thanks for confirming the ownership change. An email was just sent to %s for final approval.', 'freemius' ),
		'change-owner-request_candidate-confirmed' => /* translators: %s: Owner name */
		                                              __( '%s is the new owner of the account.', 'freemius' ),
		#endregion Change Owner
		'addon-x-cannot-run-without-y'             => /* translators: 1: Add-on name 2: Plugin name */
		                                              __( '%s cannot run without %s.', 'freemius' ),
		'addon-x-cannot-run-without-parent'        => /* translators: %s: Add-on name */
		                                              __( '%s cannot run without the plugin.', 'freemius' ),
		'plugin-x-activation-message'              => /* translators: %s: Plugin name */
		                                              __( '%s activation was successfully completed.', 'freemius' ),
		'features-and-pricing'                     => _x( 'Features & Pricing', 'Plugin installer section title', 'freemius' ),
		'free-addon-not-deployed'                  => __( 'Add-on must be deployed to WordPress.org or Freemius.', 'freemius' ),
		'paid-addon-not-deployed'                  => __( 'Paid add-on must be deployed to Freemius.', 'freemius' ),
		#region Add-On Licensing
		'addon-no-license-message'                 => /* translators: %s: Add-on name */
		                                              __( '%s is a premium only add-on. You have to purchase a license first before activating the plugin.', 'freemius' ),
		'addon-trial-cancelled-message'            => /* translators: %s: Add-on name */
		                                              __( '%s free trial was successfully cancelled. Since the add-on is premium only it was automatically deactivated. If you like to use it in the future, you\'ll have to purchase a license.', 'freemius' ),
		#endregion Add-On Licensing
		#region Billing Cycles
		'monthly'                                  => _x( 'Monthly', 'as every month', 'freemius' ),
		'mo'                                       => _x( 'mo', 'as monthly period', 'freemius' ),
		'annual'                                   => _x( 'Annual', 'as once a year', 'freemius' ),
		'annually'                                 => _x( 'Annually', 'as once a year', 'freemius' ),
		'once'                                     => _x( 'Once', 'as once a year', 'freemius' ),
		'year'                                     => _x( 'year', 'as annual period', 'freemius' ),
		'lifetime'                                 => __( 'Lifetime', 'freemius' ),
		'best'                                     => _x( 'Best', 'e.g. the best product', 'freemius' ),
		'billed-x'                                 => /* translators: %s: Billing time period (e.g. monthly) */
		                                              __( 'Billed %s', 'freemius' ),
		'save-x'                                   => /* translators: %s: Discount (e.g. discount of $5 or 10%) */
		                                              __( 'Save %s', 'freemius' ),
		#endregion Billing Cycles
		'view-details'                             => __( 'View details', 'freemius' ),
	);
