<?php
use Elementor\Repeater;
use Elementor\Core\Schemes\Typography;
use Elementor\Utils;
use Elementor\Control_Media;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;

defined( 'ABSPATH' ) || die();

class Easy_Currency_Switcher_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name.
     *
     * Retrieve rsgallery widget name.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget name.
     */
    public function get_name() {
        return 'rt_currecny_switcher';
    }   
    /**
     * Get widget title.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget title.
     */
    public function get_title() {
        return esc_html__( 'Easy Currency Switcher', 'easy-currency' );
    }

    /**
     * Get widget icon.
     *
     * @since 1.0.0
     * @access public
     *
     * @return string Widget icon.
     */
    public function get_icon() {
        return 'glyph-icon flaticon-price';
    }


    public function get_categories() {
        return [ 'easy_currency_category' ];
    }

    public function get_keywords() {
        return [ 'currency switcher', 'currency' ];
    }


	protected function register_controls() {




        $this->start_controls_section(
		    '_section_style_button',
		    [
		        'label' => esc_html__( 'Button', 'easy-currency' ),
		        'tab' => Controls_Manager::TAB_STYLE,
		    ]
		);

		$this->start_controls_tabs( '_tabs_button' );

		$this->start_controls_tab(
            'style_normal_tab',
            [
                'label' => esc_html__( 'Normal', 'easy-currency' ),
            ]
        ); 

		$this->add_control(
		    'btn_text_color',
		    [
		        'label' => esc_html__( 'Text Color', 'easy-currency' ),
		        'type' => Controls_Manager::COLOR,		      
		        'selectors' => [
		            '{{WRAPPER}} .easy-currency-switcher-select, {{WRAPPER}} .easy-currency-switcher-select.open' => 'color: {{VALUE}};',
		        ],
		    ]
		);

        $this->add_control(
		    'btn_text_color_sticky',
		    [
		        'label' => esc_html__( 'Text Color (Sticky Header)', 'easy-currency' ),
		        'type' => Controls_Manager::COLOR,		      
                'default' => '#4c5671',
		        'selectors' => [
		            '.header-inner.sticky {{WRAPPER}} .easy-currency-switcher-select, .header-inner.sticky {{WRAPPER}} .easy-currency-switcher-select.open' => 'color: {{VALUE}};',
		        ],
		    ]
		);

		$this->add_control(
		    'btn_text_padding',
		    [
		        'label' => esc_html__( 'Padding', 'easy-currency' ),
		        'type' => Controls_Manager::DIMENSIONS,
		        'size_units' => [ 'px', 'em', '%' ],
		        'selectors' => [
		            '{{WRAPPER}} .easy-currency-switcher-select, {{WRAPPER}} .easy-currency-switcher-select.open' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
		        ],
		    ]
		);
		$this->add_group_control(
		    Group_Control_Border::get_type(),
		    [
				'label' => esc_html__( 'Border', 'easy-currency' ),
		        'name' => 'btn_text_border',
		        'selector' => '{{WRAPPER}} .easy-currency-switcher-select, {{WRAPPER}} .easy-currency-switcher-select.open',
		    ]
		);

		$this->add_group_control(
		    Group_Control_Typography::get_type(),
		    [
		        'name' => 'btn_typography',
		        'selector' => '{{WRAPPER}} .easy-currency-switcher-select',
		    ]
		);

		$this->add_group_control(
		    Group_Control_Background::get_type(),
			[
				'name' => 'background_normal',
				'label' => esc_html__( 'Background', 'easy-currency' ),
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .easy-currency-switcher-select, {{WRAPPER}} .easy-currency-switcher-select.open',
			]
		);

        $this->add_control(
			'sticky_options',
			[
				'label' => esc_html__( 'Sticky Background', 'easy-currency' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $this->add_group_control(
		    Group_Control_Background::get_type(),
			[
				'name' => 'background_normal_sticky',
				'label' => esc_html__( 'Background', 'easy-currency' ),
				'types' => [ 'classic', 'gradient' ],
				'selector' => '.header-inner.sticky {{WRAPPER}} .easy-currency-switcher-select, .header-inner.sticky {{WRAPPER}} .easy-currency-switcher-select.open',
                
			]
		);
        

		$this->add_control(
		    'button_border_radius',
		    [
		        'label' => esc_html__( 'Border Radius', 'easy-currency' ),
		        'type' => Controls_Manager::DIMENSIONS,
		        'size_units' => [ 'px', '%' ],
		        'selectors' => [
		            '{{WRAPPER}} .easy-currency-switcher-select' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',  
		        ],
                'separator' => 'before',
		    ]
		);

		$this->add_group_control(
		    Group_Control_Box_Shadow::get_type(),
		    [
		        'name' => 'button_box_shadow',
		        'selector' => '{{WRAPPER}} .easy-currency-switcher-select',
		    ]
		);

		$this->end_controls_tab();

		$this->start_controls_tab(
            'style_hover_tab',
            [
                'label' => esc_html__( 'Hover', 'easy-currency' ),
            ]
        ); 
		$this->add_control(
		    'btn_text_hover_color',
		    [
		        'label' => esc_html__( 'Text Color', 'easy-currency' ),
		        'type' => Controls_Manager::COLOR,		      
		        'selectors' => [
		            '{{WRAPPER}} .easy-currency-switcher-select:hover' => 'color: {{VALUE}};',
		        ],
		    ]
		);
        $this->add_control(
		    'btn_text_hover_color_sticky',
		    [
		        'label' => esc_html__( 'Text Color (Sticky Header)', 'easy-currency' ),
		        'type' => Controls_Manager::COLOR,		      
		        'selectors' => [
		            '.header-inner.sticky {{WRAPPER}} .easy-currency-switcher-select:hover' => 'color: {{VALUE}};',
		        ],
		    ]
		);
        

		$this->add_group_control(
		    Group_Control_Background::get_type(),
			[
				'name' => 'hover_background',
				'label' => esc_html__( 'Background', 'easy-currency' ),
				'types' => [ 'classic', 'gradient' ],
				'selector' => '{{WRAPPER}} .easy-currency-switcher-select:hover, {{WRAPPER}} .easy-currency-switcher-select.open:hover',
			]
		);		

        $this->add_control(
			'hover_sticky_options',
			[
				'label' => esc_html__( 'Sticky Hover Background', 'easy-currency' ),
				'type' => \Elementor\Controls_Manager::HEADING,
				'separator' => 'before',
			]
		);

        $this->add_group_control(
		    Group_Control_Background::get_type(),
			[
				'name' => 'hover_background_sticky',
				'label' => esc_html__( 'Background', 'easy-currency' ),
				'types' => [ 'classic', 'gradient' ],
				'selector' => '.header-inner.sticky {{WRAPPER}} .easy-currency-switcher-select:hover, .header-inner.sticky {{WRAPPER}} .easy-currency-switcher-select.open:hover',
			]
		);		
        


		$this->add_group_control(
		    Group_Control_Border::get_type(),
		    [
		        'name' => 'button_hover_border',
		        'selector' => '{{WRAPPER}} .easy-currency-switcher-select:hover',
                'separator' => 'before',
		    ]
		);

		$this->add_control(
		    'button_hover_border_radius',
		    [
		        'label' => esc_html__( 'Border Radius', 'easy-currency' ),
		        'type' => Controls_Manager::DIMENSIONS,
		        'size_units' => [ 'px', '%' ],
		        'selectors' => [
		            '{{WRAPPER}} .easy-currency-switcher-select:hover' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
		        ],
		    ]
		);

		$this->add_group_control(
		    Group_Control_Box_Shadow::get_type(),
		    [
		        'name' => 'button_hover_box_shadow',
		        'selector' => '{{WRAPPER}} .easy-currency-switcher-select:hover:hover',
		    ]
		);
		$this->end_controls_tab();
		$this->end_controls_tabs();
		$this->end_controls_section();

}


	protected function render() {

        $settings = $this->get_settings_for_display();
		$ECCW_CURRENCY_VIEW = new ECCW_CURRENCY_VIEW();

		$switcher_html = $ECCW_CURRENCY_VIEW->eccw_get_currency_switcher();

		$allowed_html = [
			'div' => [
				'class' => [],
			],
			'form' => [
				'action' => [],
				'id'     => [],
				'class'  => [],
			],
			'input' => [
				'type'  => [],
				'name'  => [],
				'value' => [],
			],
			'select' => [
				'class' => [],
				'name'  => [],
			],
			'option' => [
				'value'      => [],
				'selected'   => [],
				'data-custom'=> [], // Custom data attribute
			],
			'php' => [], // Handles `<?php` tags, though PHP tags are usually not allowed in `wp_kses` context
		];

		echo wp_kses( $switcher_html, $allowed_html);
    }
    
}