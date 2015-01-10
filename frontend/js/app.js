/**
 * Created by RiteshReddy on 1/10/15.
 */

var formManagerApp = angular.module('formManagerApp', [
    'ngRoute',
    'formManagerControllers',
    'formManagerServices'

]);

formManagerApp.config(['$routeProvider', function ($routeProvider) {
    $routeProvider.
        when('/#/form/view', {
            templateUrl: 'assets/partials/listView.html',
            controller: 'FormListCtrl'
        }).
        otherwise({
            redirectTo: '/#/form/view'
        });
}]);


var formManagerServices = angular.module('formManagerServices', ['ngResource']);

formManagerServices.factory('Form', ['$resource', function ($resource) {
    return $resource('http://localhost/Form%20Management%20System/backend/meta/Form_CRUD/:id');
}]);


var formManagerControllers = angular.module('formManagerControllers', []);
formManagerControllers.controller('FormListCtrl', ['$scope', 'Form', function ($scope, Form) {
    $scope.forms = [];
    $scope.fetchError = null;
    Form.get({}, function (success) {
        $scope.fetchError = null;
        $scope.forms = success.data;
    }, function (err) {
        $scope.forms = [];
        $scope.fetchError = "Oh snap! Could not fetch data. Error: " + err.data.error + ". Error Code: " + err.data.responseCode;
        if (err.data.detail != null) {
            $scope.fetchError += ". Detail: " + err.data.detail;
        }
    })

}]);