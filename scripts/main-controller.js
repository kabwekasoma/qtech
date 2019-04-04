angular.module('quoteApp',['ngRoute']).
config(['$routeProvider', function($routeProvider) {
    $routeProvider
      .when('/', {
        templateUrl: 'views/step1.html',
        controller: 'quoterequest'
      })
      .when('/step2', {
      	templateUrl: 'views/step2.html',
      	controller: 'quoterequest'
      })
      .when('/step3', {
      	templateUrl: 'views/step3.html',
      	controller: 'quoterequest'
      })
      .when('/finish', {
      	templateUrl: 'views/finish.html',
      	controller: 'quoterequest'
      });
}]);
angular.module('quoteApp').controller('quoterequest',quoterequest, ['$location']);
//core function
function quoterequest($scope, $http,$window, $location) {
 /*	$scope.driver_type = {
        name: 'someoneelse'
      };*/
	$http.defaults.headers.post["Content-Type"] = "application/x-www-form-urlencoded;charset=utf-8";
	//$scope.error_response="";
	//$scope.createDetails = false;
	//$scope.createSuccess = false;
	//////

	//Form Variables
	$scope.services = [
      {name: 'Motor insurance'},
      {name: 'Property insurance'}
	];

	$scope.goStep1 = function () {
		$location.url('/');
	}

	$scope.goStep2 = function () {
		$location.url('step2');
	}

	$scope.goStep3 = function () {
		$location.url('step3');
	}

	$scope.goFinish = function () {
		$location.url('finish');
	}

	$scope.driver_type = [{
        name: "I am the regular driver ",
        value:1
    }, {
        name: "Someone else ",
        value:2
    }];
	$scope.request_quote = {
				vehicle_year : "",
				vehicle_make : "",
				vehicle_model: "",
				vehicle_description: "",
				cover_type: "",
				fullname: "",
				email: "",
				id: "",
				mobile: "",
	            license_type: "" 
			}
	$scope.postRequest = function(details){
		    console.log(details);

			var request = $.param({
						"year" :details.vehicle_year,
						"model" : details.vehicle_model,
						"description" : details.vehicle_description,
						"make" : details.vehicle_make,
						"cover" : details.cover_type,
						"fullname": details.fullname,
						"email": details.email,
						"id": details.id,
						"mobile": details.mobile,
						"license_type":details.license_type,
						"quotation_type":"ins_motor",
						"driver_type" :details.driver_type_selected
					});
			var url ="api/quotation/requests/add";
	
			
			$http.post(url, request)
			.success(function (data, status, headers, config) {
				alert(''+ data);
				console.log(data);
		
			})
			.error(function (data, status, headers, config) {
				console.log(data);
			}); 
	}
	//
}
