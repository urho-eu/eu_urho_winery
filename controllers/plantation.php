<?php
class eu_urho_winery_controllers_plantation extends midgardmvc_core_controllers_baseclasses_crud
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
        if (isset($args['plantation']))
        {
            try
            {
                $qs = $this->prepare_qs($args['plantation']);
                $qs->execute();
                $plantations = $qs->list_objects();
                if (count($plantations))
                {
                    $this->object = new eu_urho_winery_plantation($plantations[0]->guid);
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
            throw new midgardmvc_exception_notfound("Please specify a valid plantation");
        }

        if (   ! midgardmvc_ui_create_injector::can_use()
            && (   ! $this->object
                || ! $this->object->is_approved()))
        {
            // Regular user, hide unapproved articles
            // TODO: This check should be moved to authentication
            throw new midgardmvc_exception_notfound("No data published for " . $args['plantation']);
        }

        $this->object->rdfmapper = new midgardmvc_ui_create_rdfmapper($this->object);
        $this->mvc->head->set_title($this->object->title);
    }

    /**
     * @todo: docs
     */
    public function prepare_new_object(array $args)
    {
        $this->object = new eu_urho_winery_plantation();
        $this->object->rdfmapper = new midgardmvc_ui_create_rdfmapper($this->object);
    }

    /**
     * @todo: docs
     */
    public function get_url_read()
    {
        return $this->mvc->dispatcher->generate_url
        (
            'plantation_read', array
            (
                'plantation' => $this->object->name
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
            'plantation_read', array
            (
                'plantation' => $this->object->name
            ),
            $this->request
        );
    }

    /**
     * Gets wines for a plantation
     */
    public function get_plantations(array $args)
    {
        $this->data['admin'] = false;
        $this->data['addplantation'] = false;
        $this->data['plantation'] = array();
        $this->data['plantations'] = array();
        $this->data['container_type'] = false;

/*
        if (   ! isset($args['plantation'])
            && ! midgardmvc_ui_create_injector::can_use())
        {
            throw new midgardmvc_exception_notfound("Please specify a valid plantation");
        }
*/
        $qs = $this->prepare_qs((isset($args['plantation'])) ? $args['plantation'] : '');

        $qs->execute();
        $plantations = $qs->list_objects();

        $changed_plantations = array();

        $this->data['urlpattern'] = $this->mvc->dispatcher->generate_url('plantation_read', array('plantation' => 'plantation'), $this->request);

        foreach ($plantations as $plantation)
        {
            $plantation->localurl = false;
            $plantation->urlpattern = $this->data['urlpattern'];

            if (! isset($args['plantation']))
            {
                $plantation->localurl = $this->mvc->dispatcher->generate_url('plantation_read', array('plantation' => $plantation->name), $this->request);
            }

            $changed_plantations[] = $plantation;
        }

        $this->data['plantations'] = $changed_plantations;
        unset($plantations);

        if (midgardmvc_ui_create_injector::can_use())
        {
            $this->data['admin'] = true;
            $this->data['addplantation'] = true;
            // Define placeholder to be used with UI on empty containers
            $this->data['container_type'] = 'http://purl.org/dc/dcmitype/Collection';

            $dummy = new eu_urho_winery_plantation();
            $this->data['plantations'] = new midgardmvc_ui_create_container();
            $this->data['plantations']->set_placeholder($dummy);

            if (! count($changed_plantations))
            {
                $this->data['plantations']->attach($dummy);
            }

            // rdf mapping
            foreach ($changed_plantations as $plantation)
            {
                $this->data['plantations']->attach($plantation);
            }

            if (   (   count($changed_plantations) == 1
                    || isset($args['plantation']))
                && ! $this->data['addplantation'])
            {
                $this->data['plantations']->rewind();
                $this->data['plantation'] = $this->data['plantations']->current();
            }
        }
        else
        {
            if (! count($changed_plantations))
            {
                throw new midgardmvc_exception_notfound("No data published for " . $args['plantation']);
            }
        }
    }

    /**
     * Returns a QuerySelect object
     */
    public function prepare_qs($plantation = null, $exception_guid = null)
    {
        $qc = null;
        $exception_constraint = null;
        $approved_constraint = null;

        $storage = new midgard_query_storage('eu_urho_winery_plantation');
        $qs = new midgard_query_select($storage);
        $qc = new midgard_query_constraint_group('AND');

        $qc->add_constraint(new midgard_query_constraint(
            new midgard_query_property('id'),
            '>',
            new midgard_query_value(0)
        ));

        if ($plantation)
        {
            $plantation_constraint = new midgard_query_constraint(
                new midgard_query_property('name'),
                '=',
                new midgard_query_value($plantation)
            );
        }
        else
        {
            $plantation_constraint = new midgard_query_constraint(
                new midgard_query_property('name'),
                '<>',
                new midgard_query_value('')
            );
        }
        $qc->add_constraint($plantation_constraint);
        unset($plantation_constraint);

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

        $qs->add_order(new midgard_query_property('name'), SORT_ASC);
        $qs->set_constraint($qc);

        unset($plantation_constraint);

        return $qs;
    }
}
?>