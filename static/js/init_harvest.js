jQuery(document).ready(function($){
    jQuery('article.harvest span.date').datetime({
        format: 'yy-mm-dd',
        showWeek: true,
        altField: '.year',
        altFormat: "yy"
    });
});
