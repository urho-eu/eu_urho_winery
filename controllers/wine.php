<?php
class eu_urho_winery_controllers_wine extends eu_urho_winery_controllers_base
{
    /**
     * Calls the CRUD constructor and
     * adds the component's localization domain and sets default language
     */
    public function __construct(midgardmvc_core_request $request)
    {
        parent::__construct($request);
    }

    /**
     * @todo: docs
     */
    public function load_object(array $args)
    {
        parent::load_object(array('class' => get_class($this), 'id' => $args['wine']));
    }

    /**
     * @todo: docs
     */
    public function prepare_new_object()
    {
        parent::load_object(array('class' => get_class($this)));
    }

    /**
     * @todo: docs
     */
    public function get_url_read()
    {
        return $this->mvc->dispatcher->generate_url
        (
            'wine_read', array
            (
                'wine' => $this->object->guid
            ),
            $this->request
        );
    }

    /**
     * @todo: docs
     */
    public function get_url_update()
    {
        return $this->mvc->dispatcher->generate_url
        (
            'wine_read', array
            (
                'wine' => $this->object->guid
            ),
            $this->request
        );
    }

    /**
     * Gets wines for a year
     */
    public function get_wines_for_year(array $args)
    {
        $year = 2009;
        $guid = null;

        if (isset($args['year']))
        {
          $year = $args['year'];
        }
        if (isset($args['guid']))
        {
          $guid = $args['guid'];
        }

        $this->data['wines'] = new midgardmvc_ui_create_container();

        $qs = $this->prepare_qs($year, $guid);
        $qs->execute();
        $wines = $qs->list_objects();

        foreach ($wines as $key => $wine)
        {
            $this->data['wines']->attach($wine);
        }

        // Read container type from config to know whether items can be created to this node
        $this->data['container_type'] = 'http://purl.org/dc/dcmitype/Collection';

        // Define placeholder to be used with UI on empty containers
        $dummy = new eu_urho_winery_wine();
        $dummy->url = '#';
        $this->data['wines']->set_placeholder($dummy);
        $this->data['wines']->set_urlpattern(
            $this->mvc->dispatcher->generate_url(
                'wine_read',
                array(
                    'year' => 2011,
                    'wine' => 'GUID'
                ),
                $this->request
            )
        );
    }

    /**
     * Returns a QuerySelect object
     */
    private function prepare_qs($year = 2009, $guid = null)
    {
        $qc = null;
        $storage = new midgard_query_storage('eu_urho_winery_wine');
        $qs = new midgard_query_select($storage);

        if ($guid)
        {
          $qc = new midgard_query_constraint_group('AND');
          $qc->add_constraint(new midgard_query_constraint(
              new midgard_query_property('guid'),
              '=',
              new midgard_query_value($guid)
          ));
        }

        $year_constraint = new midgard_query_constraint(
            new midgard_query_property('metadata.created'),
            '>=',
            new midgard_query_value($year)
        );

        if ($qc)
        {
            $qc->add_constraint($year_constraint);
        }

        /* if non published wines should be hidden

        if ( ! midgardmvc_ui_create_injector::can_use() )
        {
            // Regular user, hide unapproved articles
            $qc->add_constraint(new midgard_query_constraint(
                new midgard_query_property('metadata.isapproved'),
                '=',
                new midgard_query_value(true)
            ));
        }
        */

        $qs->add_order(new midgard_query_property('metadata.created'), SORT_DESC);
        $qs->set_constraint($qc);

        return $qs;
    }

}
?>