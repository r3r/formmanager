/**
 * Created by RiteshReddy on 1/11/15.
 */

var formManagerDirectives = angular.module('formManagerDirectives', []);
formManagerDirectives.directive('ngReallyClick', [function () {
    return {
        restrict: 'A',
        link: function (scope, element, attrs) {
            element.bind('click', function () {
                var message = attrs.ngReallyMessage;
                if (message && confirm(message)) {
                    scope.$apply(attrs.ngReallyClick);
                }
            });
        }
    }
}]);