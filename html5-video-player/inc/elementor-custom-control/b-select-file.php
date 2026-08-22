<?php

namespace H5VP\Elementor;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * The Pro build of this plugin ships its own H5VP\Elementor\H5VPSelectFile.
 * Both files are pulled in with require_once against different paths, so PHP
 * would hit a fatal "cannot redeclare" if the two ever loaded in one request.
 */
if (class_exists(__NAMESPACE__ . '\H5VPSelectFile')) {
	return;
}

/**
 * FileSelect control.
 *
 * A control for selecting any type of file (video sources, posters, .vtt
 * captions) from the media library, or for pasting an external URL.
 *
 * The stored value is a plain URL string, unchanged from the original
 * implementation, so widgets saved by earlier versions keep working.
 *
 * @since 1.0.0
 */
class H5VPSelectFile extends \Elementor\Base_Data_Control
{

	/**
	 * Get control type.
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Control type.
	 */
	public function get_type()
	{
		return 'h5vp-select-file';
	}

	/**
	 * Enqueue control scripts and styles.
	 *
	 * Used to register and enqueue custom scripts and styles
	 * for this control.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function enqueue()
	{
		wp_enqueue_media();
		wp_enqueue_style('thickbox');
		wp_enqueue_script('media-upload');
		wp_enqueue_script('thickbox');

		wp_register_style('h5vp-elementor-controls', plugins_url('/css/controls.css', __FILE__), [], H5VP_VER);
		wp_enqueue_style('h5vp-elementor-controls');

		wp_register_script('h5vp-elementor-controls', plugins_url('/js/controls.js', __FILE__), ['jquery'], H5VP_VER, true);
		wp_enqueue_script('h5vp-elementor-controls');
	}

	/**
	 * Get default settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 *
	 * @return array Control default settings.
	 */
	protected function get_default_settings()
	{
		return [
			'label_block' => true,
		];
	}

	/**
	 * Render control output in the editor.
	 *
	 * The markup is static: the preview, the button label and the clear button
	 * are driven by controls.js from the current value. Re-rendering the whole
	 * control on every keystroke would move focus out of the URL field.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function content_template()
	{
		$control_uid = $this->get_control_uid();
		?>
		<div class="elementor-control-field h5vp-file-control">
			<label for="<?php echo esc_attr($control_uid); ?>" class="elementor-control-title">{{{ data.label }}}</label>
			<div class="elementor-control-input-wrapper">
				<div class="h5vp-file is-empty">
					<div class="h5vp-file__preview">
						<span class="h5vp-file__thumb" aria-hidden="true">
							<i class="eicon-video-camera"></i>
						</span>
						<span class="h5vp-file__meta">
							<span class="h5vp-file__name"></span>
							<span class="h5vp-file__host"></span>
						</span>
						<span class="h5vp-file__empty"><?php esc_html_e('No file selected', 'html5-video-player'); ?></span>
					</div>

					<div class="h5vp-file__actions">
						<button type="button" class="h5vp-file__choose" id="select-file-<?php echo esc_attr($control_uid); ?>">
							<i class="eicon-upload" aria-hidden="true"></i>
							<span class="h5vp-file__choose-text"
								data-choose="<?php esc_attr_e('Choose File', 'html5-video-player'); ?>"
								data-replace="<?php esc_attr_e('Replace', 'html5-video-player'); ?>"><?php esc_html_e('Choose File', 'html5-video-player'); ?></span>
						</button>
						<button type="button" class="h5vp-file__clear"
							title="<?php esc_attr_e('Remove', 'html5-video-player'); ?>"
							aria-label="<?php esc_attr_e('Remove', 'html5-video-player'); ?>">&times;</button>
					</div>

					<?php // Kept as a text field so a CDN or external URL can still be pasted directly. ?>
					<input type="text" class="h5vp-file__url" id="<?php echo esc_attr($control_uid); ?>"
						data-setting="{{ data.name }}" placeholder="{{ data.placeholder }}">
				</div>
			</div>
		</div>
		<# if ( data.description ) { #>
			<div class="elementor-control-field-description">{{{ data.description }}}</div>
			<# } #>
				<?php
	}
}
