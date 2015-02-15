/**
 * Created by RiteshReddy on 1/10/15.
 */

var formManagerApp = angular.module('formManagerApp', [
    'ngRoute',
    'formManagerControllers',
        'formManagerServices',
        'formManagerDirectives'
    ]).run(function ($rootScope) {
    $rootScope.backendUrl = "../backend";
});

formManagerApp.config(['$routeProvider', function ($routeProvider) {
    $routeProvider.
        when('/form/view', {
            templateUrl: 'assets/partials/form/listView.html',
            controller: 'FormListCtrl'
        }).
        when('/form/create', {
            templateUrl: 'assets/partials/form/createView.html',
            controller: 'FormCreateCtrl'
        }).
        when('/form/edit/:id', {
            templateUrl: 'assets/partials/form/editView.html',
            controller: 'FormEditCtrl'
        }).
        when('/formelements/view', {
            templateUrl: 'assets/partials/formelements/listView.html',
            controller: 'FormElementsListCtrl'
        }).
        otherwise({
            redirectTo: '/form/view'
        });
}]);
