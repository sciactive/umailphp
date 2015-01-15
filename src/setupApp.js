angular.module('setupApp', ['ngRoute', 'ui.codemirror'])
.controller('MainController', ['$scope', '$route', '$routeParams', '$location', function ($scope, $route, $routeParams, $location) {
	$scope.$route = $route;
	$scope.$location = $location;
	$scope.$routeParams = $routeParams;
}])

.controller('RenditionController', ['$scope', '$routeParams', '$timeout', function ($scope, $routeParams, $timeout) {
	$scope.params = $routeParams;
	$scope.Tilmeld = Tilmeld;
	$scope.definitions = Definitions;
	$scope.examples = Examples;
	$scope.entities = [];
	$scope.success = null;

	Nymph.getEntities({'class': '\\µMailPHP\\Rendition'}).then(function(entities){
		$scope.entities = entities;
		$scope.$apply();
	});

	$scope.askDefaultContent = function(){
		if (Definitions[$scope.entity.data.definition].html && Definitions[$scope.entity.data.definition].subject) {
			if (confirm("Would you like to start with this definition's default content and subject?")) {
				$scope.entity.data.subject = Definitions[$scope.entity.data.definition].subject;
				$scope.entity.data.content = Definitions[$scope.entity.data.definition].html;
			}
		} else if (Definitions[$scope.entity.data.definition].html) {
			if (confirm("Would you like to start with this definition's default content?")) {
				$scope.entity.data.content = Definitions[$scope.entity.data.definition].html;
			}
		} else if (Definitions[$scope.entity.data.definition].subject) {
			if (confirm("Would you like to start with this definition's default subject?")) {
				$scope.entity.data.subject = Definitions[$scope.entity.data.definition].subject;
			}
		}
	};

	$scope.entity = new Rendition();

	$scope.saveEntity = function(){
		$scope.entity.save().then(function(success){
			if (success) {
				if (!$scope.entity.inArray($scope.entities)) {
					$scope.entities.push($scope.entity);
				}
				$scope.success = true;
				$timeout(function(){
					$scope.success = null;
				}, 1000);
				$scope.$apply();
			} else {
				alert("Error saving rendition.");
			}
		}, function(){
			alert("Error communicating data.");
		});
	};

	$scope.checkNewEntity = function(){
		if (!$scope.entity) {
			$scope.entity = new Rendition();
		}
	};
}])

.controller('TemplateController', ['$scope', '$routeParams', '$timeout', function ($scope, $routeParams, $timeout) {
	$scope.params = $routeParams;
	$scope.Tilmeld = Tilmeld;
	$scope.examples = Examples;
	$scope.entities = [];
	$scope.success = null;

	Nymph.getEntities({'class': '\\µMailPHP\\Template'}).then(function(entities){
		$scope.entities = entities;
	});

	$scope.entity = new Template();
	$scope.entity.defaultContent().then(function(){
		$scope.$apply();
	});

	$scope.saveEntity = function(){
		$scope.entity.save().then(function(success){
			if (success) {
				if (!$scope.entity.inArray($scope.entities)) {
					$scope.entities.push($scope.entity);
				}
				$scope.success = true;
				$timeout(function(){
					$scope.success = null;
				}, 1000);
				$scope.$apply();
			} else {
				alert("Error saving template.");
			}
		}, function(){
			alert("Error communicating data.");
		});
	};

	$scope.checkNewEntity = function(){
		if (!$scope.entity) {
			$scope.entity = new Template();
			$scope.entity.defaultContent().then(function(){
				$scope.$apply();
			});
		}
	};
}])

.config(['$routeProvider', '$locationProvider', function ($routeProvider, $locationProvider) {
	$routeProvider
		.when('/', {
			templateUrl: baseURL+'html/instructions.html'
		})
		.when('/rendition/:entityId?', {
			templateUrl: baseURL+'html/rendition.html',
			controller: 'RenditionController'
		})
		.when('/template/:entityId?', {
			templateUrl: baseURL+'html/template.html',
			controller: 'TemplateController'
		});

	$locationProvider.html5Mode(false);
}]);