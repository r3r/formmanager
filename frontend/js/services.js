var formManagerServices = angular.module('formManagerServices', ['ngResource']);


formManagerServices.factory('Form', ['$resource', '$rootScope', function ($resource, $rootScope) {
    return $resource($rootScope.backendUrl + '/meta/Form_CRUD/:id', { id: '@id' });
}]);


formManagerServices.factory('FormElements', ['$resource', '$rootScope', function ($resource, $rootScope) {
    return $resource($rootScope.backendUrl + '/meta/Form_Elements_CRUD/:id', { id: '@id' });
}]);