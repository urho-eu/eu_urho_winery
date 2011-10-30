jQuery(document).ready(function($)
{
    bind_date_change();

    jQuery('button.add').bind('click', function(event)
    {
        bind_date_change();
    });

});

function bind_date_change()
{
    var current = jQuery('input.plantedpicker').next().html().substr(0, 10);

    if (current !== '0001-01-01')
    {
        jQuery('input.plantedpicker').val(current);
    }

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
            jQuery(this).next().html(dateText + 'T00:00:00+00:00');
            midgardCreate.Editable.setModified(true);
        }
    });
}