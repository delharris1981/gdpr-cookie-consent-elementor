<?php
/**
 * GDPR Cookie Consent Widget.
 *
 * @package GDPR_Cookie_Consent_Elementor
 */

namespace GDPR_Cookie_Consent_Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * GDPR Cookie Consent Widget.
 *
 * @since 1.0.0
 */
class GDPR_Widget extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'gdpr-cookie-consent';
	}

	/**
	 * Get widget title.
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return esc_html__( 'GDPR Cookie Consent', 'gdpr-cookie-consent-elementor' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-lock-user';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * Get widget keywords.
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return array( 'gdpr', 'cookie', 'consent', 'privacy' );
	}

	/**
	 * Get script dependencies.
	 *
	 * @return array Script dependencies.
	 */
	public function get_script_depends() {
		return array( 'gdpr-widget-frontend' );
	}

	/**
	 * Register widget controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		// Content Tab.
		$this->register_content_controls();

		// Style Tab.
		$this->register_style_controls();
	}

	/**
	 * Register content controls.
	 *
	 * @return void
	 */
	protected function register_content_controls() {
		// GDPR Message Section.
		$this->start_controls_section(
			'section_gdpr_message',
			array(
				'label' => esc_html__( 'GDPR Message', 'gdpr-cookie-consent-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'gdpr_message',
			array(
				'label'       => esc_html__( 'Message Text', 'gdpr-cookie-consent-elementor' ),
				'type'        => Controls_Manager::TEXTAREA,
				'default'     => esc_html__( 'This site uses cookies to enhance your experience. By continuing to browse, you agree to our use of cookies.', 'gdpr-cookie-consent-elementor' ),
				'placeholder' => esc_html__( 'Enter your GDPR message here...', 'gdpr-cookie-consent-elementor' ),
				'rows'        => 4,
			)
		);

		$this->end_controls_section();

		// Accept Button Section.
		$this->start_controls_section(
			'section_accept_button',
			array(
				'label' => esc_html__( 'Accept Button', 'gdpr-cookie-consent-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'accept_button_text',
			array(
				'label'       => esc_html__( 'Button Text', 'gdpr-cookie-consent-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'I Agree', 'gdpr-cookie-consent-elementor' ),
				'placeholder' => esc_html__( 'I Agree', 'gdpr-cookie-consent-elementor' ),
			)
		);

		$this->end_controls_section();

		// Decline Button Section.
		$this->start_controls_section(
			'section_decline_button',
			array(
				'label' => esc_html__( 'Decline Button', 'gdpr-cookie-consent-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'decline_button_text',
			array(
				'label'       => esc_html__( 'Button Text', 'gdpr-cookie-consent-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'I Decline', 'gdpr-cookie-consent-elementor' ),
				'placeholder' => esc_html__( 'I Decline', 'gdpr-cookie-consent-elementor' ),
			)
		);

		$this->end_controls_section();

		// Popup Settings Section.
		$this->start_controls_section(
			'section_popup_settings',
			array(
				'label' => esc_html__( 'Popup Settings', 'gdpr-cookie-consent-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'close_popup_on_click',
			array(
				'label'        => esc_html__( 'Close Popup on Button Click', 'gdpr-cookie-consent-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gdpr-cookie-consent-elementor' ),
				'label_off'    => esc_html__( 'No', 'gdpr-cookie-consent-elementor' ),
				'return_value' => 'yes',
				'default'      => 'no',
				'description' => esc_html__( 'Automatically close the popup when Accept or Decline button is clicked', 'gdpr-cookie-consent-elementor' ),
			)
		);

		$this->add_control(
			'close_popup_on_button',
			array(
				'label'       => esc_html__( 'Close on Button', 'gdpr-cookie-consent-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'both',
				'options'     => array(
					'both'   => esc_html__( 'Both Buttons', 'gdpr-cookie-consent-elementor' ),
					'accept' => esc_html__( 'Accept Button Only', 'gdpr-cookie-consent-elementor' ),
					'decline' => esc_html__( 'Decline Button Only', 'gdpr-cookie-consent-elementor' ),
				),
				'condition'   => array(
					'close_popup_on_click' => 'yes',
				),
				'description' => esc_html__( 'Select which button(s) should close the popup', 'gdpr-cookie-consent-elementor' ),
			)
		);

		$this->end_controls_section();

		// Category Settings Section.
		$this->start_controls_section(
			'section_category_settings',
			array(
				'label' => esc_html__( 'Category Settings', 'gdpr-cookie-consent-elementor' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'enable_category_management',
			array(
				'label'        => esc_html__( 'Enable Category Management', 'gdpr-cookie-consent-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gdpr-cookie-consent-elementor' ),
				'label_off'    => esc_html__( 'No', 'gdpr-cookie-consent-elementor' ),
				'return_value' => 'yes',
				'default'      => 'no',
				'description'  => esc_html__( 'Enable granular cookie category selection instead of simple accept/decline', 'gdpr-cookie-consent-elementor' ),
			)
		);

		$this->add_control(
			'category_display_mode',
			array(
				'label'       => esc_html__( 'Category Display Mode', 'gdpr-cookie-consent-elementor' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => 'modal',
				'options'     => array(
					'modal'   => esc_html__( 'Modal Only', 'gdpr-cookie-consent-elementor' ),
					'inline'  => esc_html__( 'Inline Only', 'gdpr-cookie-consent-elementor' ),
					'both'    => esc_html__( 'Both Inline and Modal', 'gdpr-cookie-consent-elementor' ),
				),
				'condition'   => array(
					'enable_category_management' => 'yes',
				),
				'description' => esc_html__( 'How to display cookie categories to users', 'gdpr-cookie-consent-elementor' ),
			)
		);

		$this->end_controls_section();

		// Customize Button Section.
		$this->start_controls_section(
			'section_customize_button',
			array(
				'label'     => esc_html__( 'Customize Button', 'gdpr-cookie-consent-elementor' ),
				'tab'       => Controls_Manager::TAB_CONTENT,
				'condition' => array(
					'enable_category_management' => 'yes',
				),
			)
		);

		$this->add_control(
			'show_customize_button',
			array(
				'label'        => esc_html__( 'Show Customize Button', 'gdpr-cookie-consent-elementor' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'gdpr-cookie-consent-elementor' ),
				'label_off'    => esc_html__( 'No', 'gdpr-cookie-consent-elementor' ),
				'return_value' => 'yes',
				'default'      => 'yes',
				'description'  => esc_html__( 'Display a button to open cookie preferences center', 'gdpr-cookie-consent-elementor' ),
			)
		);

		$this->add_control(
			'customize_button_text',
			array(
				'label'       => esc_html__( 'Customize Button Text', 'gdpr-cookie-consent-elementor' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => esc_html__( 'Customize Cookies', 'gdpr-cookie-consent-elementor' ),
				'placeholder' => esc_html__( 'Customize Cookies', 'gdpr-cookie-consent-elementor' ),
				'condition'   => array(
					'show_customize_button' => 'yes',
				),
			)
		);

		$this->end_controls_section();

		// Customize Button Style Section.
		$this->start_controls_section(
			'section_customize_button_style',
			array(
				'label'     => esc_html__( 'Customize Button', 'gdpr-cookie-consent-elementor' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array(
					'enable_category_management' => 'yes',
					'show_customize_button'     => 'yes',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'customize_button_typography',
				'global'   => array(
					'default' => Global_Typography::TYPOGRAPHY_ACCENT,
				),
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-customize-button',
			)
		);

		$this->start_controls_tabs( 'customize_button_tabs' );

		$this->start_controls_tab(
			'customize_button_normal',
			array(
				'label' => esc_html__( 'Normal', 'gdpr-cookie-consent-elementor' ),
			)
		);

		$this->add_control(
			'customize_button_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'gdpr-cookie-consent-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .gdpr-cookie-consent-customize-button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'customize_button_background',
				'types'    => array( 'classic', 'gradient' ),
				'exclude'  => array( 'image' ),
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-customize-button',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'customize_button_hover',
			array(
				'label' => esc_html__( 'Hover', 'gdpr-cookie-consent-elementor' ),
			)
		);

		$this->add_control(
			'customize_button_hover_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'gdpr-cookie-consent-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gdpr-cookie-consent-customize-button:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'customize_button_hover_background',
				'types'    => array( 'classic', 'gradient' ),
				'exclude'  => array( 'image' ),
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-customize-button:hover',
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'customize_button_border',
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-customize-button',
			)
		);

		$this->add_control(
			'customize_button_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'gdpr-cookie-consent-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .gdpr-cookie-consent-customize-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'customize_button_padding',
			array(
				'label'      => esc_html__( 'Padding', 'gdpr-cookie-consent-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .gdpr-cookie-consent-customize-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'customize_button_box_shadow',
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-customize-button',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register style controls.
	 *
	 * @return void
	 */
	protected function register_style_controls() {
		// GDPR Message Style Section.
		$this->start_controls_section(
			'section_gdpr_message_style',
			array(
				'label' => esc_html__( 'GDPR Message', 'gdpr-cookie-consent-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'gdpr_message_typography',
				'global'   => array(
					'default' => Global_Typography::TYPOGRAPHY_TEXT,
				),
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-message',
			)
		);

		$this->add_control(
			'gdpr_message_color',
			array(
				'label'     => esc_html__( 'Text Color', 'gdpr-cookie-consent-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .gdpr-cookie-consent-message' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'gdpr_message_align',
			array(
				'label'     => esc_html__( 'Alignment', 'gdpr-cookie-consent-elementor' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array(
						'title' => esc_html__( 'Left', 'gdpr-cookie-consent-elementor' ),
						'icon'  => 'eicon-text-align-left',
					),
					'center' => array(
						'title' => esc_html__( 'Center', 'gdpr-cookie-consent-elementor' ),
						'icon'  => 'eicon-text-align-center',
					),
					'right'  => array(
						'title' => esc_html__( 'Right', 'gdpr-cookie-consent-elementor' ),
						'icon'  => 'eicon-text-align-right',
					),
				),
				'default'   => 'left',
				'selectors' => array(
					'{{WRAPPER}} .gdpr-cookie-consent-message' => 'text-align: {{VALUE}};',
				),
			)
		);

		$this->add_responsive_control(
			'gdpr_message_padding',
			array(
				'label'      => esc_html__( 'Padding', 'gdpr-cookie-consent-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .gdpr-cookie-consent-message' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'gdpr_message_margin',
			array(
				'label'      => esc_html__( 'Margin', 'gdpr-cookie-consent-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .gdpr-cookie-consent-message' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();

		// Accept Button Style Section.
		$this->start_controls_section(
			'section_accept_button_style',
			array(
				'label' => esc_html__( 'Accept Button', 'gdpr-cookie-consent-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'accept_button_typography',
				'global'   => array(
					'default' => Global_Typography::TYPOGRAPHY_ACCENT,
				),
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-accept-button',
			)
		);

		$this->start_controls_tabs( 'accept_button_tabs' );

		$this->start_controls_tab(
			'accept_button_normal',
			array(
				'label' => esc_html__( 'Normal', 'gdpr-cookie-consent-elementor' ),
			)
		);

		$this->add_control(
			'accept_button_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'gdpr-cookie-consent-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .gdpr-cookie-consent-accept-button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'accept_button_background',
				'types'    => array( 'classic', 'gradient' ),
				'exclude'  => array( 'image' ),
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-accept-button',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'accept_button_hover',
			array(
				'label' => esc_html__( 'Hover', 'gdpr-cookie-consent-elementor' ),
			)
		);

		$this->add_control(
			'accept_button_hover_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'gdpr-cookie-consent-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gdpr-cookie-consent-accept-button:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'accept_button_hover_background',
				'types'    => array( 'classic', 'gradient' ),
				'exclude'  => array( 'image' ),
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-accept-button:hover',
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'accept_button_border',
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-accept-button',
			)
		);

		$this->add_control(
			'accept_button_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'gdpr-cookie-consent-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .gdpr-cookie-consent-accept-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'accept_button_padding',
			array(
				'label'      => esc_html__( 'Padding', 'gdpr-cookie-consent-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .gdpr-cookie-consent-accept-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'accept_button_box_shadow',
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-accept-button',
			)
		);

		$this->end_controls_section();

		// Decline Button Style Section.
		$this->start_controls_section(
			'section_decline_button_style',
			array(
				'label' => esc_html__( 'Decline Button', 'gdpr-cookie-consent-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'decline_button_typography',
				'global'   => array(
					'default' => Global_Typography::TYPOGRAPHY_ACCENT,
				),
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-decline-button',
			)
		);

		$this->start_controls_tabs( 'decline_button_tabs' );

		$this->start_controls_tab(
			'decline_button_normal',
			array(
				'label' => esc_html__( 'Normal', 'gdpr-cookie-consent-elementor' ),
			)
		);

		$this->add_control(
			'decline_button_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'gdpr-cookie-consent-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '',
				'selectors' => array(
					'{{WRAPPER}} .gdpr-cookie-consent-decline-button' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'decline_button_background',
				'types'    => array( 'classic', 'gradient' ),
				'exclude'  => array( 'image' ),
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-decline-button',
			)
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
			'decline_button_hover',
			array(
				'label' => esc_html__( 'Hover', 'gdpr-cookie-consent-elementor' ),
			)
		);

		$this->add_control(
			'decline_button_hover_text_color',
			array(
				'label'     => esc_html__( 'Text Color', 'gdpr-cookie-consent-elementor' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .gdpr-cookie-consent-decline-button:hover' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'decline_button_hover_background',
				'types'    => array( 'classic', 'gradient' ),
				'exclude'  => array( 'image' ),
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-decline-button:hover',
			)
		);

		$this->end_controls_tab();

		$this->end_controls_tabs();

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'decline_button_border',
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-decline-button',
			)
		);

		$this->add_control(
			'decline_button_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'gdpr-cookie-consent-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .gdpr-cookie-consent-decline-button' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'decline_button_padding',
			array(
				'label'      => esc_html__( 'Padding', 'gdpr-cookie-consent-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .gdpr-cookie-consent-decline-button' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'decline_button_box_shadow',
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-decline-button',
			)
		);

		$this->end_controls_section();

		// Container Style Section.
		$this->start_controls_section(
			'section_container_style',
			array(
				'label' => esc_html__( 'Container', 'gdpr-cookie-consent-elementor' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Background::get_type(),
			array(
				'name'     => 'container_background',
				'types'    => array( 'classic', 'gradient' ),
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-container',
			)
		);

		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'container_border',
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-container',
			)
		);

		$this->add_control(
			'container_border_radius',
			array(
				'label'      => esc_html__( 'Border Radius', 'gdpr-cookie-consent-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .gdpr-cookie-consent-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_responsive_control(
			'container_padding',
			array(
				'label'      => esc_html__( 'Padding', 'gdpr-cookie-consent-elementor' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%', 'em', 'rem', 'custom' ),
				'selectors'  => array(
					'{{WRAPPER}} .gdpr-cookie-consent-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'container_box_shadow',
				'selector' => '{{WRAPPER}} .gdpr-cookie-consent-container',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output on the frontend.
	 *
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		// Generate nonce for AJAX requests.
		$nonce = wp_create_nonce( 'gdpr_consent_nonce' );

		// Check if category management is enabled.
		$enable_categories = isset( $settings['enable_category_management'] ) && 'yes' === $settings['enable_category_management'];
		$display_mode = isset( $settings['category_display_mode'] ) ? $settings['category_display_mode'] : 'modal';
		$show_customize = isset( $settings['show_customize_button'] ) && 'yes' === $settings['show_customize_button'];
		$customize_text = isset( $settings['customize_button_text'] ) ? $settings['customize_button_text'] : __( 'Customize Cookies', 'gdpr-cookie-consent-elementor' );

		// Get categories if enabled.
		$categories = array();
		$categories_data = array();
		if ( $enable_categories ) {
			$category_manager = new \GDPR_Cookie_Consent_Elementor\Cookie_Category_Manager();
			$categories = $category_manager->get_categories();
			foreach ( $categories as $category ) {
				$categories_data[] = array(
					'id'             => $category['id'],
					'name'           => $category['name'],
					'description'    => $category['description'],
					'required'       => isset( $category['required'] ) && $category['required'],
					'default_enabled' => isset( $category['default_enabled'] ) && $category['default_enabled'],
				);
			}
		}

		$this->add_render_attribute( 'container', 'class', 'gdpr-cookie-consent-container' );
		$this->add_render_attribute( 'container', 'data-close-popup', $settings['close_popup_on_click'] );
		$this->add_render_attribute( 'container', 'data-close-on-button', $settings['close_popup_on_button'] );
		$this->add_render_attribute( 'container', 'data-nonce', $nonce );
		$this->add_render_attribute( 'container', 'data-category-mode', $enable_categories ? 'yes' : 'no' );
		if ( $enable_categories ) {
			$this->add_render_attribute( 'container', 'data-display-mode', $display_mode );
			$this->add_render_attribute( 'container', 'data-show-customize', $show_customize ? 'yes' : 'no' );
			$this->add_render_attribute( 'container', 'data-customize-text', esc_attr( $customize_text ) );
			if ( ! empty( $categories_data ) ) {
				$this->add_render_attribute( 'container', 'data-categories', wp_json_encode( $categories_data ) );
			}
		}
		$this->add_render_attribute( 'message', 'class', 'gdpr-cookie-consent-message' );
		$this->add_render_attribute( 'accept_button', 'class', 'gdpr-cookie-consent-accept-button' );
		$this->add_render_attribute( 'accept_button', 'type', 'button' );
		$this->add_render_attribute( 'accept_button', 'data-action', 'accept' );
		$this->add_render_attribute( 'decline_button', 'class', 'gdpr-cookie-consent-decline-button' );
		$this->add_render_attribute( 'decline_button', 'type', 'button' );
		$this->add_render_attribute( 'decline_button', 'data-action', 'decline' );

		// Customize button attributes.
		if ( $enable_categories && $show_customize ) {
			$this->add_render_attribute( 'customize_button', 'class', 'gdpr-cookie-consent-customize-button' );
			$this->add_render_attribute( 'customize_button', 'type', 'button' );
			$this->add_render_attribute( 'customize_button', 'data-action', 'customize' );
		}

		?>
		<div <?php echo wp_kses_post( $this->get_render_attribute_string( 'container' ) ); ?>>
			<div <?php echo wp_kses_post( $this->get_render_attribute_string( 'message' ) ); ?>>
				<?php echo wp_kses_post( wpautop( $settings['gdpr_message'] ) ); ?>
			</div>

			<?php
			// Show inline categories based on display mode.
			$show_inline = $enable_categories && ! empty( $categories ) && ( 'inline' === $display_mode || 'both' === $display_mode );
			if ( $show_inline ) :
				?>
				<div class="gdpr-cookie-consent-categories">
					<?php foreach ( $categories as $category ) : ?>
						<?php
						$category_id = isset( $category['id'] ) ? $category['id'] : '';
						$required = isset( $category['required'] ) && $category['required'];
						$default_enabled = isset( $category['default_enabled'] ) && $category['default_enabled'];
						?>
						<div class="gdpr-cookie-category" data-category-id="<?php echo esc_attr( $category_id ); ?>">
							<label>
								<input 
									type="checkbox" 
									class="gdpr-category-checkbox" 
									data-category-id="<?php echo esc_attr( $category_id ); ?>"
									<?php checked( $required || $default_enabled ); ?>
									<?php disabled( $required ); ?>
								/>
								<span class="gdpr-category-name"><?php echo esc_html( $category['name'] ); ?></span>
								<?php if ( $required ) : ?>
									<span class="gdpr-category-required"><?php esc_html_e( '(Required)', 'gdpr-cookie-consent-elementor' ); ?></span>
								<?php endif; ?>
							</label>
							<?php if ( ! empty( $category['description'] ) ) : ?>
								<p class="gdpr-category-description"><?php echo esc_html( $category['description'] ); ?></p>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="gdpr-cookie-consent-quick-actions">
					<button type="button" class="gdpr-accept-all-button"><?php esc_html_e( 'Accept All', 'gdpr-cookie-consent-elementor' ); ?></button>
					<button type="button" class="gdpr-reject-all-button"><?php esc_html_e( 'Reject All', 'gdpr-cookie-consent-elementor' ); ?></button>
				</div>
			<?php endif; ?>

			<div class="gdpr-cookie-consent-buttons">
				<button <?php echo wp_kses_post( $this->get_render_attribute_string( 'accept_button' ) ); ?>>
					<?php echo esc_html( $settings['accept_button_text'] ); ?>
				</button>
				<?php if ( $enable_categories && $show_customize ) : ?>
					<button <?php echo wp_kses_post( $this->get_render_attribute_string( 'customize_button' ) ); ?>>
						<?php echo esc_html( $customize_text ); ?>
					</button>
				<?php endif; ?>
				<button <?php echo wp_kses_post( $this->get_render_attribute_string( 'decline_button' ) ); ?>>
					<?php echo esc_html( $settings['decline_button_text'] ); ?>
				</button>
			</div>

			<?php
			// Preferences Modal (shown when display mode is modal or both).
			if ( $enable_categories && ! empty( $categories ) && ( 'modal' === $display_mode || 'both' === $display_mode ) ) :
				?>
				<div class="gdpr-preferences-modal-overlay" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="gdpr-modal-title" tabindex="-1">
					<div class="gdpr-preferences-modal">
						<div class="gdpr-modal-header">
							<h2 id="gdpr-modal-title"><?php esc_html_e( 'Cookie Preferences', 'gdpr-cookie-consent-elementor' ); ?></h2>
							<button type="button" class="gdpr-modal-close" aria-label="<?php esc_attr_e( 'Close', 'gdpr-cookie-consent-elementor' ); ?>">&times;</button>
						</div>
						<div class="gdpr-modal-body">
							<p class="gdpr-modal-description"><?php esc_html_e( 'Manage your cookie preferences. Essential cookies cannot be disabled as they are necessary for the website to function.', 'gdpr-cookie-consent-elementor' ); ?></p>
							<div class="gdpr-modal-categories">
								<?php foreach ( $categories as $category ) : ?>
									<?php
									$category_id = isset( $category['id'] ) ? $category['id'] : '';
									$required = isset( $category['required'] ) && $category['required'];
									$default_enabled = isset( $category['default_enabled'] ) && $category['default_enabled'];
									?>
									<div class="gdpr-modal-category" data-category-id="<?php echo esc_attr( $category_id ); ?>">
										<label class="gdpr-modal-category-label">
											<input 
												type="checkbox" 
												class="gdpr-modal-category-checkbox" 
												data-category-id="<?php echo esc_attr( $category_id ); ?>"
												<?php checked( $required || $default_enabled ); ?>
												<?php disabled( $required ); ?>
												<?php if ( $required ) : ?>
													aria-disabled="true"
												<?php endif; ?>
											/>
											<span class="gdpr-modal-category-name"><?php echo esc_html( $category['name'] ); ?></span>
											<?php if ( $required ) : ?>
												<span class="gdpr-modal-category-required" aria-label="<?php esc_attr_e( 'Required - cannot be disabled', 'gdpr-cookie-consent-elementor' ); ?>"><?php esc_html_e( '(Required)', 'gdpr-cookie-consent-elementor' ); ?></span>
											<?php endif; ?>
										</label>
										<?php if ( ! empty( $category['description'] ) ) : ?>
											<p class="gdpr-modal-category-description" id="gdpr-category-desc-<?php echo esc_attr( $category_id ); ?>"><?php echo esc_html( $category['description'] ); ?></p>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
						<div class="gdpr-modal-footer">
							<button type="button" class="gdpr-accept-all-modal-button"><?php esc_html_e( 'Accept All', 'gdpr-cookie-consent-elementor' ); ?></button>
							<button type="button" class="gdpr-reject-all-modal-button"><?php esc_html_e( 'Reject All', 'gdpr-cookie-consent-elementor' ); ?></button>
							<button type="button" class="gdpr-save-preferences-button button-primary"><?php esc_html_e( 'Save Preferences', 'gdpr-cookie-consent-elementor' ); ?></button>
						</div>
					</div>
				</div>
				<div class="gdpr-confirmation-message" role="status" aria-live="polite" style="display: none;">
					<?php esc_html_e( 'Preferences saved successfully!', 'gdpr-cookie-consent-elementor' ); ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}
}

