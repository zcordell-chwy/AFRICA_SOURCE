 /* Originating Release: February 2019 */
RightNow.Widgets.AnswerContent = RightNow.Widgets.extend({
    constructor: function() {
        if(RightNow.Url.getParameter('s') !== null) {
            var url = location.pathname.split('/s/')[0] + '#__highlight';
            if ('replaceState' in window.history) {
                history.replaceState(null, null, url);
            }
        }
    }
});