<?php
class eu_urho_winery_controllers_wine extends midgardmvc_core_controllers_baseclasses_crud
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
        if (isset($args['wine']))
        {
            try
            {
                $qs = $this->prepare_qs($args['wine']);
                $qs->execute();
                $wines = $qs->list_objects();
                if (count($wines))
                {
                    $this->object = new eu_urho_winery_harvest($wines[0]->guid);
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
            throw new midgardmvc_exception_notfound("Please specify a valid wine");
        }

        if (   ! midgardmvc_ui_create_injector::can_use()
            && (   ! $this->object
                || ! $this->object->is_approved()))
        {
            // Regular user, hide unapproved articles
            // TODO: This check should be moved to authentication
            throw new midgardmvc_exception_notfound("No data published for " . $args['wine  ']);
        }

        $this->object->rdfmapper = new midgardmvc_ui_create_rdfmapper($this->object);
        $this->mvc->head->set_title($this->object->title);
    }

    /**
     * @todo: docs
     */
    public function prepare_new_object(array $args)
    {
        $this->object = new eu_urho_winery_wine();
        $this->object->rdfmapper = new midgardmvc_ui_create_rdfmapper($this->object);
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
                'wine' => $this->object->name
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
                'wine' => $this->object->name
            ),
            $this->request
        );
    }

    /**
     * Gets wines
     */
    public function get_wines(array $args)
    {
        $changed_wines = array();
        $this->data['admin'] = false;
        $this->data['addwine'] = false;
        $this->data['wine'] = array();
        $this->data['wines'] = array();
        $this->data['container_type'] = 'http://purl.org/dc/dcmitype/Collection';

        $this->data['urlpattern'] = $this->mvc->dispatcher->generate_url(
            'wine_read',
            array (
                'year' => $this->mvc->configuration->starting_year,
                'wine' => 'wine'
            ),
            $this->request
        );

        if (   ! isset($args['wine'])
            && ! midgardmvc_ui_create_injector::can_use())
        {
            throw new midgardmvc_exception_notfound("Please specify a valid wine");
        }

        $year = $this->mvc->configuration->starting_year;
        $guid = null;

        if (isset($args['year']))
        {
            $year = $args['year'];
            $qs = eu_urho_winery_controllers_harvest::prepare_qs($year, null, null);
        }
        else
        {
            $qs = eu_urho_winery_controllers_harvest::prepare_qs();
        }
        if (isset($args['wine']))
        {
          $wine = $args['wine'];
        }

        // get all harvests
        $qs->execute();
        $harvests = $qs->list_objects();
        $this->data['harvests'] = new midgardmvc_ui_create_container();
        //$dummy = new eu_urho_winery_harvest();
        //$this->data['harvests']->set_placeholder($dummy);

        foreach ($harvests as $harvest)
        {
            $this->data['harvests']->attach($harvest);
        }

        $qs = $this->prepare_qs((isset($args['year'])) ? $args['year'] : null, (isset($args['wine'])) ? $args['wine'] : null);
        $qs->execute();
        $wines = $qs->list_objects();

        foreach ($wines as $wine)
        {
            $wine->localurl = false;
            $wine->urlpattern = $this->data['urlpattern'];

            if (! isset($args['wine']))
            {
                $wine->localurl = $this->mvc->dispatcher->generate_url('wine_read', array('year' => $wine->year, 'wine' => $wine->name), $this->request);
            }

            $changed_wines[] = $wine;
        }

        $this->data['wines'] = $changed_wines;
        unset($wines);

        if (midgardmvc_ui_create_injector::can_use())
        {
            $this->data['admin'] = true;
            $this->data['addwine'] = true;
            // Define placeholder to be used with UI on empty containers

            $dummy = new eu_urho_winery_wine();
            $this->data['wines'] = new midgardmvc_ui_create_container();
            $this->data['wines']->set_placeholder($dummy);

            if (! count($changed_wines))
            {
                $this->data['wines']->attach($dummy);
            }

            // rdf mapping
            foreach ($changed_wines as $wine)
            {
                $this->data['wines']->attach($wine);
            }

            if (   (   count($changed_wines) == 1
                    || isset($args['wine']))
                && ! $this->data['addwine'])
            {
                $this->data['wines']->rewind();
                $this->data['wine'] = $this->data['wines']->current();
            }
        }
        else
        {
            if (! count($changed_wines))
            {
                throw new midgardmvc_exception_notfound("No data published for " . $args['wine']);
            }
        }
    }

    /**
     * Returns a QuerySelect object
     */
    public function prepare_qs($year = null, $name = null, $exception_guid = null)
    {
        $qc = null;
        $exception_constraint = null;
        $approved_constraint = null;

        $mvc = midgardmvc_core::get_instance();

        $storage = new midgard_query_storage('eu_urho_winery_wine');
        $qs = new midgard_query_select($storage);
        $qc = new midgard_query_constraint_group('AND');

        if (! $year)
        {
            $year = $mvc->configuration->starting_year;
        }

        if ($name)
        {
            $wine_constraint = new midgard_query_constraint(
                new midgard_query_property('name'),
                '=',
                new midgard_query_value($name)
            );
            $year_constraint = new midgard_query_constraint(
                new midgard_query_property('metadata.created'),
                '=',
                new midgard_query_value($year)
            );
        }
        else
        {
            $wine_constraint = new midgard_query_constraint(
                new midgard_query_property('name'),
                '<>',
                new midgard_query_value($name)
            );
            $year_constraint = new midgard_query_constraint(
                new midgard_query_property('metadata.created'),
                '>=',
                new midgard_query_value($year)
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
            $qc->add_constraint($approved_constraint);
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

        $qc->add_constraint($wine_constraint);
        $qc->add_constraint($year_constraint);

        $qs->set_constraint($qc);
        $qs->add_order(new midgard_query_property('metadata.created'), SORT_DESC);

        unset($wine_constraint);
        unset($year_constraint);

        return $qs;
    }

}
?>