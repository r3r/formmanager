var formManagerControllers = angular.module('formManagerControllers', []);

formManagerControllers.controller('SideBarCtrl', ['$scope', '$location', function ($scope, $location) {
    $scope.isActive = function (route) {
        return route === $location.path();
    }
}]);

formManagerControllers.controller('FormListCtrl', ['$scope', '$route', 'Form', function ($scope, $route, Form) {
    $scope.title = "Forms";
    $scope.forms = [];
    $scope.headers = [];
    $scope.fetchError = null;
    $scope.alertMsg = null;

    Form.get({}, function (success) {
        $scope.fetchError = null;
        $scope.forms = success.data;
        if ($scope.forms.length != 0) {
            $scope.headers = Object.keys($scope.forms[0]);
        }

    }, function (err) {
        $scope.forms = [];
        $scope.headers = [];
        $scope.fetchError = "Oh snap! Could not fetch data. Error: " + err.data.error + ". Error Code: " + err.data.responseCode;
        if (err.data.detail != null) {
            $scope.fetchError += ". Detail: " + err.data.detail;
        }
    })

    $scope.delete = function (element) {
        Form.delete({form_id: element.id}, function (success) {
            $route.reload();
            $scope.alertMsg = "Success! " + success.success;
        }, function (err) {
            $scope.fetchError = "Oh snap! Could not delete form. Error: " + err.data.error + ". Error Code: " + err.data.responseCode;
            if (err.data.detail != null) {
                $scope.fetchError += ". Detail: " + err.data.detail;
            }
        });
    }

}]);

formManagerControllers.controller('FormCreateCtrl', ['$scope', '$location', 'Form', 'FormElements', function ($scope, $location, Form, FormElements) {
    $scope.title = "Forms";
    $scope.headers = [];
    $scope.fetchError = null;
    $scope.elementInstances = [];
    $scope.elementTypes = [];
    $scope.elementType = null;


    FormElements.get({}, function (success) {
        $scope.fetchError = null;
        $scope.elementTypes = success.data;
        $scope.elementType = $scope.elementTypes[0];
    }, function (err) {
        $scope.elementTypes = [];
        $scope.fetchError = "Oh snap! Could not fetch data. Error: " + err.data.error + ". Error Code: " + err.data.responseCode;
        if (err.data.detail != null) {
            $scope.fetchError += ". Detail: " + err.data.detail;
        }
    })


    $scope.add = function (eType) {

        var newInstance = {};
        newInstance.name = "";
        newInstance.type = eType;
        newInstance.label = "";
        newInstance.options = {};
        if (eType.db_column_type == "string") {
            newInstance.options.length = 255;
        }
        if (eType.multi_valued == 1) {
            newInstance.multi_values = "Enter comma separated values to use for this field.";
            newInstance.options.many_to_many = 0;
        }
        $scope.elementInstances.push(newInstance);
    }


    $scope.remove = function (instance) {
        console.log(instance);
        var index = $scope.elementInstances.indexOf(instance);
        if (index > -1) {
            $scope.elementInstances.splice(index, 1);
        }
    }


    $scope.submit = function (name, elements) {
        //Convert Mutli_valued String to array
        elements.forEach(function (el, ind, arr) {
            if (el.type.multi_valued == 1) {
                arr[ind].multi_values = el.multi_values.split(",")
            }
        });
        var data = new Form();
        data.form_name = name;
        data.form_elements = elements;
        data.$save({}, function (success) {
            //console.log(success);
            $location.path('/form/view');
        }, function (err) {
            $scope.fetchError = "Oh snap! Could not create form. Error: " + err.data.error + ". Error Code: " + err.data.responseCode;
            if (err.data.detail != null) {
                $scope.fetchError += ". Detail: " + err.data.detail;
            }
        })
    }
}]);
//TODO Fix this up and serve it from backend
formManagerControllers.controller('FormEditCtrl', ['$scope', '$location', '$routeParams', 'Form', 'FormElements', function ($scope, $location, $routeParams, Form, FormElements) {
    $scope.id = $routeParams.id;
    $scope.title = "Forms";
    $scope.headers = [];
    $scope.fetchError = null;
    $scope.elementInstances = [];
    $scope.elementTypes = [];
    $scope.elementType = null;

    FormElements.get({}, function (success) {
        $scope.fetchError = null;
        $scope.elementTypes = success.data;
        $scope.elementType = $scope.elementTypes[0];
    }, function (err) {
        $scope.elementTypes = [];
        $scope.fetchError = "Oh snap! Could not fetch data. Error: " + err.data.error + ". Error Code: " + err.data.responseCode;
        if (err.data.detail != null) {
            $scope.fetchError += ". Detail: " + err.data.detail;
        }
    });

    Form.get({form_id: $scope.id}, function (success) {
        $scope.elementInstances = success.data.schema;
        console.log($scope.elementInstances)
        $scope.alertMsg = "Success! " + success.success;
    }, function (err) {
        $scope.fetchError = "Oh snap! Could not delete form. Error: " + err.data.error + ". Error Code: " + err.data.responseCode;
        if (err.data.detail != null) {
            $scope.fetchError += ". Detail: " + err.data.detail;
        }
    });


}]);

formManagerControllers.controller('FormElementsListCtrl', ['$scope', 'FormElements', function ($scope, FormElements) {
    $scope.title = "Form Elements";
    $scope.elements = [];
    $scope.headers = [];
    $scope.fetchError = null;
    FormElements.get({}, function (success) {
        $scope.fetchError = null;
        $scope.elements = success.data;
        $scope.headers = Object.keys($scope.elements[0]);
    }, function (err) {
        $scope.elements = [];
        $scope.headers = [];
        $scope.fetchError = "Oh snap! Could not fetch data. Error: " + err.data.error + ". Error Code: " + err.data.responseCode;
        if (err.data.detail != null) {
            $scope.fetchError += ". Detail: " + err.data.detail;
        }
    })

}]);