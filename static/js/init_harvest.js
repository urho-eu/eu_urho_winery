jQuery(document).ready(function(jQuery)
{
    if (   jQuery('ul.harvests li').length > 0
        || jQuery('article.harvest').length > 0)
    {
        bind_all();
    }

    jQuery('button.add').bind('click', function(event)
    {
        bind_all();
    });

});

function bind_all()
{
    jQuery('input.datepicker').each(function(index, element)
    {
        var current = jQuery(element).next().html().substr(0, 10);
        if (current !== '0001-01-01')
        {
            jQuery(element).val(current);
        }
    });

    jQuery('input.datepicker').datepicker(
    {
        dateFormat: 'yy-mm-dd',
        changeYear: true,
        changeMonth: true,
        showWeek: true,
        showMonthAfterYear: true,
        yearRange: '-10:+0',
        maxDate: '+0d',
        onSelect: function(dateText, inst)
        {
            var tz = wineryUtils.determineTimeZone();
            jQuery(this).next().html(dateText + 'T00:00:00' + tz);
            midgardCreate.Editable.setModified(true);
        }
    });

    // note: if using the default property in template then no need for this
    /*
        jQuery('select.plantationselect').each(function(index, element)
        {
            var plantation = jQuery(element).children(":selected").val();
            jQuery(element).next().html(plantation);
            console.log('set plantation: ' + plantation);
        });
    */

    jQuery('select.plantationselect').bind('change', function(event)
    {
        var object = event.currentTarget;
        var plantation = jQuery(object).children(':selected').val();
        jQuery(object).next().html(plantation);
        midgardCreate.Editable.setModified(true);
    });
}
