jQuery(document).ready(function(jQuery)
{
    if (typeof wineryUtils === 'undefined')
    {
        /**
         * dummy object
         */
        wineryUtils = {};
    }

    /**
     * Returns the string representation of the client's timezone
     * in the form of [+|-]hh:mm
     */
    wineryUtils.determineTimeZone = function()
    {
        // determine the time zone offset of the client computer
        var _date = new Date();
        var _tz = _date.getTimezoneOffset();
        var tz_hour = Math.abs(parseInt(_tz / 60));
        var tz_mins = Math.abs(_tz % 60);

        var tz = (_tz < 0) ? '+' : '-';
        tz += (tz_hour < 10) ? '0' + tz_hour : tz_hour;
        tz += ':';
        tz += (tz_mins < 10) ? '0' + tz_mins : tz_mins;

        return tz;
    }
});