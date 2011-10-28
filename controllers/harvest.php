<?php
class eu_urho_winery_controllers_harvest extends midgardmvc_core_controllers_baseclasses_crud
{
    var $mvc = null;

    /**
     * Calls the CRUD constructor and
     * adds the component's localization domain and sets default language
     */
    public function __construct(midgardmvc_core_request $request)
    {
        parent::__construct($request);
        $this->request = $request;
        $this->mvc = midgardmvc_core::get_instance();
    }

    /**
     * @todo: docs
     */
    public function load_object(array $args)
    {
        if (isset($args['harvest']))
        {
            try
            {
                $qs = $this->prepare_qs($args['harvest']);
                $qs->execute();
                $harvests = $qs->list_objects();
                if (count($harvests))
                {
                    $this->object = new eu_urho_winery_harvest($harvests[0]->guid);
                }
                else
                {
                    $this->prepare_new_object($args);
                }
            }
            catch (midgard_error_exception $e)
            {
                throw new midgardmvc_exception_notfound($e->getMessage());
            }
        }
        else
        {
            throw new midgardmvc_exception_notfound("Please specify a valid harvest");
        }

        if (   ! midgardmvc_ui_create_injector::can_use()
            && (   ! $this->object
                || ! $this->object->is_approved()))
        {
            // Regular user, hide unapproved articles
            // TODO: This check should be moved to authentication
            throw new midgardmvc_exception_notfound("No data published for " . $args['harvest']);
        }

        $this->object->rdfmapper = new midgardmvc_ui_create_rdfmapper($this->object);
        $this->mvc->head->set_title($this->object->title);
    }

    /**
     * @todo: docs
     */
    public function prepare_new_object(array $args)
    {
        $this->object = new eu_urho_winery_harvest();
        $this->object->rdfmapper = new midgardmvc_ui_create_rdfmapper($this->object);
    }

    /**
     * @todo: docs
     */
    public function get_url_read()
    {
        return $this->mvc->dispatcher->generate_url
        (
            'harvest_read', array
            (
                'harvest' => $this->object->title
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
            'harvest_read', array
            (
                'harvest' => $this->object->title
            ),
            $this->request
        );
    }

    /**
     * Gets wines for a harvest
     */
    public function get_harvests(array $args)
    {
        $changed_harvests = array();
        $this->data['admin'] = false;
        $this->data['addharvest'] = false;
        $this->data['harvest'] = array();
        $this->data['harvests'] = array();
        $this->data['container_type'] = 'http://purl.org/dc/dcmitype/Collection';

        if (   ! isset($args['harvest'])
            && ! midgardmvc_ui_create_injector::can_use())
        {
            throw new midgardmvc_exception_notfound("Please specify a valid harvest");
        }

        $qs = $this->prepare_qs((isset($args['harvest'])) ? $args['harvest'] : '');
        $qs->execute();
        $harvests = $qs->list_objects();

        $this->data['urlpattern'] = $this->mvc->dispatcher->generate_url(
            'harvest_read',
            array (
                'year' => $this->mvc->configuration->starting_year,
                'harvest' => 'harvest'
            ),
            $this->request
        );

        // get all years
        $qs = eu_urho_winery_controllers_year::prepare_qs();
        $qs->execute();
        $years = $qs->list_objects();
        $this->data['years'] = new midgardmvc_ui_create_container();
        $dummy = new eu_urho_winery_year();
        $this->data['years']->set_placeholder($dummy);

        foreach ($years as $year)
        {
            $this->data['years']->attach($year);
        }

        foreach ($harvests as $harvest)
        {
            $harvest->localurl = false;
            $harvest->urlpattern = $this->data['urlpattern'];

            if (! isset($args['harvest']))
            {
                $harvest->localurl = $this->mvc->dispatcher->generate_url('harvest_read', array('year' => $harvest->year, 'harvest' => $harvest->title), $this->request);
            }

            $changed_harvests[] = $harvest;
        }

        $this->data['harvests'] = $changed_harvests;
        unset($harvests);

        if (midgardmvc_ui_create_injector::can_use())
        {
            $this->data['admin'] = true;
            $this->data['addharvest'] = true;
            // Define placeholder to be used with UI on empty containers

#            $dummy = new eu_urho_winery_harvest();
            $this->data['harvests'] = new midgardmvc_ui_create_container();
#            $this->data['harvests']->set_placeholder($dummy);

            if (! count($changed_harvests))
            {
                $this->data['harvests']->attach($dummy);
            }

            // rdf mapping
            foreach ($changed_harvests as $harvest)
            {
                $this->data['harvests']->attach($harvest);
            }

            if (   (   count($changed_harvests) == 1
                    || isset($args['harvest']))
                && ! $this->data['addharvest'])
            {
                $this->data['harvests']->rewind();
                $this->data['harvest'] = $this->data['harvests']->current();
            }
        }
        else
        {
            if (! count($changed_harvests))
            {
                throw new midgardmvc_exception_notfound("No data published for " . $args['harvest']);
            }
        }
    }

    /**
     * Returns a QuerySelect object
     */
    private function prepare_qs($harvest = null)
    {
        $storage = new midgard_query_storage('eu_urho_winery_harvest');
        $qs = new midgard_query_select($storage);

        $approved_constraint = null;

        if ($harvest)
        {
            $harvest_constraint = new midgard_query_constraint(
                new midgard_query_property('title'),
                '=',
                new midgard_query_value($harvest)
            );
        }
        else
        {
            $harvest_constraint = new midgard_query_constraint(
                new midgard_query_property('title'),
                '<>',
                new midgard_query_value('')
            );
        }

        if ( ! midgardmvc_ui_create_injector::can_use() )
        {
            // Regular user, hide unapproved articles
            $approved_constraint = new midgard_query_constraint(
                new midgard_query_property('metadata.isapproved'),
                '=',
                new midgard_query_value(true)
            );
        }

        if (   $harvest_constraint
            && $approved_constraint)
        {
            $qc = new midgard_query_constraint_group('AND');
            $qc->add_constraint($harvest_constraint);
            $qc->add_constraint($approved_constraint);
            unset($approved_constraint);
        }
        else
        {
            $qc = $harvest_constraint;
        }

        unset($harvest_constraint);

        $qs->add_order(new midgard_query_property('title'), SORT_ASC);

        if ($qc)
        {
            $qs->set_constraint($qc);
        }

        return $qs;
    }
}
?>