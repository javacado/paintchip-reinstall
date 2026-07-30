<?php

namespace WBCR\Factory_Templates_134\Pages;

/**
 * Общий класс прослойка для страниц Clearfy и его компоннетов.
 * В этом классе добавляются общие ресурсы и элементы, необходимые для всех связанных плагинов.
 *
 * @author        Alex Kovalev <alex.kovalevv@gmail.com>, Github: https://github.com/alexkovalevv
 * @since         2.0.5
 */

// Exit if accessed directly
if( !defined('ABSPATH') ) {
	exit;
}

//global $ssssdfsfsdf;

/**
 * Class Wbcr_FactoryPages480_ImpressiveThemplate
 *
 * @method string getRatingWidget(array $args = []) - get widget content rating
 * @method string getDonateWidget() - get widget content donate
 * @method string getSubscribeWidget()
 * @method string getBusinessSuggetionWidget()
 */
class PageBase extends \WBCR\Factory_Templates_134\Impressive {

	/**
	 * {@inheritDoc}
	 *
	 * @since   2.0.5 - добавлен
	 * @var bool
	 */
	public $show_right_sidebar_in_options = true;

	/**
	 * {@inheritDoc}
	 *
	 * @since  2.0.5 - добавлен
	 * @var bool
	 */
	public $available_for_multisite = true;

	/**
	 * {@inheritDoc}
	 *
	 * @since  2.0.6 - добавлен
	 * @var bool
	 */
	public $internal = true;

	/**
	 * Show on the page a search form for search options of plugin?
	 *
	 * @since  2.2.0 - Added
	 * @var bool - true show, false hide
	 */
	public $show_search_options_form;

	/**
	 * @param \Wbcr_Factory480_Plugin $plugin
	 */
	public function __construct(\Wbcr_Factory480_Plugin $plugin)
	{
		parent::__construct($plugin);

		if( is_null($this->show_search_options_form) ) {
			$this->show_search_options_form = false;
			if( "options" === $this->type ) {
				$this->show_search_options_form = true;
			}
		}

		if( "options" === $this->type && "hide_my_wp" !== $this->id ) {
			$this->register_options_to_search();
		}

		add_action("wp_ajax_wbcr-clearfy-subscribe-for-{$this->plugin->getPluginName()}", [
			$this,
			'subsribe_widget_ajax_handler'
		]);
	}

	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return null|string
	 */
	public function __call($name, $arguments)
	{
		if( substr($name, 0, 3) == 'get' ) {
			$called_method_name = 'show' . substr($name, 3);
			if( method_exists($this, $called_method_name) ) {
				ob_start();

				$this->$called_method_name($arguments);
				$content = ob_get_contents();
				ob_end_clean();

				return $content;
			}
		}

		return null;
	}

	/**
	 * Requests assets (js and css) for the page.
	 *
	 * @param \Wbcr_Factory480_ScriptList $scripts
	 * @param \Wbcr_Factory480_StyleList $styles
	 *
	 * @return void
	 * @see Wbcr_FactoryPages480_AdminPage
	 *
	 */
	public function assets($scripts, $styles)
	{
		parent::assets($scripts, $styles);

		$this->styles->add(FACTORY_TEMPLATES_134_URL . '/assets/css/clearfy-base.css');

		// todo: вынести все общие скрипты и стили фреймворка, продумать совместимость с другими плагинами
		if( defined('WCL_PLUGIN_URL') ) {
			$this->styles->add(WCL_PLUGIN_URL . '/admin/assets/css/general.css');
		}

		$this->scripts->add(FACTORY_TEMPLATES_134_URL . '/assets/js/clearfy-widgets.js', [
			'jquery',
			'wfactory-480-core-general',
			'wbcr-factory-templates-134-global'
		], 'wbcr-factory-templates-134-widgets');

		// Script for search form on plugin options
		if( $this->show_search_options_form ) {
			$this->styles->add(FACTORY_TEMPLATES_134_URL . '/assets/css/libs/autocomplete.css');

			$this->scripts->add(FACTORY_TEMPLATES_134_URL . '/assets/js/libs/jquery.autocomplete.min.js');
			$this->scripts->add(FACTORY_TEMPLATES_134_URL . '/assets/js/clearfy-search-options.js');
		}

		/**
		 * Allows you to enqueue scripts to the internal pages of the plugin.
		 * $this->getResultId() - page id + plugin name = quick_start-wbcr_clearfy
		 *
		 * @since 2.0.5
		 */
		do_action('wbcr/clearfy/page_assets', $this->getResultId(), $scripts, $styles);
	}

	/**
	 * Регистрируем ajax обработчик для текущей страницы
	 *
	 * @since 2.0.7
	 */
	public function subsribe_widget_ajax_handler()
	{
		wbcr_factory_templates_134_subscribe($this->plugin);
	}

	/**
	 * @return \Wbcr_Factory480_Request
	 */
	public function request()
	{
		return $this->plugin->request;
	}

	/**
	 * @param      $option_name
	 * @param bool $default *
	 *
	 * @return mixed|void
	 * @since 2.0.5
	 *
	 */
	public function getPopulateOption($option_name, $default = false)
	{
		return $this->plugin->getPopulateOption($option_name, $default);
	}

	/**
	 * @param      $option_name
	 * @param bool $default
	 *
	 * @return mixed|void
	 */
	public function getOption($option_name, $default = false)
	{
		return $this->plugin->getOption($option_name, $default);
	}

	/**
	 * @param $option_name
	 * @param $value
	 *
	 * @return void
	 */
	public function updatePopulateOption($option_name, $value)
	{
		$this->plugin->updatePopulateOption($option_name, $value);
	}

	/**
	 * @param $option_name
	 * @param $value
	 *
	 * @return void
	 */
	public function updateOption($option_name, $value)
	{
		$this->plugin->updateOption($option_name, $value);
	}

	/**
	 * @param $option_name
	 *
	 * @return void
	 */
	public function deletePopulateOption($option_name)
	{
		$this->plugin->deletePopulateOption($option_name);
	}

	/**
	 * @param $option_name
	 *
	 * @return void
	 */
	public function deleteOption($option_name)
	{
		$this->plugin->deleteOption($option_name);
	}

	/**
	 * @param string $position
	 *
	 * @return mixed|void
	 */
	protected function getPageWidgets($position = 'bottom')
	{
		$widgets = [];

		if( $position == 'bottom' ) {
			$widgets['rating_widget'] = $this->getRatingWidget();
			//$widgets['donate_widget'] = $this->getDonateWidget();
		} else if( $position == 'right' ) {
			$widgets['business_suggetion'] = $this->getBusinessSuggetionWidget();
			if( $this->plugin->getPluginInfoAttr('subscribe_widget') && !$this->plugin->getPopulateOption('factory_clearfy_user_subsribed') ) {
				$widgets['subscribe'] = $this->getSubscribeWidget();
			}
			$widgets['rating_widget'] = $this->getRatingWidget();
		}

		/**
		 * @since 4.0.9 - является устаревшим
		 */
		$widgets = wbcr_factory_480_apply_filters_deprecated('wbcr_factory_pages_480_imppage_get_widgets', [
			$widgets,
			$position,
			$this->plugin,
			$this
		], '4.0.9', 'wbcr/factory/pages/impressive/widgets');

		/**
		 * @since 4.0.1 - добавлен
		 * @since 4.0.9 - изменено имя
		 */
		$widgets = apply_filters('wbcr/factory/pages/impressive/widgets', $widgets, $position, $this->plugin, $this);

		return $widgets;
	}

	/**
	 * Создает Html разметку виджета для рекламы премиум версии
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.2
	 */
	public function showBusinessSuggetionWidget()
	{
		$plugin_name = $this->plugin->getPluginName();
		$upgrade_price = 0;
		$purchase_url = $this->plugin->get_support()->get_pricing_url(true, 'right_sidebar_ads');

		$default_features = [
			'4_premium' => __('4 premium components now;', 'wbcr_factory_templates_134'),
			'40_premium' => __('40 new premium components within a year for the single price;', 'wbcr_factory_templates_134'),
			'multisite_support' => __('Multisite support;', 'wbcr_factory_templates_134'),
			'advance_settings' => __('Advanced settings;', 'wbcr_factory_templates_134'),
			'no_ads' => __('No ads;', 'wbcr_factory_templates_134'),
			'perfect_support' => __('Perfect support.', 'wbcr_factory_templates_134')
		];

		/**
		 * @since 2.0.8 - added
		 */
		$suggetion_title = __('MORE IN CLEARFY <span>BUSINESS</span>', 'wbcr_factory_templates_134');
		$suggetion_title = apply_filters('wbcr/clearfy/pages/suggetion_title', $suggetion_title, $plugin_name, $this->id);

		/**
		 * @since 2.0.8 - deprecated
		 */
		$suggetion_features = wbcr_factory_480_apply_filters_deprecated('wbcr/clearfy/page_bussines_suggetion_features', [
			$default_features,
			$this->plugin->getPluginName(),
			$this->id
		], '2.0.8', 'wbcr/clearfy/pages/suggetion_features');

		/**
		 * @since 2.0.8 - renamed
		 * @since 2.0.6
		 */
		$suggetion_features = apply_filters('wbcr/clearfy/pages/suggetion_features', $suggetion_features, $plugin_name, $this->id);

		if( empty($suggetion_features) ) {
			$suggetion_features = $default_features;
		}
		?>
		<div class="wbcr-factory-sidebar-widget wbcr-factory-templates-134-pro-suggettion">
			<h3><?php echo $suggetion_title; ?></h3>
			<ul>
				<?php if( !empty($suggetion_features) ): ?>
					<?php foreach($suggetion_features as $feature): ?>
						<li><?php echo $feature; ?></li>
					<?php endforeach; ?>
				<?php endif; ?>
			</ul>
			<a href="<?php echo $purchase_url ?>" class="wbcr-factory-purchase-premium" target="_blank"
			   rel="noopener">
				<?php printf(__('Upgrade for $%s', 'wbcr_factory_templates_134'), $upgrade_price) ?>
			</a>
		</div>
		<?php
	}

	/**
	 * Создает html разметку виджета рейтинга
	 *
	 * @param array $args
	 *
	 * @since  2.0.0
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 */
	public function showRatingWidget(array $args)
	{
		if( !isset($args[0]) || empty($args[0]) ) {
			$page_url = "https://wordpress.org/support/plugin/clearfy/reviews";
		} else {
			$page_url = $args[0];
		}

		$page_url = apply_filters('wbcr_factory_pages_480_imppage_rating_widget_url', $page_url, $this->plugin->getPluginName(), $this->getResultId());

		?>
		<div class="wbcr-factory-sidebar-widget">
			<strong><?php _e( 'Leave a review:', 'wbcr_factory_templates_134' ); ?></strong>
			<?php _e( 'Liking the plugin? A quick review would mean a lot and helps us make it even better.', 'wbcr_factory_templates_134' ); ?>

			<span>
				<i class="dashicons dashicons-star-filled"></i>
				<a class="wbcr-leave-review-link" href="<?php echo $page_url; ?>" title="Go rate us" target="_blank">
					<?php _e( 'Leave a Review', 'wbcr_factory_templates_134' ); ?>
				</a>
			</span>
		</div>
		<?php
	}

	/**
	 * Создает html размету виджета доната
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  2.0.0
	 */
	public function showDonateWidget()
	{
		?>
		<div class="wbcr-factory-sidebar-widget">
			<p>
				<strong><?php _e('Donation for plugin development', 'wbcr_factory_templates_134'); ?></strong>
			</p>
			<?php if( get_locale() !== 'ru_RU' ): ?>
				<form id="wbcr-factory-paypal-donation-form" action="https://www.paypal.com/cgi-bin/webscr"
				      method="post" target="_blank">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="VDX7JNTQPNPFW">
					<div class="wbcr-factory-donation-price">5$</div>
					<input type="image" src="<?php echo FACTORY_TEMPLATES_134_URL ?>/templates/assets/img/paypal-donate.png"
					       border="0" name="submit" alt="PayPal – The safer, easier way to pay online!">
				</form>
			<?php else: ?>
				<iframe frameborder="0" allowtransparency="true" scrolling="no"
				        src="https://money.yandex.ru/embed/donate.xml?account=410011242846510&quickpay=donate&payment-type-choice=on&mobile-payment-type-choice=on&default-sum=300&targets=%D0%9D%D0%B0+%D0%BF%D0%BE%D0%B4%D0%B4%D0%B5%D1%80%D0%B6%D0%BA%D1%83+%D0%BF%D0%BB%D0%B0%D0%B3%D0%B8%D0%BD%D0%B0+%D0%B8+%D1%80%D0%B0%D0%B7%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D0%BA%D1%83+%D0%BD%D0%BE%D0%B2%D1%8B%D1%85+%D1%84%D1%83%D0%BD%D0%BA%D1%86%D0%B8%D0%B9.+&target-visibility=on&project-name=Webcraftic&project-site=&button-text=05&comment=on&hint=%D0%9A%D0%B0%D0%BA%D1%83%D1%8E+%D1%84%D1%83%D0%BD%D0%BA%D1%86%D0%B8%D1%8E+%D0%BD%D1%83%D0%B6%D0%BD%D0%BE+%D0%B4%D0%BE%D0%B1%D0%B0%D0%B2%D0%B8%D1%82%D1%8C+%D0%B2+%D0%BF%D0%BB%D0%B0%D0%B3%D0%B8%D0%BD%3F&mail=on&successURL="
				        width="508" height="187"></iframe>
			<?php endif; ?>
		</div>
		<?php
	}

    public function showSubscribeWidget()
    {
        ?>
        <div id="wbcr-clr-subscribe-widget" class="wbcr-factory-sidebar-widget wbcr-factory-subscribe-widget">
            <p><strong><?php esc_html_e('Stay connected for news & updates!', 'comments-plus'); ?></strong></p>
            <div class="wbcr-clr-subscribe-widget-body">

                <div class="wbcr-factory-subscribe-widget__message-contanier">
                    <div class="wbcr-factory-subscribe-widget__text wbcr-factory-subscribe-widget__text--success">
                        <?php _e("Thank you, you have successfully subscribed!", 'wbcr_factory_clearfy_246') ?>
                    </div>
                    <div class="wbcr-factory-subscribe-widget__text wbcr-factory-subscribe-widget__text--success2">
                        <?php _e("Thank you for your subscription.", 'wbcr_factory_clearfy_246'); ?>
                    </div>
                </div>

                <form id="wbcr-factory-subscribe-widget__subscribe-form" method="post" data-nonce="<?php echo wp_create_nonce('clearfy_subscribe_for_' . $this->plugin->getPluginName()) ?>">
                    <input id="wbcr-factory-subscribe-widget__email" class="wbcr-factory-subscribe-widget__field" type="email" name="email" placeholder="<?php _e('Your email address', 'comments-plus'); ?>" required>
                    <input type="hidden" id="wbcr-factory-subscribe-widget__plugin-name" value="<?php echo esc_attr($this->plugin->getPluginName()); ?>">
                    <input type="submit" class="btn wbcr-factory-subscribe-widget__button" value="<?php _e('Sign me up', 'comments-plus'); ?>">
                </form>
            </div>
        </div>

        <?php
    }

	/**
	 * Registers page options in the options registry
	 *
	 * This will allow the user to search all the plugin options.
	 */
	public function register_options_to_search()
	{
		require_once FACTORY_TEMPLATES_134_DIR . '/includes/class-search-options.php';

		$options = $this->getPageOptions();
		$page_url = $this->getBaseUrl();
		$page_id = $this->getResultId();

		\WBCR\Factory_Templates_134\Search_Options::register_options($options, $page_url, $page_id);
	}

	/**
	 * Add search plugin options form to each option page
	 */
	public function printAllNotices()
	{
		parent::printAllNotices(); // TODO: Change the autogenerated stub

		if( !$this->show_search_options_form ) {
			return;
		}
		?>
		<div id="wbcr-factory-templates-134__search_options_form" class="wbcr-factory-templates-134__autocomplete-wrap">
			<label for="autocomplete" class="wbcr-factory-templates-134__autocomplete-label">
				<?php _e('Can\'t find the settings you need? Use the search by the plugin options:', 'wbcr_factory_templates_134'); ?>
			</label>
			<input type="text" placeholder="<?php _e('Enter the option name to search...', 'wbcr_factory_templates_134'); ?>" name="country" id="wbcr-factory-templates-134__autocomplete"/>

		</div>
		<?php
	}
}

