jQuery(document).ready(function(jQuery)
{
    if (jQuery('ul.plantations li').length > 0)
    {
        bind_date_change();
    }

    jQuery('button.add').bind('click', function(event)
    {
        bind_date_change();
    });

});

function bind_date_change()
{
    jQuery('input.plantedpicker').each(function(index, element)
    {
        var current = jQuery(element).next().html().substr(0, 10);
        if (current !== '0001-01-01')
        {
            jQuery(element).val(current);
        }
    });

    jQuery('input.plantedpicker').datepicker(
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
}