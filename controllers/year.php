<?php
class eu_urho_winery_controllers_year extends midgardmvc_core_controllers_baseclasses_crud
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
     * Redirect / requests
     */
    public function get_redirect(array $args)
    {
        $this->mvc->head->relocate
        (
            $this->mvc->dispatcher->generate_url
            (
                '/', array(), $this->request
            )
        );
    }

    /**
     * @todo: docs
     */
    public function load_object(array $args)
    {
        $year = date('Y') - 1;

        if (isset($args['year']))
        {
            $year = $args['year'];
        }

        if (   is_numeric($year)
            && $year > 1970
            && $year <= date('Y'))
        {
            try
            {
                $qs = $this->prepare_qs($year);
                $qs->execute();
                $years = $qs->list_objects();
                if (count($years))
                {
                    if (! isset($args['year']))
                    {
                        // if the request did not have a year, then redirect
                        $this->mvc->head->relocate
                        (
                            $this->mvc->dispatcher->generate_url
                            (
                                'year_read',
                                array
                                (
                                    'year' => $year
                                ),
                                $this->request
                            )
                        );
                    }
                    $this->object = new eu_urho_winery_year($years[0]->guid);
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
            throw new midgardmvc_exception_notfound("Invalid year requested: " . $year);
        }

        if (   ! midgardmvc_ui_create_injector::can_use()
            && (   ! $this->object
                || ! $this->object->is_approved()))
        {
            // Regular user, hide unapproved articles
            // TODO: This check should be moved to authentication
            throw new midgardmvc_exception_notfound("No data published for " . $year);
        }

        $this->object->rdfmapper = new midgardmvc_ui_create_rdfmapper($this->object);
        $this->mvc->head->set_title($this->object->title);
    }

    /**
     * @todo: docs
     */
    public function prepare_new_object(array $args)
    {
        $this->object = new eu_urho_winery_year();
        $this->object->title = date('Y');
        $this->object->rdfmapper = new midgardmvc_ui_create_rdfmapper($this->object);
    }

    /**
     * @todo: docs
     */
    public function get_url_read()
    {
        return $this->mvc->dispatcher->generate_url
        (
            'year_read', array
            (
                'year' => $this->object->title
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
            'year_read', array
            (
                'year' => $this->object->title
            ),
            $this->request
        );
    }

    /**
     * Gets wines for a year
     */
    public function get_years(array $args)
    {
        $year = null;
        $this->data['admin'] = false;
        $this->data['addyear'] = false;
        $this->data['year'] = array();
        $this->data['years'] = array();
        $this->data['container_type'] = false;

        if (isset($args['year']))
        {
            $year = $args['year'];
            if (   ! is_numeric($year)
                || $year < 1970
                || $year > date('Y'))
            {
               throw new midgardmvc_exception_notfound("Invalid year requested: " . $year);
            }
        }

        $qs = $this->prepare_qs($year);

        $qs->execute();
        $years = $qs->list_objects();

        $changed_years = array();

        if (midgardmvc_ui_create_injector::can_use())
        {
            $this->data['urlpattern'] = $this->mvc->dispatcher->generate_url(
                'year_read',
                array(
                    'year' => $this->mvc->configuration->starting_year,
                ),
                $this->request
            );
        }

        foreach ($years as $year)
        {
            $year->localurl = false;

            if (midgardmvc_ui_create_injector::can_use())
            {
                $year->urlpattern = $this->data['urlpattern'];
            }

            if (! isset($args['year']))
            {
                $year->localurl = $this->mvc->dispatcher->generate_url('year_read', array('year' => $year->title), $this->request);
            }

            $changed_years[] = $year;
        }

        $this->data['years'] = $changed_years;
        unset($years);

        if (midgardmvc_ui_create_injector::can_use())
        {
            $this->data['admin'] = true;
            // Define placeholder to be used with UI on empty containers
            $this->data['container_type'] = 'http://purl.org/dc/dcmitype/Collection';

            $dummy = new eu_urho_winery_year();
            $this->data['years'] = new midgardmvc_ui_create_container();
            $this->data['years']->set_placeholder($dummy);

            if (! isset($args['year']))
            {
                $this->data['addyear'] = true;
            }
            else
            {
                $this->data['addyear'] = false;
                if (! count($changed_years))
                {
                    throw new midgardmvc_exception_notfound("No data published for " . $year);
                }
            }

            // rdf mapping
            foreach ($changed_years as $year)
            {
                $this->data['years']->attach($year);
            }

            if (   (   count($changed_years) == 1
                    || isset($args['year']))
                && ! $this->data['addyear'])
            {
                $this->data['years']->rewind();
                $this->data['year'] = $this->data['years']->current();
                $this->data['years'] = false;
            }
        }
        else
        {
            if (count($changed_years) == 1)
            {
                $this->data['year'] = $changed_years[0];
            }
            if (! count($changed_years))
            {
                throw new midgardmvc_exception_notfound("No data published for " . $year);
            }
        }

        unset($changed_years);
    }

    /**
     * Returns a QuerySelect object
     */
    public function prepare_qs($year = null)
    {
        $qc = null;
        $approved_constraint = null;

        $storage = new midgard_query_storage('eu_urho_winery_year');
        $qs = new midgard_query_select($storage);
        $qc = new midgard_query_constraint_group('AND');

        $qc->add_constraint(new midgard_query_constraint(
            new midgard_query_property('id'),
            '>',
            new midgard_query_value(0)
        ));

        if (! $year)
        {
            $year_constraint = new midgard_query_constraint(
                new midgard_query_property('title'),
                '>=',
                new midgard_query_value($this->mvc->configuration->starting_year)
            );
        }
        else
        {
            $year_constraint = new midgard_query_constraint(
                new midgard_query_property('title'),
                '=',
                new midgard_query_value($year)
            );
        }
        $qc->add_constraint($year_constraint);
        unset($year_constraint);

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

        $qs->set_constraint($qc);
        $qs->add_order(new midgard_query_property('title'), SORT_DESC);

        return $qs;
    }
}
?>