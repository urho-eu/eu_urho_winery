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

        $this->mvc = midgardmvc_core::get_instance();
        $this->mvc->i18n->set_translation_domain('eu_urho_winery');

        $default_language = $this->mvc->configuration->default_language;

        if (! isset($default_language))
        {
            $default_language = 'en_US';
        }

        $this->mvc->i18n->set_language($default_language, false);
    }

    /**
     * @todo: docs
     */
    public function load_object(array $args)
    {
        try
        {
            $this->object = new eu_urho_winery_wine($args['wine']);
        }
        catch (midgard_error_exception $e)
        {
            throw new midgardmvc_exception_notfound($e->getMessage());
        }

        if (   ! midgardmvc_ui_create_injector::can_use()
            && ! $this->object->is_approved())
        {
            // Regular user, hide unapproved articles
            // TODO: This check should be moved to authentication
            throw new midgardmvc_exception_notfound("No wine found");
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
     * @todo: docs
     */
    public function get_read(array $args)
    {
        parent::get_read($args);
    }
}
?>