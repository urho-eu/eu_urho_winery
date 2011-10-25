<?php
/**
 * @package eu_urho_winery
 */
class eu_urho_winery_injector
{
    /**
     * @todo: docs
     */
    public function inject_process(midgardmvc_core_request $request)
    {
        static $connected = false;

        if ( ! $connected)
        {
            // Subscribe to content changed signals from Midgard
            midgard_object_class::connect_default('eu_urho_winery_wine', 'action-create', array('eu_urho_winery_injector', 'check_node'), array($request));
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
    }

    /**
     * Sets the wine's ....
     *
     */
    public static function check_node(eu_urho_winery_wine $wine, $params)
    {
        if ($wine->node)
        {
            return;
        }

        $request = midgardmvc_core::get_instance()->context->get_request();
        $node = $request->get_node();
        if ( ! $node )
        {
          return;
        }

        $node_object = $node->get_object();
        if ( ! $node_object instanceof midgardmvc_core_node )
        {
          return;
        }

        # here set whatever before writing to the database
    }
}
?>
