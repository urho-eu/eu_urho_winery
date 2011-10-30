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
                $qs = $this->prepare_qs(null, $args['harvest']);
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
            throw new midgardmvc_exception_notfound("No data published for " . $args['year'] . '/' . $args['harvest']);
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
                'harvest' => $this->object->name
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
                'harvest' => $this->object->name
            ),
            $this->request
        );
    }

    /**
     * Gets wines for a harvest
     */
    public function get_harvests(array $args)
    {
        // todo: complain if no years registered
        // todo: complain if no plantations registered

        $changed_harvests = array();
        $this->data['admin'] = false;
        $this->data['addharvest'] = false;
        $this->data['harvest'] = array();
        $this->data['harvests'] = array();
        $this->data['container_type'] = 'http://purl.org/dc/dcmitype/Collection';

        $qs = $this->prepare_qs((isset($args['year'])) ? $args['year'] : null, (isset($args['harvest'])) ? $args['harvest'] : null);
        $qs->execute();
        $harvests = $qs->list_objects();

        if (midgardmvc_ui_create_injector::can_use())
        {
            $this->data['urlpattern'] = $this->mvc->dispatcher->generate_url(
                'harvest_read',
                array (
                    'year' => $this->mvc->configuration->starting_year,
                    'harvest' => 'harvest'
                ),
                $this->request
            );
        }

        // get all plantations
        $qs = eu_urho_winery_controllers_plantation::prepare_qs();
        $qs->execute();
        $plantations = $qs->list_objects();
        $this->data['plantations'] = new midgardmvc_ui_create_container();
        $dummy = new eu_urho_winery_plantation();
        $this->data['plantations']->set_placeholder($dummy);

        foreach ($plantations as $plantation)
        {
            $this->data['plantations']->attach($plantation);
        }

        foreach ($harvests as $harvest)
        {
            $harvest->localurl = false;
            if (midgardmvc_ui_create_injector::can_use())
            {
                $harvest->urlpattern = $this->data['urlpattern'];
            }

            if (! isset($args['harvest']))
            {
                $year = (int)substr($harvest->date->__toString(), 0, 4);
                $harvest->localurl = $this->mvc->dispatcher->generate_url('harvest_read', array('year' => $year, 'harvest' => $harvest->name), $this->request);
            }

            $changed_harvests[] = $harvest;
        }

        $this->data['harvests'] = $changed_harvests;
        unset($harvests);

        if (midgardmvc_ui_create_injector::can_use())
        {
            $this->data['admin'] = true;

            if ( ! (   isset($args['year'])
                    && isset($args['harvest'])))
            {
                $this->data['addharvest'] = true;
            }

            // Define placeholder to be used with UI on empty containers
            $dummy = new eu_urho_winery_harvest();
            $this->data['harvests'] = new midgardmvc_ui_create_container();
            $this->data['harvests']->set_placeholder($dummy);

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

            if (count($this->data['plantations']))
            {
                $this->data['default_plantation'] = $plantations[0]->id;
            }

            $this->data['default_date'] = new midgard_datetime();
        }
        else
        {
            if (count($changed_harvests) == 1)
            {
                $this->data['harvest'] = $changed_harvests[0];
            }
            if (! count($changed_harvests))
            {
                throw new midgardmvc_exception_notfound("No data published for " . $args['year'] . '/' . $args['harvest']);
            }
        }
    }

    /**
     * Returns a QuerySelect object
     */
    public function prepare_qs($year = null, $harvest = null, $exception_guid = null)
    {
        $qc = null;
        $exception_constraint = null;
        $approved_constraint = null;

        $mvc = midgardmvc_core::get_instance();

        $storage = new midgard_query_storage('eu_urho_winery_harvest');
        $qs = new midgard_query_select($storage);
        $qc = new midgard_query_constraint_group('AND');

        $qc->add_constraint(new midgard_query_constraint(
            new midgard_query_property('id'),
            '>',
            new midgard_query_value(0)
        ));

        if ($year)
        {
            $startdate = $year . '-01-01';
            $enddate = $year . '-12-31';
            $year_constraint = new midgard_query_constraint_group('AND');

            $start = new midgard_query_constraint(
                new midgard_query_property('date'),
                '>=',
                new midgard_query_value($startdate)
            );
            $end = new midgard_query_constraint(
                new midgard_query_property('date'),
                '<=',
                new midgard_query_value($enddate)
            );

            $year_constraint->add_constraint($start);
            $year_constraint->add_constraint($end);

            unset($startdate);
            unset($enddate);
            unset($start);
            unset($end);

            $qc->add_constraint($year_constraint);
            unset($year_constraint);
        }

        if ($harvest)
        {
            $harvest_constraint = new midgard_query_constraint(
                new midgard_query_property('name'),
                '=',
                new midgard_query_value($harvest)
            );
        }
        else
        {
            $harvest_constraint = new midgard_query_constraint(
                new midgard_query_property('name'),
                '<>',
                new midgard_query_value('')
            );
        }
        $qc->add_constraint($harvest_constraint);
        unset($harvest_constraint);

        if ( ! midgardmvc_ui_create_injector::can_use() )
        {
            // Regular user, hide unapproved articles
            $approved_constraint = new midgard_query_constraint(
                new midgard_query_property('metadata.isapproved'),
                '=',
                new midgard_query_value(true)
            );
            $qc->add_constraint($approved_constraint);
            unset($approved_constraint);
        }

        if ($exception_guid)
        {
            $exception_constraint = new midgard_query_constraint(
                new midgard_query_property('guid'),
                '<>',
                new midgard_query_value($exception_guid)
            );
            $qc->add_constraint($exception_constraint);
            unset($exception_constraint);
        }

        $qs->set_constraint($qc);
        $qs->add_order(new midgard_query_property('date'), SORT_ASC);

        return $qs;
    }
}
?>