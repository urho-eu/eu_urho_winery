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
            midgard_object_class::connect_default('eu_urho_winery_wine', 'action-create', array('eu_urho_winery_injector', 'check_wine'), array($request));
            midgard_object_class::connect_default('eu_urho_winery_wine', 'action-update', array('eu_urho_winery_injector', 'check_wine'), array($request));
            midgard_object_class::connect_default('eu_urho_winery_harvest', 'action-create', array('eu_urho_winery_injector', 'check_harvest'), array($request));
            midgard_object_class::connect_default('eu_urho_winery_harvest', 'action-update', array('eu_urho_winery_injector', 'check_harvest'), array($request));
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
    public static function check_wine(eu_urho_winery_wine $wine, $params)
    {
        $wine->title = self::cleanup_title($wine->title);
        $wine->name = self::generate_name($wine->title);

        $qs = eu_urho_winery_controllers_wine::prepare_qs($wine->harvest, $wine->name, $wine->guid);
        $qs->execute();
        $wines = $qs->list_objects();
        if (count($wines))
        {
            //make the name unique
            $wine->name .= time();
        }
    }


    /**
     * Validate and do last minute changes on plantation objects
     */
    public static function check_plantation(eu_urho_winery_plantation $plantation, $params)
    {
        $plantation->title = self::cleanup_title($plantation->title);
        $plantation->name = self::generate_name($plantation->title);
        $plantation->planted = strip_tags($plantation->planted);
        $plantation->system = strip_tags($plantation->system);

        $qs = eu_urho_winery_controllers_plantation::prepare_qs($plantation->name, $plantation->guid);
        $qs->execute();
        $plantations = $qs->list_objects();
        if (count($plantations))
        {
            //make the name unique
            $plantation->name .= time();
        }
    }

    /**
     * Validate and do last minute changes on harvest objects
     */
    public static function check_harvest(eu_urho_winery_harvest $harvest, $params)
    {
        $harvest->title = self::cleanup_title($harvest->title);
        $harvest->name = self::generate_name($harvest->title);

        $qs = eu_urho_winery_controllers_harvest::prepare_qs($harvest->year, $harvest->name, $harvest->guid);
        $qs->execute();
        $harvests = $qs->list_objects();

        if (count($harvests))
        {
            //make the name unique
            $harvest->name .= time();
        }
    }

    /**
     * generates a name from a title
     * @param string string to be cleaned up
     * @return string
     */
    public function generate_name($title)
    {
        return preg_replace('/_+/', '_', preg_replace('/\W/', '_', strip_tags(html_entity_decode($title))));
    }

    /**
     * Cleans up a title
     * @param string string to be cleaned up
     * @return string
     */
    public function cleanup_title($title)
    {
        return preg_replace('/\s+/', ' ', preg_replace('/\W/', ' ', strip_tags(html_entity_decode($title))));
    }
}
?>
