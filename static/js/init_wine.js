jQuery(document).ready(function($)
{
    bind_harvest_change();

    // rebind harvest change so that it works on dynamically added elements
    jQuery('button.add').bind('click', function(event)
    {
        bind_harvest_change();
        // fire the change event on the harvest select of the new wine
        jQuery(event.currentTarget).next().find('.harvestselect').trigger('change');
    });

    jQuery('.harvestselect').each(function(index, element)
    {
        var harvest = jQuery(element).next().html();
        console.log('set harvest to ' + harvest);
        jQuery(element).children("option[value='" + harvest + "']").attr('selected', 'selected');
    });
});

/**
 * called when the harvest select changed
 * needed to add it as a function, because it is directly added to the onchange
 * property of that select, so it works on newly added wines too
 */
function bind_harvest_change()
{
    jQuery('.harvestselect').bind('change', function(event)
    {
        var object = event.currentTarget;
        var harvest = jQuery(object).children(':selected').val();
        jQuery(object).next().html(harvest);
        midgardCreate.Editable.setModified(true);
    });
}