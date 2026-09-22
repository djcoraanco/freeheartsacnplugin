<?php
if (!defined('ABSPATH')) {
    exit;
}

class FreeHeartScan_Elementor_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'free_heart_scan_widget';
    }

    public function get_title() {
        return esc_html__('Free Heart Scan Form', 'free-heart-scan');
    }

    public function get_icon() {
        return 'eicon-form-horizontal';
    }

    public function get_categories() {
        return array('general', 'theme-elements');
    }

    public function get_keywords() {
        return array('heart', 'scan', 'lead', 'form', 'life imaging');
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            array(
                'label' => esc_html__('Form Settings', 'free-heart-scan'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            )
        );

        $this->add_control(
            'form_title',
            array(
                'label' => esc_html__('Custom Title', 'free-heart-scan'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => esc_html__('Free Heart Scan Preventive Evaluation', 'free-heart-scan'),
            )
        );

        $this->end_controls_section();
    }

    protected function render() {
        wp_enqueue_style('fhs-form-css');
        wp_enqueue_script('fhs-form-js');
        include FHS_PLUGIN_DIR . 'templates/form-template.php';
    }
}
