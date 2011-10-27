<?php
/**
 * @package eu_urho_winery
 */
class eu_urho_winery_injector
{
    var $mvc = null;
    var $request = null;

    public function __construct()
    {
        $this->mvc = midgardmvc_core::get_instance();
    }

    /**
     * @todo: docs
     */
    public function inject_process(midgardmvc_core_request $request)
    {
        static $connected = false;

        if ( ! $connected)
        {
            // Subscribe to content changed signals from Midgard
            midgard_object_class::connect_default('eu_urho_winery_plantation', 'action-create', array('eu_urho_winery_injector', 'check_plantation'), array($request));
            midgard_object_class::connect_default('eu_urho_winery_plantation', 'action-update', array('eu_urho_winery_injector', 'check_plantation'), array($request));
            $connected = true;
        }

        $component = $request->get_node()->get_component();
    }

    /**
     * @todo: docs
     */
    public function inject_template(midgardmvc_core_request $request)
    {
        // We inject the template to provide eu_urho_winery styling
        $request->add_component_to_chain(midgardmvc_core::get_instance()->component->get('eu_urho_winery'), true);

        $route = $request->get_route();

        if (   (   $route->id == "plantation_index"
                || $route->id == "plantation_read")
            && midgardmvc_ui_create_injector::can_use())
        {
            //$this->add_datetime_elements();
        }
    }

    /**
     * Adds js and css files to head
     */
    private function add_datetime_elements()
    {
        $this->mvc->head->enable_jquery();
        $this->mvc->head->enable_jquery_ui();

        $this->mvc->head->add_jsfile(MIDGARDMVC_STATIC_URL . '/eu_urho_winery/js/jquery.ui.datetime.min.js');
        $this->mvc->head->add_jsfile(MIDGARDMVC_STATIC_URL . '/eu_urho_winery/js/init.js');

        $this->mvc->head->add_link
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDGARDMVC_STATIC_URL . '/eu_urho_winery/css/jquery.ui.datetime.css'
            )
        );
    }

    /**
     * Validate and do last minute changes on plantation objects
     */
    public static function check_plantation(eu_urho_winery_plantation $plantation, $params)
    {
          // clean up
          $plantation->title = strip_tags($plantation->title);
          $plantation->planted = strip_tags($plantation->planted);
          $plantation->system = strip_tags($plantation->system);
    }
}
?>
